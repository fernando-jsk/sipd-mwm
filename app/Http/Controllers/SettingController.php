<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
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

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'activeVersion' => $activeVersion,
            'availableVersions' => $availableVersions,
            'fundingSources' => $fundingSources
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
            Setting::where('key', $settingData['key'])->update(['value' => $settingData['value']]);
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

    public function clearExpenditures(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->with('error', 'Password tidak valid. Operasi dibatalkan.');
        }

        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\Expenditure::truncate();
            \App\Models\ExpenditureDetail::truncate();
            \App\Models\ExpenditureTax::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            activity('setting')
                ->log("Menghapus permanen seluruh data pengeluaran (SPPD, OPD, SPD) beserta rinciannya");

            return redirect()->back()->with('message', 'Seluruh data pengeluaran berhasil dibersihkan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            \Illuminate\Support\Facades\Log::error("Failed to clear expenditures: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        }
    }

    public function clearReceipts(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->with('error', 'Password tidak valid. Operasi dibatalkan.');
        }

        try {
            // Hapus file lampiran jika ada
            $attachmentPaths = \App\Models\Receipt::whereNotNull('attachment_path')->pluck('attachment_path')->toArray();
            if (!empty($attachmentPaths)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attachmentPaths);
            }

            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\Receipt::truncate();
            \App\Models\ReceiptDetail::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            activity('setting')
                ->log("Menghapus permanen seluruh data penerimaan (TBP/STS) beserta rinciannya");

            return redirect()->back()->with('message', 'Seluruh data penerimaan berhasil dibersihkan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            \Illuminate\Support\Facades\Log::error("Failed to clear receipts: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        }
    }
}
