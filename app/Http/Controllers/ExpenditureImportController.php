<?php

namespace App\Http\Controllers;

use App\Models\Expenditure;
use App\Models\ExpenditureDetail;
use App\Models\ExpenditureTax;
use App\Models\AccountCode;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExpenditureImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'treasurer_id' => 'required|exists:users,id',
            'kpa_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,submitted,authorized,disbursed',
            'end_row' => 'nullable|integer|min:2',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        
        
        $highestRow = $worksheet->getHighestRow();
        if ($request->filled('end_row')) {
            $highestRow = min((int) $request->end_row, $highestRow);
        }

        $stats = [
            'success' => 0,
            'skipped' => 0,
            'skipped_reasons' => []
        ];

        DB::beginTransaction();

        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $noSpp = trim($worksheet->getCell('B' . $row)->getValue() ?? '');
                
                // Lewati baris kosong atau baris header
                if (empty($noSpp) || strtolower($noSpp) == 'no. spp') {
                    continue;
                }

                // Cek Duplikasi No. SPP
                if (Expenditure::where('document_number', $noSpp)->exists()) {
                    $stats['skipped']++;
                    $stats['skipped_reasons'][] = "Baris $row dilewati: No. SPP '$noSpp' sudah ada di database.";
                    continue; // Skip duplikat
                }

                // 1. Tanggal SPP & SPM (Kolom D)
                $tanggalSppVal = $worksheet->getCell('D' . $row)->getValue();
                $tanggalSpp = null;
                if (!empty($tanggalSppVal)) {
                    if (is_numeric($tanggalSppVal)) {
                        $tanggalSpp = Date::excelToDateTimeObject($tanggalSppVal)->format('Y-m-d');
                    } else {
                        // Coba parse string
                        $tanggalSpp = date('Y-m-d', strtotime($tanggalSppVal));
                    }
                } else {
                    $tanggalSpp = date('Y-m-d'); // Fallback
                }

                // 2. Vendor (Kolom F - M)
                $namaPerusahaan = trim($worksheet->getCell('F' . $row)->getValue() ?? '');
                $vendorId = null;
                if (!empty($namaPerusahaan)) {
                    $vendor = Vendor::firstOrCreate(
                        ['name' => $namaPerusahaan],
                        [
                            'type' => trim($worksheet->getCell('G' . $row)->getValue() ?? 'Lainnya'), // Bentuk Perusahaan
                            'bank_name' => trim($worksheet->getCell('K' . $row)->getValue() ?? ''),
                            'bank_account_number' => trim($worksheet->getCell('L' . $row)->getValue() ?? ''),
                            'npwp' => trim($worksheet->getCell('M' . $row)->getValue() ?? ''),
                            'address' => trim($worksheet->getCell('H' . $row)->getValue() ?? ''),
                            'director_name' => trim($worksheet->getCell('J' . $row)->getValue() ?? ''), // Penandatangan
                        ]
                    );
                    $vendorId = $vendor->id;
                }

                // 3. User PPTK (Kolom BB/BC -> Nama/NIK)
                $namaPptk = trim($worksheet->getCell('BB' . $row)->getValue() ?? '');
                $ptkId = null;
                if (!empty($namaPptk)) {
                    $ptk = User::where('name', 'like', "%$namaPptk%")->first();
                    if ($ptk) {
                        $ptkId = $ptk->id;
                    }
                }
                
                // Fallback jika PPTK tidak ditemukan, gunakan treasurer_id yang dipilih agar tidak error
                if (!$ptkId) {
                    $ptkId = $request->treasurer_id; 
                }

                // 4. Proses 8 Set Kode Rekening (Mulai dari Kolom O, P, Q dan seterusnya hingga Kolom AL)
                // O = 15, P = 16, Q = 17. Total 8 set = 24 kolom (kolom 15 sampai 38).
                $detailsToInsert = [];
                $skipRow = false;
                
                for ($col = 15; $col <= 38; $col += 3) {
                    $kodeRekeningStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $jumlahStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2);
                    
                    $kodeRekening = trim($worksheet->getCell($kodeRekeningStr . $row)->getValue() ?? '');
                    $jumlah = (float)($worksheet->getCell($jumlahStr . $row)->getCalculatedValue() ?? 0);
                    
                    if (!empty($kodeRekening) && $jumlah > 0) {
                        // Cari Account Code
                        $accountCode = AccountCode::where('code', $kodeRekening)->first();
                        
                        if (!$accountCode) {
                            $stats['skipped']++;
                            $stats['skipped_reasons'][] = "Baris $row dilewati: Kode rekening '$kodeRekening' tidak ditemukan di database.";
                            $skipRow = true;
                            break; // Keluar dari loop set rekening
                        }
                        
                        $detailsToInsert[] = [
                            'account_code_id' => $accountCode->id,
                            'amount' => $jumlah
                        ];
                    }
                }

                if ($skipRow || empty($detailsToInsert)) {
                    if (!$skipRow) { // Berarti empty details
                        $stats['skipped']++;
                        $stats['skipped_reasons'][] = "Baris $row dilewati: Tidak ada nominal pengeluaran yang valid.";
                    }
                    continue; // Skip baris ini dan jangan di-insert
                }

                $status = $request->status ?? 'draft';
                $opdNumber = null;
                $opdDate = null;
                $opdAuthorizedBy = null;
                $spdNumber = null;
                $spdDate = null;
                $spdDisbursedBy = null;

                if (in_array($status, ['authorized', 'disbursed'])) {
                    $opdNumber = $noSpp;
                    $opdDate = $tanggalSpp;
                    $opdAuthorizedBy = $request->kpa_id;
                }
                if ($status === 'disbursed') {
                    $spdNumber = $noSpp;
                    $spdDate = $tanggalSpp;
                    $spdDisbursedBy = $request->treasurer_id;
                }

                // 5. Insert Expenditure (SPPD)
                $expenditure = Expenditure::create([
                    'document_number' => $noSpp,
                    'date' => $tanggalSpp,
                    'type' => 'LS_Barang_Jasa_Modal', // Default fallback
                    'description' => trim($worksheet->getCell('N' . $row)->getValue() ?? 'Import Excel'),
                    'treasurer_id' => $request->treasurer_id,
                    'kpa_id' => $request->kpa_id,
                    'ptk_id' => $ptkId,
                    'activity_date' => $tanggalSpp, // Asumsi sama
                    'activity_description' => trim($worksheet->getCell('N' . $row)->getValue() ?? 'Import Excel'),
                    'payment_method' => 'rekanan', // Default
                    'vendor_id' => $vendorId,
                    'bank_name' => trim($worksheet->getCell('K' . $row)->getValue() ?? null),
                    'bank_account_number' => trim($worksheet->getCell('L' . $row)->getValue() ?? null),
                    'contract_number' => trim($worksheet->getCell('I' . $row)->getValue() ?? null),
                    'status' => $status,
                    'opd_number' => $opdNumber,
                    'opd_date' => $opdDate,
                    'opd_authorized_by' => $opdAuthorizedBy,
                    'spd_number' => $spdNumber,
                    'spd_date' => $spdDate,
                    'spd_disbursed_by' => $spdDisbursedBy,
                    'created_by' => auth()->id(),
                ]);

                // 6. Insert Expenditure Details
                foreach ($detailsToInsert as $detail) {
                    $expenditure->details()->create($detail);
                }

                // 7. Proses Pajak (AN = 40)
                $taxMappings = [
                    ['col_amount' => 'AN', 'col_billing' => 'AO', 'type' => 'PPN'],
                    ['col_amount' => 'AP', 'col_billing' => 'AQ', 'type' => 'PPh 21'],
                    ['col_amount' => 'AR', 'col_billing' => 'AS', 'type' => 'PPh 22'],
                    ['col_amount' => 'AT', 'col_billing' => 'AU', 'type' => 'PPh 23'],
                    ['col_amount' => 'AV', 'col_billing' => 'AW', 'type' => 'PPh Final'],
                ];

                foreach ($taxMappings as $taxMap) {
                    $taxAmount = (float)($worksheet->getCell($taxMap['col_amount'] . $row)->getCalculatedValue() ?? 0);
                    $taxBilling = trim($worksheet->getCell($taxMap['col_billing'] . $row)->getValue() ?? '');
                    
                    if ($taxAmount > 0) {
                        $expenditure->taxes()->create([
                            'tax_type' => $taxMap['type'],
                            'billing_code' => $taxBilling,
                            'amount' => $taxAmount
                        ]);
                    }
                }
                
                $stats['success']++;
            }

            DB::commit();

            return redirect()->back()->with([
                'message' => "Import selesai. Berhasil: {$stats['success']}, Dilewati: {$stats['skipped']}.",
                'skipped_reasons' => $stats['skipped_reasons']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel Import Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['file' => 'Terjadi kesalahan saat membaca baris ' . $row . ': ' . $e->getMessage()]);
        }
    }
}
