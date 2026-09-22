<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BudgetRealizationService
{
    /**
     * Cache memori untuk aturan jenis pengeluaran non-anggaran.
     */
    protected ?array $nonBudgetaryTypesCache = null;

    /**
     * Ambil daftar jenis pengeluaran non-anggaran (misal UP, TU) dari Pengaturan Sistem.
     * Mengacu pada setting 'expenditure_journal_rules' (is_budgetary = false).
     *
     * @return array
     */
    public function getNonBudgetaryTypes(): array
    {
        if ($this->nonBudgetaryTypesCache !== null) {
            return $this->nonBudgetaryTypesCache;
        }

        $rulesJson = \App\Models\Setting::where('key', 'expenditure_journal_rules')->value('value');
        if ($rulesJson) {
            $rules = json_decode($rulesJson, true);
            $nonBudgetary = [];
            if (is_array($rules)) {
                foreach ($rules as $type => $rule) {
                    if (isset($rule['is_budgetary']) && !$rule['is_budgetary']) {
                        $nonBudgetary[] = $type;
                    }
                }
            }
            if (!empty($nonBudgetary)) {
                return $this->nonBudgetaryTypesCache = $nonBudgetary;
            }
        }

        return $this->nonBudgetaryTypesCache = ['UP', 'TU'];
    }

    /**
     * Cek apakah jenis pengeluaran termasuk belanja riil / anggaran (budgetary).
     *
     * @param string $type
     * @return bool
     */
    public function isBudgetaryType(string $type): bool
    {
        return !in_array($type, $this->getNonBudgetaryTypes());
    }

    /**
     * Terapkan filter periode (bulan, triwulan, atau semester) pada query.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string $dateColumn
     * @param string|int|null $period
     * @return void
     */
    public function applyPeriodFilter($query, string $dateColumn, $period = null): void
    {
        if (!$period || $period === 'all') {
            return;
        }

        switch ($period) {
            case 'q1':
                $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 3);
                break;
            case 'q2':
                $query->whereMonth($dateColumn, '>=', 4)->whereMonth($dateColumn, '<=', 6);
                break;
            case 'q3':
                $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 9);
                break;
            case 'q4':
                $query->whereMonth($dateColumn, '>=', 10)->whereMonth($dateColumn, '<=', 12);
                break;
            case 's1':
                $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 6);
                break;
            case 's2':
                $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 12);
                break;
            default:
                $query->whereMonth($dateColumn, $period);
                break;
        }
    }

    /**
     * Hitung total realisasi belanja riil per kode rekening (SSOT Belanja).
     * Formula: Dokumen Belanja Non-GU/LS Cair + Kuitansi Belanja Kas UP Cair.
     *
     * @param string|int $year
     * @param string|int|null $period
     * @param array|null $accountIds
     * @param bool $statusOnlyDisbursed
     * @return array [account_code_id => total_realization]
     */
    public function getExpenditureRealizationByAccount($year, $period = null, ?array $accountIds = null, bool $statusOnlyDisbursed = true): array
    {
        // 1. Belanja dari dokumen SPPD/SPD non-UP & non-GU ganda
        $expQuery = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->whereYear('expenditures.date', $year)
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($q) {
                $q->where('expenditures.type', '!=', 'GU')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('expenditure_receipts')
                          ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                  });
            });

        if ($statusOnlyDisbursed) {
            $expQuery->where('expenditures.status', 'disbursed');
        } else {
            $expQuery->where('expenditures.status', '!=', 'rejected');
        }

        $this->applyPeriodFilter($expQuery, 'expenditures.date', $period);

        if (!empty($accountIds)) {
            $expQuery->whereIn('expenditure_details.account_code_id', $accountIds);
        }

        $docTotals = $expQuery->select('expenditure_details.account_code_id', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('expenditure_details.account_code_id')
            ->pluck('total', 'account_code_id')
            ->toArray();

        // 2. Belanja kas UP dari kuitansi cair (status: paid, in_gu, completed)
        $receiptQuery = DB::table('expenditure_receipts')
            ->whereYear('date', $year)
            ->whereIn('status', ['paid', 'in_gu', 'completed']);

        $this->applyPeriodFilter($receiptQuery, 'date', $period);

        if (!empty($accountIds)) {
            $receiptQuery->whereIn('account_code_id', $accountIds);
        }

        $receiptTotals = $receiptQuery->select('account_code_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_code_id')
            ->pluck('total', 'account_code_id')
            ->toArray();

        // 3. Gabungkan hasil per akun rekening
        $allIds = array_unique(array_merge(
            $accountIds ?: [],
            array_keys($docTotals),
            array_keys($receiptTotals)
        ));

        $results = [];
        foreach ($allIds as $accId) {
            $total = (float) ($docTotals[$accId] ?? 0) + (float) ($receiptTotals[$accId] ?? 0);
            if ($total > 0) {
                $results[$accId] = $total;
            }
        }

        return $results;
    }

    /**
     * Hitung total realisasi penerimaan pendapatan per kode rekening (SSOT Pendapatan).
     *
     * @param string|int $year
     * @param string|int|null $period
     * @param array|null $accountIds
     * @return array [account_code_id => total_realization]
     */
    public function getRevenueRealizationByAccount($year, $period = null, ?array $accountIds = null): array
    {
        $query = DB::table('receipt_details')
            ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
            ->where('receipts.status', 'submitted')
            ->whereYear('receipts.date', $year);

        $this->applyPeriodFilter($query, 'receipts.date', $period);

        if (!empty($accountIds)) {
            $query->whereIn('receipt_details.account_code_id', $accountIds);
        }

        return $query->select('receipt_details.account_code_id', DB::raw('SUM(receipt_details.amount) as total'))
            ->groupBy('receipt_details.account_code_id')
            ->pluck('total', 'account_code_id')
            ->toArray();
    }

    /**
     * Hitung ringkasan pemakaian pagu per akun rekening (digunakan pada form SPPD).
     * Mengelompokkan nominal ke dalam 'submitted' (sedang proses) dan 'disbursed' (sudah cair).
     *
     * @param string|int $budgetYear
     * @param int|null $excludeExpenditureId
     * @return array [account_code_id => ['submitted' => float, 'disbursed' => float, 'total' => float]]
     */
    public function getBudgetUsageByAccount($budgetYear, ?int $excludeExpenditureId = null): array
    {
        $usageQuery = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->whereYear('expenditures.date', $budgetYear)
            ->where('expenditures.status', '!=', 'rejected')
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($q) {
                $q->where('expenditures.type', '!=', 'GU')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('expenditure_receipts')
                          ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                  });
            });

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
                $budgetUsage[$accId] = ['submitted' => 0.0, 'disbursed' => 0.0, 'total' => 0.0];
            }
            if (in_array($usage->status, ['disbursed', 'spd_disbursed'])) {
                $budgetUsage[$accId]['disbursed'] += (float) $usage->total;
            } else {
                $budgetUsage[$accId]['submitted'] += (float) $usage->total;
            }
        }

        // Akumulasi belanja kas UP dari kuitansi cair (status: paid, in_gu, completed)
        $receiptUsageQuery = DB::table('expenditure_receipts')
            ->whereYear('date', $budgetYear)
            ->whereIn('status', ['paid', 'in_gu', 'completed']);

        if ($excludeExpenditureId) {
            $receiptUsageQuery->where(function ($q) use ($excludeExpenditureId) {
                $q->whereNull('expenditure_id')
                  ->orWhere('expenditure_id', '!=', $excludeExpenditureId);
            });
        }

        $receiptUsageResults = $receiptUsageQuery->select('account_code_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_code_id')
            ->get();

        foreach ($receiptUsageResults as $rUsage) {
            $accId = $rUsage->account_code_id;
            if (!isset($budgetUsage[$accId])) {
                $budgetUsage[$accId] = ['submitted' => 0.0, 'disbursed' => 0.0, 'total' => 0.0];
            }
            $budgetUsage[$accId]['disbursed'] += (float) $rUsage->total;
        }

        foreach ($budgetUsage as $accId => &$usage) {
            $usage['total'] = $usage['submitted'] + $usage['disbursed'];
        }

        return $budgetUsage;
    }

    /**
     * Hitung total pemakaian anggaran untuk 1 akun rekening tertentu (digunakan untuk validasi batas pagu).
     *
     * @param int $accountCodeId
     * @param string|int $budgetYear
     * @param int|null $excludeExpenditureId
     * @return float
     */
    public function getUsedBudgetForAccount(int $accountCodeId, $budgetYear, ?int $excludeExpenditureId = null): float
    {
        $docQuery = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->where('expenditure_details.account_code_id', $accountCodeId)
            ->whereYear('expenditures.date', $budgetYear)
            ->where('expenditures.status', '!=', 'rejected')
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($sub) {
                $sub->where('expenditures.type', '!=', 'GU')
                    ->orWhereNotExists(function ($ex) {
                        $ex->select(DB::raw(1))
                            ->from('expenditure_receipts')
                            ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                    });
            });

        if ($excludeExpenditureId) {
            $docQuery->where('expenditures.id', '!=', $excludeExpenditureId);
        }

        $usedDoc = (float) $docQuery->sum('expenditure_details.amount');

        $receiptQuery = DB::table('expenditure_receipts')
            ->where('account_code_id', $accountCodeId)
            ->whereYear('date', $budgetYear)
            ->whereIn('status', ['paid', 'in_gu', 'completed']);

        if ($excludeExpenditureId) {
            $receiptQuery->where(function ($q) use ($excludeExpenditureId) {
                $q->whereNull('expenditure_id')
                  ->orWhere('expenditure_id', '!=', $excludeExpenditureId);
            });
        }

        $usedReceipt = (float) $receiptQuery->sum('amount');

        return $usedDoc + $usedReceipt;
    }

    /**
     * Ambil data arus kas keluar belanja riil per bulan (1-12) untuk Dashboard.
     *
     * @param string|int $year
     * @return array [1 => total, 2 => total, ..., 12 => total]
     */
    public function getMonthlyCashOut($year): array
    {
        $cashOutData = array_fill(1, 12, 0.0);

        // Belanja dokumen non-UP & non-GU bertaut cair
        $docExpenditures = DB::table('expenditures')
            ->join('expenditure_details', 'expenditures.id', '=', 'expenditure_details.expenditure_id')
            ->whereYear('expenditures.date', $year)
            ->where('expenditures.status', 'disbursed')
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($q) {
                $q->where('expenditures.type', '!=', 'GU')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('expenditure_receipts')
                          ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                  });
            })
            ->select(DB::raw('MONTH(expenditures.date) as month'), DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy(DB::raw('MONTH(expenditures.date)'))
            ->get();

        foreach ($docExpenditures as $r) {
            $cashOutData[(int) $r->month] += (float) $r->total;
        }

        // Belanja kas UP dari kuitansi cair
        $receiptExpenditures = DB::table('expenditure_receipts')
            ->whereYear('date', $year)
            ->whereIn('status', ['paid', 'in_gu', 'completed'])
            ->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'))
            ->get();

        foreach ($receiptExpenditures as $r) {
            $cashOutData[(int) $r->month] += (float) $r->total;
        }

        return $cashOutData;
    }

    /**
     * Ambil top N breakdown pengeluaran riil berdasarkan nama rekening untuk Dashboard.
     *
     * @param string|int $year
     * @param int $startMonth
     * @param int $endMonth
     * @param int $limit
     * @return array ['labels' => string[], 'values' => float[]]
     */
    public function getExpenditureBreakdown($year, int $startMonth, int $endMonth, int $limit = 10): array
    {
        $breakdownMap = [];

        // Belanja dokumen
        $docBreakdown = DB::table('expenditures')
            ->join('expenditure_details', 'expenditures.id', '=', 'expenditure_details.expenditure_id')
            ->join('account_codes', 'expenditure_details.account_code_id', '=', 'account_codes.id')
            ->whereYear('expenditures.date', $year)
            ->where('expenditures.status', 'disbursed')
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($q) {
                $q->where('expenditures.type', '!=', 'GU')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('expenditure_receipts')
                          ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                  });
            })
            ->whereMonth('expenditures.date', '>=', $startMonth)
            ->whereMonth('expenditures.date', '<=', $endMonth)
            ->select('account_codes.name', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('account_codes.name')
            ->get();

        foreach ($docBreakdown as $b) {
            $breakdownMap[$b->name] = ($breakdownMap[$b->name] ?? 0.0) + (float) $b->total;
        }

        // Belanja kuitansi kas UP
        $receiptBreakdown = DB::table('expenditure_receipts')
            ->join('account_codes', 'expenditure_receipts.account_code_id', '=', 'account_codes.id')
            ->whereYear('expenditure_receipts.date', $year)
            ->whereIn('expenditure_receipts.status', ['paid', 'in_gu', 'completed'])
            ->whereMonth('expenditure_receipts.date', '>=', $startMonth)
            ->whereMonth('expenditure_receipts.date', '<=', $endMonth)
            ->select('account_codes.name', DB::raw('SUM(expenditure_receipts.amount) as total'))
            ->groupBy('account_codes.name')
            ->get();

        foreach ($receiptBreakdown as $b) {
            $breakdownMap[$b->name] = ($breakdownMap[$b->name] ?? 0.0) + (float) $b->total;
        }

        arsort($breakdownMap);

        $labels = [];
        $values = [];
        foreach (array_slice($breakdownMap, 0, $limit, true) as $name => $val) {
            $labels[] = $name;
            $values[] = (float) $val;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Hitung total belanja riil sepanjang masa (All Time) untuk perhitungan saldo akhir.
     *
     * @return float
     */
    public function getTotalAllTimeExpenditures(): float
    {
        $docOut = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->where('expenditures.status', 'disbursed')
            ->whereNotIn('expenditures.type', $this->getNonBudgetaryTypes())
            ->where(function ($q) {
                $q->where('expenditures.type', '!=', 'GU')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('expenditure_receipts')
                          ->whereColumn('expenditure_receipts.expenditure_id', 'expenditures.id');
                  });
            })
            ->sum('expenditure_details.amount');

        $receiptOut = DB::table('expenditure_receipts')
            ->whereIn('status', ['paid', 'in_gu', 'completed'])
            ->sum('amount');

        return (float) $docOut + (float) $receiptOut;
    }

    /**
     * Hitung total penerimaan pendapatan sepanjang masa (All Time) untuk perhitungan saldo akhir.
     *
     * @return float
     */
    public function getTotalAllTimeRevenues(): float
    {
        return (float) DB::table('receipt_details')
            ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
            ->where('receipts.status', 'submitted')
            ->sum('receipt_details.amount');
    }
}

