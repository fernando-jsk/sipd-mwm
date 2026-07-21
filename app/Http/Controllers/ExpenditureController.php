<?php

namespace App\Http\Controllers;

use App\Models\Expenditure;
use App\Models\ExpenditureDetail;
use App\Models\AccountCode;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Setting;
use App\Models\RbaDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Facades\LogBatch;

class ExpenditureController extends Controller
{
    public function index(Request $request)
    {
        return $this->sppdIndex($request);
    }

    public function sppdIndex(Request $request)
    {
        $query = Expenditure::with(['vendor', 'treasurer', 'kpa', 'ptk', 'createdBy']);
        $query = $this->applyFiltersAndSort($query, $request, 'created_at');

        $expenditures = $query->paginate(20)->withQueryString();

        return Inertia::render('Expenditures/SppdIndex', [
            'expenditures' => $expenditures,
            'filters' => (object) $request->only('search', 'status', 'sort')
        ]);
    }

    public function opdIndex(Request $request)
    {
        // Khusus Direktur: Menampilkan SPPD yang diajukan (submitted) atau sudah diotorisasi OPD
        $query = Expenditure::with(['vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'opdAuthorizedBy'])
            ->whereIn('status', ['submitted', 'sppd_submitted', 'authorized', 'opd_authorized', 'disbursed', 'spd_disbursed', 'rejected']);
        
        $query = $this->applyFiltersAndSort($query, $request, 'updated_at');

        $expenditures = $query->paginate(20)->withQueryString();

        return Inertia::render('Expenditures/OpdIndex', [
            'expenditures' => $expenditures,
            'filters' => (object) $request->only('search', 'status', 'sort')
        ]);
    }

    public function spdIndex(Request $request)
    {
        // Khusus Kabag Keuangan: Menampilkan OPD yang diotorisasi (authorized) atau sudah dicairkan SPD
        $query = Expenditure::with(['vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'opdAuthorizedBy', 'spdDisbursedBy'])
            ->whereIn('status', ['authorized', 'opd_authorized', 'disbursed', 'spd_disbursed']);
        
        $query = $this->applyFiltersAndSort($query, $request, 'updated_at');

        $expenditures = $query->paginate(20)->withQueryString();

        return Inertia::render('Expenditures/SpdIndex', [
            'expenditures' => $expenditures,
            'filters' => (object) $request->only('search', 'status', 'sort')
        ]);
    }

    public function create(Request $request)
    {
        $users = User::all(['id', 'name']);
        $vendors = Vendor::all(['id', 'name', 'bank_name', 'bank_account_number']);
        
        return Inertia::render('Expenditures/CreateEdit', [
            'users' => $users,
            'vendors' => $vendors,
            'accountCodes' => $this->getAccountCodesWithBudgetUsage($request)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|string|unique:expenditures,document_number',
            'date' => 'required|date',
            'type' => 'required|in:UP,GU,TU,LS,LS_Pegawai,LS_Barang_Jasa_Modal',
            'description' => 'required|string',
            'treasurer_id' => 'required|exists:users,id',
            'kpa_id' => 'required|exists:users,id',
            'ptk_id' => 'required|exists:users,id',
            'activity_date' => 'required|date',
            'activity_description' => 'required|string',
            'payment_method' => 'required|in:rekanan,pegawai,ls_bendahara,terlampir',
            'vendor_id' => 'nullable|exists:vendors,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'contract_number' => 'nullable|string',
            'status' => 'required|in:draft,submitted,sppd_submitted',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'details' => 'required|array|min:1',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.amount' => 'required|numeric|min:0.01',
            'taxes' => 'nullable|array',
            'taxes.*.tax_type' => 'required|in:PPN,PPh 21,PPh 22,PPh 23,PPh Final',
            'taxes.*.billing_code' => 'nullable|string',
            'taxes.*.amount' => 'required|numeric|min:0.01',
        ]);

        $this->validateBudgetLimit($request, $validated['details']);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('expenditures', 'public');
            $validated['attachment_path'] = $path;
        }

        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $expenditure = Expenditure::create($validated);

            foreach ($validated['details'] as $detail) {
                $expenditure->details()->create([
                    'account_code_id' => $detail['account_code_id'],
                    'amount' => $detail['amount']
                ]);
            }
            
            if (!empty($validated['taxes'])) {
                foreach ($validated['taxes'] as $tax) {
                    $expenditure->taxes()->create([
                        'tax_type' => $tax['tax_type'],
                        'billing_code' => $tax['billing_code'] ?? null,
                        'amount' => $tax['amount']
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('expenditures.sppd')->with('message', 'Dokumen SPPD berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Expenditure $expenditure)
    {
        $expenditure->load(['details.accountCode', 'vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'opdAuthorizedBy', 'spdDisbursedBy', 'taxes']);
        
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Expenditure::class)
            ->where('subject_id', $expenditure->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Expenditures/Show', [
            'expenditure' => $expenditure,
            'activities' => $activities
        ]);
    }

    public function edit(Request $request, Expenditure $expenditure)
    {
        if ($expenditure->status !== 'draft' && $expenditure->status !== 'rejected') {
            return redirect()->route('expenditures.sppd')->with('error', 'Hanya dokumen Draft atau Ditolak yang dapat diedit.');
        }

        $expenditure->load('details', 'taxes');
        
        $users = User::all(['id', 'name']);
        $vendors = Vendor::all(['id', 'name', 'bank_name', 'bank_account_number']);
        
        return Inertia::render('Expenditures/CreateEdit', [
            'expenditure' => $expenditure,
            'users' => $users,
            'vendors' => $vendors,
            'accountCodes' => $this->getAccountCodesWithBudgetUsage($request, $expenditure->id)
        ]);
    }

    public function update(Request $request, Expenditure $expenditure)
    {
        if ($expenditure->status !== 'draft' && $expenditure->status !== 'rejected') {
            return redirect()->route('expenditures.sppd')->with('error', 'Hanya dokumen Draft atau Ditolak yang dapat diedit.');
        }

        $validated = $request->validate([
            'document_number' => 'required|string|unique:expenditures,document_number,' . $expenditure->id,
            'date' => 'required|date',
            'type' => 'required|in:UP,GU,TU,LS,LS_Pegawai,LS_Barang_Jasa_Modal',
            'description' => 'required|string',
            'treasurer_id' => 'required|exists:users,id',
            'kpa_id' => 'required|exists:users,id',
            'ptk_id' => 'required|exists:users,id',
            'activity_date' => 'required|date',
            'activity_description' => 'required|string',
            'payment_method' => 'required|in:rekanan,pegawai,ls_bendahara,terlampir',
            'vendor_id' => 'nullable|exists:vendors,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'contract_number' => 'nullable|string',
            'status' => 'required|in:draft,submitted,sppd_submitted',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'details' => 'required|array|min:1',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.amount' => 'required|numeric|min:0.01',
            'taxes' => 'nullable|array',
            'taxes.*.tax_type' => 'required|in:PPN,PPh 21,PPh 22,PPh 23,PPh Final',
            'taxes.*.billing_code' => 'nullable|string',
            'taxes.*.amount' => 'required|numeric|min:0.01',
        ]);

        $this->validateBudgetLimit($request, $validated['details'], $expenditure->id);

        if ($request->hasFile('attachment')) {
            if ($expenditure->attachment_path) {
                \Storage::disk('public')->delete($expenditure->attachment_path);
            }
            $path = $request->file('attachment')->store('expenditures', 'public');
            $validated['attachment_path'] = $path;
        }

        DB::beginTransaction();
        try {
            $expenditure->update($validated);
            
            // Delete old details
            $expenditure->details()->delete();
            $expenditure->taxes()->delete();

            // Insert new details
            foreach ($validated['details'] as $detail) {
                $expenditure->details()->create([
                    'account_code_id' => $detail['account_code_id'],
                    'amount' => $detail['amount']
                ]);
            }
            
            if (!empty($validated['taxes'])) {
                foreach ($validated['taxes'] as $tax) {
                    $expenditure->taxes()->create([
                        'tax_type' => $tax['tax_type'],
                        'billing_code' => $tax['billing_code'] ?? null,
                        'amount' => $tax['amount']
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('expenditures.sppd')->with('message', 'Dokumen SPPD berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Expenditure $expenditure)
    {
        if ($expenditure->status !== 'draft') {
            return redirect()->route('expenditures.sppd')->with('error', 'Hanya dokumen Draft yang dapat dihapus.');
        }

        if ($expenditure->attachment_path) {
            \Storage::disk('public')->delete($expenditure->attachment_path);
        }

        $expenditure->delete();

        return redirect()->route('expenditures.sppd')->with('message', 'Dokumen SPPD berhasil dihapus.');
    }

    public function updateStatus(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,sppd_submitted,authorized,opd_authorized,verified,disbursed,spd_disbursed,rejected',
            'rejection_note' => 'required_if:status,rejected|nullable|string',
            'opd_number' => 'required_if:status,authorized|required_if:status,opd_authorized|nullable|string',
            'opd_notes' => 'nullable|string',
            'spd_number' => 'required_if:status,disbursed|required_if:status,spd_disbursed|nullable|string'
        ]);

        $updateData = [
            'status' => $validated['status']
        ];

        // 1. Pengajuan SPPD oleh Bendahara
        if ($validated['status'] === 'submitted' || $validated['status'] === 'sppd_submitted') {
            $updateData['status'] = 'submitted';
        }

        // 2. Otorisasi OPD oleh Direktur
        if ($validated['status'] === 'authorized' || $validated['status'] === 'opd_authorized') {
            $updateData['status'] = 'authorized';
            $updateData['opd_number'] = $validated['opd_number'];
            $updateData['opd_date'] = now();
            $updateData['opd_notes'] = $validated['opd_notes'] ?? null;
            $updateData['opd_authorized_by'] = auth()->id();
        }

        // 3. Verifikasi & Pencairan SPD oleh Kabag Keuangan
        if ($validated['status'] === 'disbursed' || $validated['status'] === 'spd_disbursed') {
            $updateData['status'] = 'disbursed';
            $updateData['spd_number'] = $validated['spd_number'];
            $updateData['spd_date'] = now();
            $updateData['spd_disbursed_by'] = auth()->id();
        }

        // 4. Penolakan
        if ($validated['status'] === 'rejected') {
            $updateData['status'] = 'rejected';
            $updateData['rejection_note'] = $validated['rejection_note'];
        }

        $expenditure->update($updateData);

        return redirect()->back()->with('message', 'Status dokumen berhasil diperbarui.');
    }

    public function printSppd(Expenditure $expenditure)
    {
        $expenditure->load(['details.accountCode', 'vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'taxes']);
        return Inertia::render('Expenditures/PrintSppd', [
            'expenditure' => $expenditure
        ]);
    }

    public function printOpd(Expenditure $expenditure)
    {
        if ($expenditure->status === 'draft' || $expenditure->status === 'submitted') {
            return redirect()->back()->with('error', 'Dokumen OPD belum diotorisasi.');
        }

        $expenditure->load(['details.accountCode', 'vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'opdAuthorizedBy', 'taxes']);
        return Inertia::render('Expenditures/PrintOpd', [
            'expenditure' => $expenditure
        ]);
    }

    public function printSpd(Expenditure $expenditure)
    {
        if ($expenditure->status !== 'disbursed') {
            return redirect()->back()->with('error', 'Dokumen SPD belum diverifikasi / dicairkan.');
        }

        $expenditure->load(['details.accountCode', 'vendor', 'treasurer', 'kpa', 'ptk', 'createdBy', 'opdAuthorizedBy', 'spdDisbursedBy', 'taxes']);
        return Inertia::render('Expenditures/PrintSpd', [
            'expenditure' => $expenditure
        ]);
    }

    private function applyFiltersAndSort($query, Request $request, $sortColumn = 'updated_at')
    {
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('document_number', 'like', '%' . $request->search . '%')
                  ->orWhere('opd_number', 'like', '%' . $request->search . '%')
                  ->orWhere('spd_number', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy($sortColumn, 'asc');
        } else {
            $query->orderBy($sortColumn, 'desc');
        }

        return $query;
    }

    private function getAccountCodesWithBudgetUsage(Request $request, $excludeExpenditureId = null)
    {
        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        $activeVersion = Setting::where('key', 'rba_active_version')->value('value') ?? 0;

        $rbaDocs = RbaDocument::with('accountCode')
            ->where('budget_year', $budgetYear)
            ->where('version', $activeVersion)
            ->get();
            
        // Hitung pemakaian pagu
        $usageQuery = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->whereYear('expenditures.date', $budgetYear)
            ->where('expenditures.status', '!=', 'rejected');

        if ($excludeExpenditureId) {
            $usageQuery->where('expenditures.id', '!=', $excludeExpenditureId);
        }

        $usageResults = $usageQuery->select('expenditure_details.account_code_id', 'expenditures.status', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('expenditure_details.account_code_id', 'expenditures.status')
            ->get();

        $budgetUsage = [];
        foreach ($usageResults as $usage) {
            $accId = $usage->account_code_id;
            if (!isset($budgetUsage[$accId])) {
                $budgetUsage[$accId] = ['submitted' => 0, 'disbursed' => 0];
            }
            if (in_array($usage->status, ['disbursed', 'spd_disbursed'])) {
                $budgetUsage[$accId]['disbursed'] += $usage->total;
            } else {
                $budgetUsage[$accId]['submitted'] += $usage->total;
            }
        }

        $accountCodes = [];
        foreach ($rbaDocs as $doc) {
            if ($doc->accountCode) {
                $accId = $doc->accountCode->id;
                $submitted = $budgetUsage[$accId]['submitted'] ?? 0;
                $disbursed = $budgetUsage[$accId]['disbursed'] ?? 0;
                $usedAmount = $submitted + $disbursed;
                
                $accountCodes[] = [
                    'id' => $accId,
                    'code' => $doc->accountCode->code,
                    'name' => $doc->accountCode->name,
                    'funding_source_id' => $doc->funding_source_id,
                    'total_budget' => $doc->total_budget,
                    'submitted_amount' => $submitted,
                    'disbursed_amount' => $disbursed,
                    'used_amount' => $usedAmount,
                    'remaining_budget' => $doc->total_budget - $usedAmount
                ];
            }
        }

        return collect($accountCodes)->sortBy('code')->values()->all();
    }

    private function validateBudgetLimit(Request $request, array $details, $excludeExpenditureId = null)
    {
        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        $activeVersion = Setting::where('key', 'rba_active_version')->value('value') ?? 0;
        
        $validationMode = Setting::where('key', 'budget_validation_mode')->value('value') ?? 'warning';

        // Group amounts by account_code_id
        $requestedAmounts = [];
        foreach ($details as $detail) {
            $accId = $detail['account_code_id'];
            if (!isset($requestedAmounts[$accId])) {
                $requestedAmounts[$accId] = 0;
            }
            $requestedAmounts[$accId] += $detail['amount'];
        }

        foreach ($requestedAmounts as $accId => $amount) {
            $rbaDoc = RbaDocument::where('budget_year', $budgetYear)
                ->where('version', $activeVersion)
                ->where('account_code_id', $accId)
                ->first();

            if (!$rbaDoc) {
                throw ValidationException::withMessages([
                    'details' => "Akun anggaran tidak ditemukan pada RBA aktif."
                ]);
            }

            $usedAmountQuery = ExpenditureDetail::where('account_code_id', $accId)
                ->whereHas('expenditure', function($q) use ($budgetYear, $excludeExpenditureId) {
                    $q->whereYear('date', $budgetYear)
                      ->where('status', '!=', 'rejected');
                    if ($excludeExpenditureId) {
                        $q->where('id', '!=', $excludeExpenditureId);
                    }
                });
                
            $usedAmount = $usedAmountQuery->sum('amount');

            if (($usedAmount + $amount) > $rbaDoc->total_budget) {
                if ($validationMode === 'strict') {
                    throw ValidationException::withMessages([
                        'details' => "Total pengeluaran melebihi sisa pagu untuk salah satu akun anggaran. Mode validasi: Strict."
                    ]);
                }
                // Jika mode warning, biarkan lolos, tapi kita bisa tambahkan flash session warning di frontend nantinya
                session()->flash('warning', 'Peringatan: Beberapa pengeluaran melebihi sisa pagu anggaran.');
            }
        }
    }
}
