<?php

namespace App\Http\Controllers;

use App\Models\ExpenditureReceipt;
use App\Models\AccountCode;
use App\Models\RbaDocument;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExpenditureReceiptController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = $request->session()->get('active_budget_year', date('Y'));
        $selectedYear = $request->input('year', $activeYear);
        $selectedMonth = $request->input('month', 'all');

        $query = ExpenditureReceipt::with(['accountCode', 'createdBy', 'expenditure'])
            ->whereYear('date', $selectedYear);

        if ($selectedMonth && $selectedMonth !== 'all') {
            $query->whereMonth('date', $selectedMonth);
        }

        // Filter status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'draft') {
                $query->where('status', 'draft');
            } elseif ($request->status === 'cair' || $request->status === 'ready' || $request->status === 'paid') {
                $query->where('status', 'paid')->whereNull('expenditure_id');
            } elseif ($request->status === 'in_gu' || $request->status === 'proses_gu') {
                $query->where(function ($q) {
                    $q->where('status', 'in_gu')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('expenditure_id')
                              ->where('status', '!=', 'completed');
                      });
                });
            } elseif ($request->status === 'completed' || $request->status === 'sudah_gu') {
                $query->where('status', 'completed');
            } else {
                $query->where('status', $request->status);
            }
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', $search)
                  ->orWhere('recipient_name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('billing_code', 'like', $search)
                  ->orWhereHas('accountCode', function ($aq) use ($search) {
                      $aq->where('code', 'like', $search)
                         ->orWhere('name', 'like', $search);
                  });
            });
        }

        // Urutan / Sort
        $sort = $request->input('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $query->orderBy('date', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'number_asc') {
            $query->orderBy('receipt_number', 'asc');
        } elseif ($sort === 'number_desc') {
            $query->orderBy('receipt_number', 'desc');
        } else {
            $query->orderBy('date', 'desc')->orderBy('id', 'desc');
        }

        $receipts = $query->paginate(20)->withQueryString();

        // Hitung Ringkasan Statistik
        $statsQuery = ExpenditureReceipt::whereYear('date', $selectedYear);
        if ($selectedMonth && $selectedMonth !== 'all') {
            $statsQuery->whereMonth('date', $selectedMonth);
        }

        $totalMonth = (float) (clone $statsQuery)->sum('amount');
        $totalDraft = (float) ExpenditureReceipt::whereYear('date', $selectedYear)
            ->where('status', 'draft')
            ->when($selectedMonth && $selectedMonth !== 'all', fn($q) => $q->whereMonth('date', $selectedMonth))
            ->sum('amount');
        $totalCair = (float) ExpenditureReceipt::whereYear('date', $selectedYear)
            ->where('status', 'paid')
            ->whereNull('expenditure_id')
            ->when($selectedMonth && $selectedMonth !== 'all', fn($q) => $q->whereMonth('date', $selectedMonth))
            ->sum('amount');
        $totalInGu = (float) ExpenditureReceipt::whereYear('date', $selectedYear)
            ->where(function ($q) {
                $q->where('status', 'in_gu')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('expenditure_id')
                          ->where('status', '!=', 'completed');
                  });
            })
            ->when($selectedMonth && $selectedMonth !== 'all', fn($q) => $q->whereMonth('date', $selectedMonth))
            ->sum('amount');
        $totalCompleted = (float) ExpenditureReceipt::whereYear('date', $selectedYear)
            ->where('status', 'completed')
            ->when($selectedMonth && $selectedMonth !== 'all', fn($q) => $q->whereMonth('date', $selectedMonth))
            ->sum('amount');

        // Ambil akun-akun Belanja RBA aktif (5.x)
        $accountCodes = $this->getBelanjaAccountCodes($selectedYear);

        return Inertia::render('ExpenditureReceipts/Index', [
            'receipts' => $receipts,
            'filters' => [
                'year' => (string) $selectedYear,
                'month' => (string) $selectedMonth,
                'status' => $request->input('status', 'all'),
                'search' => $request->input('search', ''),
                'sort' => $sort,
            ],
            'stats' => [
                'total_month' => $totalMonth,
                'total_draft' => $totalDraft,
                'total_cair' => $totalCair,
                'total_ready' => $totalCair,
                'total_in_gu' => $totalInGu,
                'total_completed' => $totalCompleted,
            ],
            'accountCodes' => $accountCodes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receipt_number' => 'required|string|max:100|unique:expenditure_receipts,receipt_number',
            'date' => 'required|date',
            'account_code_id' => 'required|exists:account_codes,id',
            'recipient_name' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'tax_type' => 'nullable|in:PPN,PPh 21,PPh 22,PPh 23,PPh Final',
            'tax_amount' => 'nullable|numeric|min:0',
            'billing_code' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,paid,in_gu,completed',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('receipts_spj', 'public');
            $validated['attachment_path'] = $path;
        }

        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'paid';
        $validated['created_by'] = auth()->id() ?? 1;

        ExpenditureReceipt::create($validated);

        return redirect()->back()->with('message', 'Kwitansi belanja kas UP berhasil dicatat.');
    }

    public function update(Request $request, ExpenditureReceipt $expenditureReceipt)
    {
        if ($expenditureReceipt->status === 'completed' || $expenditureReceipt->expenditure_id !== null) {
            return redirect()->back()->with('error', 'Kwitansi yang sudah masuk dalam dokumen SPP-GU tidak dapat diedit secara langsung.');
        }

        $validated = $request->validate([
            'receipt_number' => 'required|string|max:100|unique:expenditure_receipts,receipt_number,' . $expenditureReceipt->id,
            'date' => 'required|date',
            'account_code_id' => 'required|exists:account_codes,id',
            'recipient_name' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'tax_type' => 'nullable|in:PPN,PPh 21,PPh 22,PPh 23,PPh Final',
            'tax_amount' => 'nullable|numeric|min:0',
            'billing_code' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,paid,in_gu,completed',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('attachment')) {
            if ($expenditureReceipt->attachment_path) {
                Storage::disk('public')->delete($expenditureReceipt->attachment_path);
            }
            $path = $request->file('attachment')->store('receipts_spj', 'public');
            $validated['attachment_path'] = $path;
        }

        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'paid';

        $expenditureReceipt->update($validated);

        return redirect()->back()->with('message', 'Kwitansi belanja kas UP berhasil diperbarui.');
    }

    public function destroy(ExpenditureReceipt $expenditureReceipt)
    {
        if ($expenditureReceipt->status === 'completed' || $expenditureReceipt->expenditure_id !== null) {
            return redirect()->back()->with('error', 'Kwitansi yang sudah masuk dalam dokumen SPP-GU tidak dapat dihapus.');
        }

        if ($expenditureReceipt->attachment_path) {
            Storage::disk('public')->delete($expenditureReceipt->attachment_path);
        }

        $expenditureReceipt->delete();

        return redirect()->back()->with('message', 'Kwitansi belanja kas UP berhasil dihapus.');
    }

    public function print(ExpenditureReceipt $expenditureReceipt)
    {
        $expenditureReceipt->load(['accountCode', 'createdBy', 'expenditure.ptk']);

        // Ambil data PPTK
        $pptk = null;
        if ($expenditureReceipt->expenditure && $expenditureReceipt->expenditure->ptk) {
            $pptk = $expenditureReceipt->expenditure->ptk;
        }

        if (!$pptk) {
            $year = $expenditureReceipt->date ? $expenditureReceipt->date->format('Y') : date('Y');
            $rbaDoc = RbaDocument::where('account_code_id', $expenditureReceipt->account_code_id)
                ->where('budget_year', $year)
                ->whereNotNull('pptk_id')
                ->with('pptk')
                ->first();

            if ($rbaDoc && $rbaDoc->pptk) {
                $pptk = $rbaDoc->pptk;
            }
        }

        if (!$pptk) {
            $pptk = \App\Models\User::where('name', 'like', '%Stevy Rotikan%')->first();
        }

        if (!$pptk) {
            $pptk = $expenditureReceipt->createdBy ?? \App\Models\User::first();
        }

        // Ambil data Bendahara Pengeluaran
        $treasurer = \App\Models\User::role('bendahara')->first()
            ?? \App\Models\User::where('name', 'like', '%Saskia Paraso%')->first()
            ?? $expenditureReceipt->createdBy;

        return Inertia::render('ExpenditureReceipts/Print', [
            'receipt' => $expenditureReceipt,
            'pptk' => $pptk,
            'treasurer' => $treasurer,
        ]);
    }

    private function getBelanjaAccountCodes($year)
    {
        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$year}")->value('value') ?? 0);

        $rbaDocs = RbaDocument::with('accountCode')
            ->where('budget_year', $year)
            ->where('version', $activeVersion)
            ->where('rba_type', 'rinci')
            ->whereHas('accountCode', function ($q) {
                $q->where('code', 'like', '5%');
            })
            ->get();

        if ($rbaDocs->isEmpty()) {
            $rbaDocs = RbaDocument::with('accountCode')
                ->where('budget_year', $year)
                ->where('version', $activeVersion)
                ->where('rba_type', 'gelondongan')
                ->whereHas('accountCode', function ($q) {
                    $q->where('code', 'like', '5%');
                })
                ->get();
        }

        if ($rbaDocs->isEmpty()) {
            // Fallback: semua akun belanja aktif
            return AccountCode::where('is_active', true)
                ->where('code', 'like', '5%')
                ->orderBy('code')
                ->get(['id', 'code', 'name']);
        }

        return $rbaDocs->map(function ($doc) {
            return [
                'id' => $doc->accountCode->id,
                'code' => $doc->accountCode->code,
                'name' => $doc->accountCode->name,
                'total_budget' => (float) $doc->total_budget,
            ];
        })->sortBy('code')->values()->all();
    }
}
