<?php

namespace App\Services;

use App\Models\AccountCode;
use App\Models\RbaDocument;
use App\Models\ReceiptDetail;
use App\Models\ExpenditureDetail;
use Illuminate\Support\Facades\DB;

class LraService
{
    /**
     * Get aggregated LRA Data.
     *
     * @param string $year
     * @param string|null $month
     * @param string|null $version
     * @return array
     */
    public function getLraData($year, $month = null, $version = null)
    {
        // 1. Fetch all active account codes
        $accounts = AccountCode::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id')
            ->toArray();

        // 2. Fetch Budget (RBA)
        $rbaQuery = RbaDocument::where('budget_year', $year);
        if ($version && $version !== 'all') {
            $rbaQuery->where('version', $version);
        } else {
            // Jika tidak ada versi spesifik yang dipilih (atau 'all'), gunakan versi aktif
            $activeVersion = (int) (\App\Models\Setting::where('key', "rba_active_version_{$year}")->value('value') ?? 0);
            $rbaQuery->where('version', $activeVersion);
        }
        
        $budgets = $rbaQuery->select('account_code_id', DB::raw('SUM(total_budget) as total'))
            ->groupBy('account_code_id')
            ->pluck('total', 'account_code_id')
            ->toArray();

        $applyPeriodFilter = function ($query, $dateColumn, $period) {
            if (!$period || $period === 'all') return;
            switch ($period) {
                case 'q1': $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 3); break;
                case 'q2': $query->whereMonth($dateColumn, '>=', 4)->whereMonth($dateColumn, '<=', 6); break;
                case 'q3': $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 9); break;
                case 'q4': $query->whereMonth($dateColumn, '>=', 10)->whereMonth($dateColumn, '<=', 12); break;
                case 's1': $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 6); break;
                case 's2': $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 12); break;
                default: $query->whereMonth($dateColumn, $period); break;
            }
        };

        // 3. Fetch Revenue Realization
        $receiptQuery = ReceiptDetail::join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
            ->whereYear('receipts.date', $year)
            ->where('receipts.status', '!=', 'rejected');
            
        $applyPeriodFilter($receiptQuery, 'receipts.date', $month);

        $revenues = $receiptQuery->select('receipt_details.account_code_id', DB::raw('SUM(receipt_details.amount) as total'))
            ->groupBy('receipt_details.account_code_id')
            ->pluck('total', 'receipt_details.account_code_id')
            ->toArray();

        // 4. Fetch Expenditure Realization
        $expenditureQuery = ExpenditureDetail::join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->whereYear('expenditures.date', $year)
            ->where('expenditures.status', '!=', 'rejected');

        $applyPeriodFilter($expenditureQuery, 'expenditures.date', $month);

        $expenditures = $expenditureQuery->select('expenditure_details.account_code_id', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('expenditure_details.account_code_id')
            ->pluck('total', 'expenditure_details.account_code_id')
            ->toArray();

        // 5. Initialize flat map
        $map = [];
        foreach ($accounts as $id => $account) {
            $map[$id] = [
                'id' => $account['id'],
                'parent_id' => $account['parent_id'],
                'level' => $account['level'],
                'code' => $account['code'],
                'name' => $account['name'],
                'budget' => isset($budgets[$id]) ? (float)$budgets[$id] : 0.0,
                'revenue' => isset($revenues[$id]) ? (float)$revenues[$id] : 0.0,
                'expenditure' => isset($expenditures[$id]) ? (float)$expenditures[$id] : 0.0,
                'realization' => 0.0, // Will compute based on type
                'variance' => 0.0,
                'percentage' => 0.0,
                'children' => []
            ];
            
            // Set initial realization based on basic account types:
            // Usually Code starting with 4 is Revenue (Pendapatan), 5 is Expenditure (Belanja), 6 is Financing (Pembiayaan)
            $firstDigit = substr($account['code'], 0, 1);
            if ($firstDigit == '4') {
                $map[$id]['realization'] = $map[$id]['revenue'];
            } elseif ($firstDigit == '5') {
                $map[$id]['realization'] = $map[$id]['expenditure'];
            } elseif ($firstDigit == '6') {
                $map[$id]['realization'] = $map[$id]['revenue'] + $map[$id]['expenditure'];
            } else {
                $map[$id]['realization'] = $map[$id]['revenue'] + $map[$id]['expenditure'];
            }
        }

        // 6. Rollup from bottom to top (Sort by level descending)
        $levels = array_column($map, 'level');
        array_multisort($levels, SORT_DESC, $map);

        $tempMap = [];
        foreach ($map as $node) {
            $tempMap[$node['id']] = $node;
        }
        $map = $tempMap;

        foreach ($map as $id => &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['budget'] += $node['budget'];
                $map[$node['parent_id']]['revenue'] += $node['revenue'];
                $map[$node['parent_id']]['expenditure'] += $node['expenditure'];
                $map[$node['parent_id']]['realization'] += $node['realization'];
            }
        }

        // 7. Compute variance and percentage, and build Tree
        $tree = [];
        $finalMap = [];
        
        // Re-sort to original order (by code)
        $codes = array_column($map, 'code');
        array_multisort($codes, SORT_ASC, $map);
        
        foreach ($map as $id => $node) {
            // Prune nodes that have no activity (0 budget and 0 realization)
            // But keep level 1 nodes (e.g. Pendapatan, Belanja) for structural clarity
            if ($node['budget'] == 0 && $node['realization'] == 0 && $node['revenue'] == 0 && $node['expenditure'] == 0) {
                if ($node['level'] > 1) {
                    continue;
                }
            }

            $node['variance'] = $node['budget'] - $node['realization'];
            if ($node['budget'] > 0) {
                $node['percentage'] = round(($node['realization'] / $node['budget']) * 100, 2);
            } else {
                $node['percentage'] = $node['realization'] > 0 ? 100 : 0;
            }
            
            $finalMap[$node['id']] = $node;
        }

        // Keep only root nodes in tree
        foreach ($finalMap as $id => &$node) {
            if ($node['parent_id'] === null) {
                $tree[] = &$node;
            } else {
                if (isset($finalMap[$node['parent_id']])) {
                    $finalMap[$node['parent_id']]['children'][] = &$node;
                }
            }
        }

        return [
            'tree' => $tree,
            'summary' => $this->calculateSummary($finalMap)
        ];
    }

    private function calculateSummary($map)
    {
        $summary = [
            'total_revenue_budget' => 0,
            'total_revenue_realization' => 0,
            'total_expenditure_budget' => 0,
            'total_expenditure_realization' => 0,
            'silpa' => 0
        ];

        foreach ($map as $node) {
            // Level 1 accounts
            if ($node['level'] == 1) {
                $firstDigit = substr($node['code'], 0, 1);
                if ($firstDigit == '4') {
                    $summary['total_revenue_budget'] += $node['budget'];
                    $summary['total_revenue_realization'] += $node['realization'];
                } elseif ($firstDigit == '5') {
                    $summary['total_expenditure_budget'] += $node['budget'];
                    $summary['total_expenditure_realization'] += $node['realization'];
                }
            }
        }
        
        $summary['silpa'] = $summary['total_revenue_realization'] - $summary['total_expenditure_realization'];
        
        return $summary;
    }
}
