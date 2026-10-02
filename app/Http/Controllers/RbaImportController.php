<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\AccountCode;
use App\Models\RbaDocument;
use App\Models\RbaDetail;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RbaImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'start_row' => 'required|integer|min:1',
            'start_column' => 'required|string|max:2',
        ]);

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        $activeVersionName = RbaDocument::where('budget_year', $budgetYear)
            ->where('version', $activeVersion)
            ->value('version_name') ?? 'Induk';

        $file = $request->file('file');
        $startRow = $request->input('start_row');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            
            DB::beginTransaction();

            $activeDocument = null;
            $stack = []; // Stack to hold parent IDs by indent level

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $kodeRek = trim($sheet->getCell("A{$row}")->getValue() ?? '');
                $uraianCell = $sheet->getCell("B{$row}");
                $uraian = trim($uraianCell->getValue() ?? '');

                // 1. Check for Kode Rekening (Leaf Account)
                if ($kodeRek !== '') {
                    $account = AccountCode::where('code', $kodeRek)->whereDoesntHave('children')->first();
                    
                    if ($account) {
                        // Create or Get RbaDocument for this account
                        $activeDocument = RbaDocument::firstOrCreate([
                            'account_code_id' => $account->id,
                            'budget_year' => $budgetYear,
                            'version' => $activeVersion,
                        ], [
                            'version_name' => $activeVersionName,
                            'status' => 'draft',
                            'total_budget' => 0
                        ]);

                        // Clear existing details if we are re-importing over the same version
                        RbaDetail::where('rba_document_id', $activeDocument->id)->delete();
                        $activeDocument->update(['total_budget' => 0]);

                        // Reset stack for new document
                        $stack = [];
                    }
                    continue;
                }

                if (!$activeDocument) continue;
                if (empty($uraian)) continue;

                // 2. Determine Indent Level
                $indentLevel = $uraianCell->getStyle()->getAlignment()->getIndent();
                $originalVal = (string) ($uraianCell->getValue() ?? '');
                
                // Fallback for CSV or badly formatted files (count leading spaces)
                if ($indentLevel == 0) {
                    if (preg_match('/^(\s+)/', $originalVal, $matches)) {
                        $indentLevel = floor(strlen($matches[1]) / 2); // Assume 2 spaces per indent
                    } else if (str_starts_with($uraian, '[#]')) {
                        $indentLevel = 0;
                        $uraian = trim(substr($uraian, 3));
                    } else if (str_starts_with($uraian, '-')) {
                        $indentLevel = 1;
                        $uraian = trim(ltrim($uraian, '- '));
                    }
                } else {
                    // Clean up leading prefixes if they exist even with proper indent
                    if (str_starts_with($uraian, '[#]')) {
                        $uraian = trim(substr($uraian, 3));
                    } else if (str_starts_with($uraian, '- ')) {
                        $uraian = trim(substr($uraian, 2));
                    }
                }

                $colHarga = strtoupper($request->start_column);
                $colSat1 = $colHarga; $colSat1++;
                $colVol1 = $colSat1; $colVol1++;
                $colSat2 = $colVol1; $colSat2++;
                $colVol2 = $colSat2; $colVol2++;

                // 3. Read Financial Values (5 columns dynamically)
                $rawHarga = trim((string) ($sheet->getCell("{$colHarga}{$row}")->getCalculatedValue() ?? ''));
                $rawSat1  = trim((string) ($sheet->getCell("{$colSat1}{$row}")->getCalculatedValue() ?? ''));
                $rawVol1  = trim((string) ($sheet->getCell("{$colVol1}{$row}")->getCalculatedValue() ?? ''));
                $rawSat2  = trim((string) ($sheet->getCell("{$colSat2}{$row}")->getCalculatedValue() ?? ''));
                $rawVol2  = trim((string) ($sheet->getCell("{$colVol2}{$row}")->getCalculatedValue() ?? ''));

                $isDash = function ($val) {
                    $str = trim((string) $val);
                    return in_array($str, ['-', '–', '—', 'Rp -', 'Rp. -']);
                };

                $parseNum = function ($raw) use ($isDash) {
                    if (is_numeric($raw)) {
                        return (float) $raw;
                    }
                    if ($isDash($raw) || empty($raw)) {
                        return 0.0;
                    }
                    $clean = str_replace([',', 'Rp', 'rp', 'RP', ' '], '', (string) $raw);
                    return is_numeric($clean) ? (float) $clean : 0.0;
                };

                $harga = $parseNum($rawHarga);
                $vol1  = $parseNum($rawVol1);
                $vol2  = $parseNum($rawVol2);
                $sat1  = $rawSat1;
                $sat2  = $rawSat2;

                // Calculate final koefisien and combined satuan
                // If vol2 is 0 or empty, we just use vol1
                $koefisien = $vol1;
                $satuan = $sat1;
                
                if ($vol2 > 0) {
                    $koefisien = $vol1 * $vol2;
                    $satuan = ($sat1 !== '' ? $sat1 : '-') . '/' . ($sat2 !== '' ? $sat2 : '-');
                }

                // 4. Determine Type (item vs header)
                // A row is an item if:
                // - It has price and volume > 0, OR
                // - Price/volume/satuan cells explicitly contain dash '-' or '0', OR
                // - Satuan is provided along with price or volume
                $isPrefixedHeader = str_starts_with(trim($originalVal), '[#]');
                $hasDashIndicator = $isDash($rawHarga) || $isDash($rawVol1) || $isDash($rawSat1);
                $hasItemSpecs = ($rawSat1 !== '' && ($rawVol1 !== '' || $rawHarga !== ''));

                $isItem = !$isPrefixedHeader && (
                    ($koefisien > 0 && $harga > 0) ||
                    $hasDashIndicator ||
                    $hasItemSpecs
                );
                $type = $isItem ? 'item' : 'header';

                // 5. Determine Parent ID based on Indent Level
                $parentId = null;
                if ($indentLevel > 0) {
                    for ($l = $indentLevel - 1; $l >= 0; $l--) {
                        if (isset($stack[$l])) {
                            $parentId = $stack[$l];
                            break;
                        }
                    }
                }

                // 6. Save Detail
                $jumlah = $isItem ? ($harga * $koefisien) : 0;
                
                $detail = RbaDetail::create([
                    'rba_document_id' => $activeDocument->id,
                    'parent_id' => $parentId,
                    'type' => $type,
                    'uraian' => ltrim($uraian),
                    'harga' => $isItem ? $harga : null,
                    'vol_1' => $isItem ? ($vol1 > 0 ? $vol1 : ($isDash($rawVol1) ? 0 : null)) : null,
                    'satuan_1' => $isItem ? ($sat1 !== '' ? $sat1 : null) : null,
                    'vol_2' => $isItem ? ($vol2 > 0 ? $vol2 : ($isDash($rawVol2) ? 0 : null)) : null,
                    'satuan_2' => $isItem ? ($sat2 !== '' ? $sat2 : null) : null,
                    'koefisien' => $isItem ? $koefisien : null,
                    'satuan' => $isItem ? ($satuan !== '' ? $satuan : null) : null,
                    'jumlah' => $isItem ? $jumlah : null,
                ]);

                // Update stack for current level ONLY if this is a header
                if ($type === 'header') {
                    $stack[$indentLevel] = $detail->id;

                    // Clear deeper levels from stack to prevent stray children
                    foreach (array_keys($stack) as $lvl) {
                        if ($lvl > $indentLevel) {
                            unset($stack[$lvl]);
                        }
                    }
                }

                // Add to document total budget if it's an item
                if ($isItem) {
                    $activeDocument->total_budget += $jumlah;
                    $activeDocument->save();
                }
            }

            DB::commit();

            activity('setting')
                ->log("Mengimpor data Kertas Kerja RBA tahun anggaran {$budgetYear}");

            return redirect()->back()->with('message', 'Impor data RBA berhasil diselesaikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }
}
