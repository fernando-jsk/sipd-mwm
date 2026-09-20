<?php

namespace App\Http\Controllers;

use App\Models\Expenditure;
use App\Models\ExpenditureDetail;
use App\Models\ExpenditureTax;
use App\Models\FundingSource;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\RbaDetail;
use App\Models\RbaDocument;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::all()->keyBy('key');
        
        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        
        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        
        // Get all unique versions for the active year
        $availableVersions = \App\Models\RbaDocument::where('budget_year', $budgetYear)
            ->select('version', 'version_name')
            ->distinct()
            ->orderBy('version')
            ->get();
        
        // If empty, mock the default 'Induk'
        if ($availableVersions->isEmpty()) {
            $availableVersions = collect([
                (object) ['version' => 0, 'version_name' => 'Induk']
            ]);
        }

        $fundingSources = \App\Models\FundingSource::orderBy('name')->get();

        // Ambil akun kas aktif (1.1%) untuk dropdown sumber kas dan kas bendahara
        $cashAccounts = \App\Models\AccountCode::where('is_active', true)
            ->where('code', 'like', '1.1%')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'level']);

        // Ambil aturan jurnal pengeluaran (parsed JSON)
        $expenditureRulesSetting = $settings->get('expenditure_journal_rules');
        $expenditureRules = $expenditureRulesSetting && $expenditureRulesSetting->value 
            ? json_decode($expenditureRulesSetting->value, true) 
            : [];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'activeVersion' => $activeVersion,
            'availableVersions' => $availableVersions,
            'fundingSources' => $fundingSources,
            'cashAccounts' => $cashAccounts,
            'expenditureRules' => $expenditureRules
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string'
        ]);

        foreach ($validated['settings'] as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                ['value' => $settingData['value']]
            );
        }

        activity('setting')
            ->log('Memperbarui pengaturan sistem');

        return redirect()->back()->with('message', 'Pengaturan berhasil disimpan');
    }

    public function setBudgetYear(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|string|size:4'
        ]);

        $request->session()->put('active_budget_year', $validated['year']);

        return redirect()->back()->with('message', "Tahun Anggaran diubah ke {$validated['year']}");
    }

    public function buatReplikasi(Request $request)
    {
        $validated = $request->validate([
            'source_version' => 'required|integer',
            'version_name' => 'required|string|max:255'
        ]);

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));

        \Illuminate\Support\Facades\DB::transaction(function () use ($budgetYear, $validated) {
            $maxVersion = \App\Models\RbaDocument::where('budget_year', $budgetYear)->max('version') ?? 0;
            $newVersion = $maxVersion + 1;

            $documentsToDuplicate = \App\Models\RbaDocument::where('budget_year', $budgetYear)
                ->where('version', $validated['source_version'])
                ->get();

            foreach ($documentsToDuplicate as $doc) {
                $newDoc = $doc->replicate();
                $newDoc->version = $newVersion;
                $newDoc->version_name = $validated['version_name'];
                $newDoc->status = 'draft';
                $newDoc->save();

                $oldDetails = \App\Models\RbaDetail::where('rba_document_id', $doc->id)
                    ->orderBy('id', 'asc')
                    ->get();
                
                $idMap = [];

                foreach ($oldDetails as $oldDetail) {
                    $newDetail = $oldDetail->replicate();
                    $newDetail->rba_document_id = $newDoc->id;
                    
                    if ($oldDetail->parent_id && isset($idMap[$oldDetail->parent_id])) {
                        $newDetail->parent_id = $idMap[$oldDetail->parent_id];
                    }
                    
                    $newDetail->save();
                    $idMap[$oldDetail->id] = $newDetail->id;
                }
            }
        });

        activity('setting')
            ->log("Membuat replikasi RBA '{$validated['version_name']}' untuk tahun {$budgetYear}");

        return redirect()->back()->with('message', 'Replikasi RBA berhasil dibuat.');
    }

    public function setActiveVersion(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|integer'
        ]);

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        
        Setting::updateOrCreate(
            ['key' => "rba_active_version_{$budgetYear}"],
            ['value' => (string) $validated['version']]
        );

        activity('setting')
            ->log("Mengubah tahapan aktif RBA tahun {$budgetYear} ke versi {$validated['version']}");

        return redirect()->back()->with('message', 'Tahapan RBA aktif berhasil diubah.');
    }

    public function destroyVersion(Request $request, $version)
    {
        $version = (int) $version;
        if ($version === 0) {
            return redirect()->back()->with('error', 'Versi Induk (0) tidak boleh dihapus.');
        }

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));

        \App\Models\RbaDocument::where('budget_year', $budgetYear)
            ->where('version', $version)
            ->delete(); // Cascade will delete rba_details

        // If the active version was deleted, reset active version to 0 or highest remaining
        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        
        if ($activeVersion === $version) {
            $highestRemaining = \App\Models\RbaDocument::where('budget_year', $budgetYear)->max('version') ?? 0;
            Setting::updateOrCreate(
                ['key' => "rba_active_version_{$budgetYear}"],
                ['value' => (string) $highestRemaining]
            );
        }

        activity('setting')
            ->log("Menghapus permanen RBA versi {$version} tahun anggaran {$budgetYear}");

        return redirect()->back()->with('message', 'Versi RBA berhasil dihapus secara permanen.');
    }

    public function storeFundingSource(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        \App\Models\FundingSource::create($validated);

        activity('setting')->log('Menambahkan master data Sumber Dana baru: ' . $validated['name']);

        return redirect()->back()->with('message', 'Sumber Dana berhasil ditambahkan.');
    }

    public function updateFundingSource(Request $request, \App\Models\FundingSource $fundingSource)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $fundingSource->update($validated);

        activity('setting')->log('Memperbarui master data Sumber Dana: ' . $validated['name']);

        return redirect()->back()->with('message', 'Sumber Dana berhasil diperbarui.');
    }

    public function destroyFundingSource(\App\Models\FundingSource $fundingSource)
    {
        // Check if used in RbaDocuments or AccountCodes
        if ($fundingSource->rbaDocuments()->exists() || $fundingSource->accountCodes()->exists()) {
            return redirect()->back()->with('error', 'Sumber Dana tidak dapat dihapus karena sudah digunakan dalam transaksi atau master data kode rekening.');
        }

        $name = $fundingSource->name;
        $fundingSource->delete();

        activity('setting')->log('Menghapus master data Sumber Dana: ' . $name);

        return redirect()->back()->with('message', 'Sumber Dana berhasil dihapus.');
    }

    public function clearPreview(Request $request)
    {
        $request->validate([
            'type' => 'required|in:expenditure,receipt',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $query = $request->type === 'expenditure'
            ? Expenditure::query()
            : Receipt::query();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $count = $query->count();
        $total = $request->type === 'expenditure'
            ? Expenditure::count()
            : Receipt::count();

        return response()->json([
            'count' => $count,
            'total' => $total,
            'is_filtered' => $request->filled('start_date') || $request->filled('end_date'),
        ]);
    }

    public function clearExpenditures(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->with('error', 'Password tidak valid. Operasi dibatalkan.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $query = Expenditure::query();
            $isFiltered = false;

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
                $isFiltered = true;
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
                $isFiltered = true;
            }

            if ($isFiltered) {
                $expenditures = $query->get(['id', 'attachment_path']);
                $count = $expenditures->count();

                if ($count === 0) {
                    return redirect()->back()->with('message', 'Tidak ada data transaksi pengeluaran pada rentang tanggal yang dipilih.');
                }

                $expenditureIds = $expenditures->pluck('id')->toArray();

                // Hapus file lampiran jika ada
                $attachmentPaths = $expenditures->whereNotNull('attachment_path')->pluck('attachment_path')->toArray();
                if (!empty($attachmentPaths)) {
                    Storage::disk('public')->delete($attachmentPaths);
                }

                // Hapus jurnal terkait pengeluaran beserta rinciannya
                $journalIds = Journal::where('journalable_type', Expenditure::class)
                    ->whereIn('journalable_id', $expenditureIds)
                    ->pluck('id')
                    ->toArray();

                if (!empty($journalIds)) {
                    JournalDetail::whereIn('journal_id', $journalIds)->delete();
                    Journal::whereIn('id', $journalIds)->delete();
                }

                ExpenditureTax::whereIn('expenditure_id', $expenditureIds)->delete();
                ExpenditureDetail::whereIn('expenditure_id', $expenditureIds)->delete();
                Expenditure::whereIn('id', $expenditureIds)->delete();

                $dateText = $this->formatDateRangeText($request->start_date, $request->end_date);

                activity('setting')
                    ->log("Menghapus data pengeluaran (SPPD, OPD, SPD) {$dateText} ({$count} data dihapus) beserta rincian dan jurnal terkait");

                return redirect()->back()->with('message', "Sebanyak {$count} data pengeluaran {$dateText} dan jurnal terkait berhasil dibersihkan.");
            } else {
                // Hapus seluruh file lampiran jika ada
                $attachmentPaths = Expenditure::whereNotNull('attachment_path')->pluck('attachment_path')->toArray();
                if (!empty($attachmentPaths)) {
                    Storage::disk('public')->delete($attachmentPaths);
                }

                // Hapus jurnal terkait pengeluaran beserta rinciannya
                JournalDetail::whereIn('journal_id', function ($query) {
                    $query->select('id')
                        ->from('journals')
                        ->where('journalable_type', Expenditure::class);
                })->delete();

                Journal::where('journalable_type', Expenditure::class)->delete();

                Expenditure::truncate();
                ExpenditureDetail::truncate();
                ExpenditureTax::truncate();

                activity('setting')
                    ->log("Menghapus permanen seluruh data pengeluaran (SPPD, OPD, SPD) beserta rincian dan jurnal terkait");

                return redirect()->back()->with('message', 'Seluruh data pengeluaran dan jurnal terkait berhasil dibersihkan.');
            }
        } catch (\Exception $e) {
            Log::error("Failed to clear expenditures: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function clearReceipts(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->with('error', 'Password tidak valid. Operasi dibatalkan.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $query = Receipt::query();
            $isFiltered = false;

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
                $isFiltered = true;
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
                $isFiltered = true;
            }

            if ($isFiltered) {
                $receipts = $query->get(['id', 'attachment_path']);
                $count = $receipts->count();

                if ($count === 0) {
                    return redirect()->back()->with('message', 'Tidak ada data transaksi penerimaan pada rentang tanggal yang dipilih.');
                }

                $receiptIds = $receipts->pluck('id')->toArray();

                // Hapus file lampiran jika ada
                $attachmentPaths = $receipts->whereNotNull('attachment_path')->pluck('attachment_path')->toArray();
                if (!empty($attachmentPaths)) {
                    Storage::disk('public')->delete($attachmentPaths);
                }

                // Hapus jurnal terkait penerimaan beserta rinciannya
                $journalIds = Journal::where('journalable_type', Receipt::class)
                    ->whereIn('journalable_id', $receiptIds)
                    ->pluck('id')
                    ->toArray();

                if (!empty($journalIds)) {
                    JournalDetail::whereIn('journal_id', $journalIds)->delete();
                    Journal::whereIn('id', $journalIds)->delete();
                }

                ReceiptDetail::whereIn('receipt_id', $receiptIds)->delete();
                Receipt::whereIn('id', $receiptIds)->delete();

                $dateText = $this->formatDateRangeText($request->start_date, $request->end_date);

                activity('setting')
                    ->log("Menghapus data penerimaan (TBP/STS) {$dateText} ({$count} data dihapus) beserta rincian dan jurnal terkait");

                return redirect()->back()->with('message', "Sebanyak {$count} data penerimaan {$dateText} dan jurnal terkait berhasil dibersihkan.");
            } else {
                // Hapus file lampiran jika ada
                $attachmentPaths = Receipt::whereNotNull('attachment_path')->pluck('attachment_path')->toArray();
                if (!empty($attachmentPaths)) {
                    Storage::disk('public')->delete($attachmentPaths);
                }

                // Hapus jurnal terkait penerimaan beserta rinciannya
                JournalDetail::whereIn('journal_id', function ($query) {
                    $query->select('id')
                        ->from('journals')
                        ->where('journalable_type', Receipt::class);
                })->delete();

                Journal::where('journalable_type', Receipt::class)->delete();

                Receipt::truncate();
                ReceiptDetail::truncate();

                activity('setting')
                    ->log("Menghapus permanen seluruh data penerimaan (TBP/STS) beserta rincian dan jurnal terkait");

                return redirect()->back()->with('message', 'Seluruh data penerimaan dan jurnal terkait berhasil dibersihkan.');
            }
        } catch (\Exception $e) {
            Log::error("Failed to clear receipts: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function formatDateRangeText(?string $startDate, ?string $endDate): string
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->format('d/m/Y');
            $end = Carbon::parse($endDate)->format('d/m/Y');
            return "periode {$start} s/d {$end}";
        } elseif ($startDate) {
            $start = Carbon::parse($startDate)->format('d/m/Y');
            return "mulai tanggal {$start}";
        } elseif ($endDate) {
            $end = Carbon::parse($endDate)->format('d/m/Y');
            return "sampai dengan tanggal {$end}";
        }

        return "seluruh periode";
    }
}
