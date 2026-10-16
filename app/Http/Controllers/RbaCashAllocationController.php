<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Models\RbaCashAllocation;
use App\Models\RbaDocument;
use App\Models\Setting;
use App\Services\BudgetRealizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RbaCashAllocationController extends Controller
{
    protected BudgetRealizationService $budgetRealizationService;

    public function __construct(BudgetRealizationService $budgetRealizationService)
    {
        $this->budgetRealizationService = $budgetRealizationService;
    }

    /**
     * Tampilkan halaman matriks Anggaran Kas Belanja (RAK).
     */
    public function index(Request $request)
    {
        $budgetYear = $request->session()->get('active_budget_year', date('Y'));
        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        $activeVersionName = RbaDocument::where('budget_year', $budgetYear)->where('version', $activeVersion)->value('version_name') ?? 'Induk';

        $rbaViewType = $request->query('rba_view_type', 'gelondongan');

        // Ambil dokumen RBA Belanja (prefix 5%) aktif
        $rbaDocs = RbaDocument::join('account_codes', 'rba_documents.account_code_id', '=', 'account_codes.id')
            ->where('account_codes.code', 'like', '5%')
            ->where('rba_documents.budget_year', $budgetYear)
            ->where('rba_documents.version', $activeVersion)
            ->where('rba_documents.rba_type', $rbaViewType)
            ->with(['accountCode', 'pptk', 'cashAllocations'])
            ->select('rba_documents.*')
            ->get();

        $activeAccountIds = $rbaDocs->pluck('account_code_id')->toArray();

        // Ambil realisasi belanja riil per akun rekening (SSOT)
        $realizations = $this->budgetRealizationService->getExpenditureRealizationByAccount(
            $budgetYear,
            null,
            $activeAccountIds
        );

        // Ambil alokasi kas bulanan per rba_document_id
        $allocationsMatrix = $this->budgetRealizationService->getMonthlyAllocationsMatrix(
            $budgetYear,
            $activeVersion,
            $rbaViewType
        );

        $items = [];
        $totalPaguRba = 0;
        $totalKasTeralokasi = 0;
        $balancedCount = 0;

        foreach ($rbaDocs as $doc) {
            $docAllocations = $allocationsMatrix[$doc->id] ?? [];
            $months = [];
            $docTotalKas = 0;

            for ($m = 1; $m <= 12; $m++) {
                $amt = (float) ($docAllocations[$m] ?? 0);
                $months[$m] = $amt;
                $docTotalKas += $amt;
            }

            $pagu = (float) $doc->total_budget;
            $selisih = round($pagu - $docTotalKas, 2);
            $isBalanced = abs($selisih) <= 0.05 && $pagu > 0;

            if ($isBalanced) {
                $balancedCount++;
            }

            $totalPaguRba += $pagu;
            $totalKasTeralokasi += $docTotalKas;

            $items[] = [
                'id' => $doc->id,
                'account_code_id' => $doc->account_code_id,
                'account_code' => $doc->accountCode ? $doc->accountCode->code : '-',
                'account_name' => $doc->accountCode ? $doc->accountCode->name : '-',
                'pptk_name' => $doc->pptk ? $doc->pptk->name : '-',
                'total_budget' => $pagu,
                'months' => $months,
                'total_cash' => $docTotalKas,
                'difference' => $selisih,
                'is_balanced' => $isBalanced,
                'realization' => (float) ($realizations[$doc->account_code_id] ?? 0),
            ];
        }

        return Inertia::render('Rba/CashAllocations/Index', [
            'items' => $items,
            'budgetYear' => $budgetYear,
            'currentVersion' => $activeVersion,
            'currentVersionName' => $activeVersionName,
            'rbaViewType' => $rbaViewType,
            'summary' => [
                'total_pagu' => $totalPaguRba,
                'total_kas' => $totalKasTeralokasi,
                'total_selisih' => round($totalPaguRba - $totalKasTeralokasi, 2),
                'total_rekening' => count($rbaDocs),
                'rekening_seimbang' => $balancedCount,
            ]
        ]);
    }

    /**
     * Ambil data alokasi kas bulanan untuk 1 dokumen RBA (JSON untuk modal).
     */
    public function show(RbaDocument $rbaDocument)
    {
        $rbaDocument->load(['accountCode', 'pptk', 'cashAllocations']);
        $allocations = $rbaDocument->cashAllocations->pluck('amount', 'month')->toArray();
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = (float) ($allocations[$m] ?? 0);
        }

        return response()->json([
            'status' => 'success',
            'rba_document' => [
                'id' => $rbaDocument->id,
                'account_code' => $rbaDocument->accountCode ? $rbaDocument->accountCode->code : '-',
                'account_name' => $rbaDocument->accountCode ? $rbaDocument->accountCode->name : '-',
                'pptk_name' => $rbaDocument->pptk ? $rbaDocument->pptk->name : '-',
                'total_budget' => (float) $rbaDocument->total_budget,
                'months' => $months,
            ]
        ]);
    }

    /**
     * Simpan / Perbarui alokasi kas 12 bulan untuk 1 dokumen RBA.
     */
    public function update(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'months' => 'required|array',
            'months.1' => 'required|numeric|min:0',
            'months.2' => 'required|numeric|min:0',
            'months.3' => 'required|numeric|min:0',
            'months.4' => 'required|numeric|min:0',
            'months.5' => 'required|numeric|min:0',
            'months.6' => 'required|numeric|min:0',
            'months.7' => 'required|numeric|min:0',
            'months.8' => 'required|numeric|min:0',
            'months.9' => 'required|numeric|min:0',
            'months.10' => 'required|numeric|min:0',
            'months.11' => 'required|numeric|min:0',
            'months.12' => 'required|numeric|min:0',
        ]);

        $totalAllocated = array_sum($validated['months']);
        $pagu = (float) $rbaDocument->total_budget;

        // Validasi keseimbangan 100% (toleransi 5 sen untuk floating point)
        if (abs($totalAllocated - $pagu) > 0.05) {
            $formattedAllocated = 'Rp ' . number_format($totalAllocated, 2, ',', '.');
            $formattedPagu = 'Rp ' . number_format($pagu, 2, ',', '.');
            return back()->with('error', "Total alokasi 12 bulan ({$formattedAllocated}) harus sama dengan pagu tahunan RBA ({$formattedPagu}).");
        }

        DB::transaction(function () use ($rbaDocument, $validated) {
            for ($m = 1; $m <= 12; $m++) {
                RbaCashAllocation::updateOrCreate(
                    [
                        'rba_document_id' => $rbaDocument->id,
                        'month' => $m,
                    ],
                    [
                        'amount' => $validated['months'][$m],
                    ]
                );
            }
        });

        return back()->with('message', 'Alokasi anggaran kas berhasil disimpan.');
    }

    /**
     * Endpoint pembantu distribusi cerdas (Bagi Rata / Pola Triwulan).
     */
    public function autoDistribute(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'method' => 'required|in:equal_12,quarterly,quarterly_progressive',
            'locked_months' => 'nullable|array',
            'locked_months.*' => 'numeric|min:0',
        ]);

        $pagu = (float) $rbaDocument->total_budget;
        $lockedMonths = $validated['locked_months'] ?? [];
        $months = [];

        if ($validated['method'] === 'equal_12') {
            // Hitung sisa yang belum dikunci
            $lockedSum = 0;
            $unlockedCount = 0;

            for ($m = 1; $m <= 12; $m++) {
                if (isset($lockedMonths[$m])) {
                    $months[$m] = (float) $lockedMonths[$m];
                    $lockedSum += $months[$m];
                } else {
                    $unlockedCount++;
                }
            }

            $remainingPagu = max(0, $pagu - $lockedSum);

            if ($unlockedCount > 0) {
                $monthly = round($remainingPagu / $unlockedCount, 2);
                $accumulated = 0;
                $lastUnlockedMonth = null;

                for ($m = 1; $m <= 12; $m++) {
                    if (!isset($lockedMonths[$m])) {
                        $lastUnlockedMonth = $m;
                        $months[$m] = $monthly;
                        $accumulated += $monthly;
                    }
                }

                // Masukkan sisa selisih pembulatan ke bulan unlocked terakhir (biasanya Des)
                if ($lastUnlockedMonth !== null) {
                    $diff = round($remainingPagu - $accumulated, 2);
                    $months[$lastUnlockedMonth] += $diff;
                }
            }
        } elseif ($validated['method'] === 'quarterly') {
            // Rata per triwulan: 25% per triwulan, masing-masing dibagi 3 bulan
            $quarterPagu = round($pagu / 4, 2);
            $qAccumulated = 0;

            for ($q = 1; $q <= 4; $q++) {
                $qPagu = ($q === 4) ? ($pagu - $qAccumulated) : $quarterPagu;
                $qAccumulated += $qPagu;

                $mMonthly = round($qPagu / 3, 2);
                $mAccumulated = 0;

                for ($sub = 1; $sub <= 3; $sub++) {
                    $m = ($q - 1) * 3 + $sub;
                    if ($sub === 3) {
                        $months[$m] = $qPagu - $mAccumulated;
                    } else {
                        $months[$m] = $mMonthly;
                        $mAccumulated += $mMonthly;
                    }
                }
            }
        } elseif ($validated['method'] === 'quarterly_progressive') {
            // Pola Progresif: TW I 20%, TW II 30%, TW III 30%, TW IV 20%
            $percentages = [1 => 0.20, 2 => 0.30, 3 => 0.30, 4 => 0.20];
            $qAccumulated = 0;

            for ($q = 1; $q <= 4; $q++) {
                $qPagu = ($q === 4) ? ($pagu - $qAccumulated) : round($pagu * $percentages[$q], 2);
                $qAccumulated += $qPagu;

                $mMonthly = round($qPagu / 3, 2);
                $mAccumulated = 0;

                for ($sub = 1; $sub <= 3; $sub++) {
                    $m = ($q - 1) * 3 + $sub;
                    if ($sub === 3) {
                        $months[$m] = $qPagu - $mAccumulated;
                    } else {
                        $months[$m] = $mMonthly;
                        $mAccumulated += $mMonthly;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'months' => $months,
            'total_allocated' => array_sum($months),
            'total_budget' => $pagu,
        ]);
    }
}
