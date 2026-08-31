<?php

namespace App\Services;

use App\Models\AccountCode;
use Illuminate\Support\Facades\DB;

class LoService
{
    /**
     * Get aggregated Laporan Operasional (LO) Data for a specific year.
     *
     * @param int|string $year
     * @return array
     */
    public function getLoData($year)
    {
        $year = (int) $year;

        // 1. Fetch all active account codes for Pendapatan (4), Beban (5), Non-Operasional/Pembiayaan (6)
        $accounts = AccountCode::where('is_active', true)
            ->where(function($query) {
                $query->where('code', 'like', '4%')
                      ->orWhere('code', 'like', '5%')
                      ->orWhere('code', 'like', '6%');
            })
            ->orderBy('code')
            ->get();

        // Build code to ID lookup map for parent derivation
        $codeToId = [];
        foreach ($accounts as $account) {
            $codeToId[$account->code] = $account->id;
        }

        // 2. Fetch all journal details for the year (excluding opening balances)
        $mutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', '!=', 'opening_balance')
            ->selectRaw('account_code_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        // 3. Initialize flat map
        $map = [];
        foreach ($accounts as $account) {
            $id = $account->id;
            $mutDebit = $mutations->get($id)->total_debit ?? 0;
            $mutCredit = $mutations->get($id)->total_credit ?? 0;

            $firstDigit = substr($account->code, 0, 1);
            
            // Normal Balances:
            // Pendapatan (4) & Non-Operasional (6) = Kredit (credit - debit)
            // Beban (5) = Debit (debit - credit)
            $balance = 0;
            if ($firstDigit == '4' || $firstDigit == '6') {
                $balance = $mutCredit - $mutDebit;
            } elseif ($firstDigit == '5') {
                $balance = $mutDebit - $mutCredit;
            }

            // Derive parent_id from code structure if parent_id is missing
            $parentId = $account->parent_id;
            if (!$parentId && strpos($account->code, '.') !== false) {
                $parentCode = substr($account->code, 0, strrpos($account->code, '.'));
                if (isset($codeToId[$parentCode])) {
                    $parentId = $codeToId[$parentCode];
                }
            }

            $map[$id] = [
                'id' => $account->id,
                'parent_id' => $parentId,
                'level' => (int)$account->level,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float)$balance,
                'children' => []
            ];
        }

        // 4. Rollup from deepest level to top (Sort by level descending)
        $levels = array_column($map, 'level');
        array_multisort($levels, SORT_DESC, $map);

        $tempMap = [];
        foreach ($map as $node) {
            $tempMap[$node['id']] = $node;
        }
        $map = $tempMap;

        foreach ($map as $id => &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['balance'] += $node['balance'];
            }
        }
        unset($node);

        // 5. Re-sort to original order (by code)
        $codes = array_column($map, 'code');
        array_multisort($codes, SORT_ASC, $map);

        // Filter out zero-balance leaf accounts (keep level <= 2 for structure)
        $finalMap = [];
        foreach ($map as $id => $node) {
            if ($node['balance'] == 0 && $node['level'] > 2) {
                continue;
            }
            $finalMap[$node['id']] = $node;
        }

        // 6. Build trees cleanly without reference leakage
        $treeMap = [];
        foreach ($finalMap as $id => $node) {
            $node['children'] = [];
            $treeMap[$id] = $node;
        }

        foreach ($treeMap as $id => &$node) {
            if (!empty($node['parent_id']) && isset($treeMap[$node['parent_id']])) {
                $treeMap[$node['parent_id']]['children'][] = &$node;
            }
        }
        unset($node);

        $pendapatanTree = [];
        $bebanTree = [];
        $nonOperasionalTree = [];

        foreach ($treeMap as $id => $node) {
            if (empty($node['parent_id'])) {
                $firstDigit = substr($node['code'], 0, 1);
                if ($firstDigit === '4') {
                    $pendapatanTree[] = $node;
                } elseif ($firstDigit === '5') {
                    $bebanTree[] = $node;
                } elseif ($firstDigit === '6') {
                    $nonOperasionalTree[] = $node;
                }
            }
        }

        // 7. Calculate Summary totals
        $totalPendapatan = 0;
        $totalBeban = 0;
        $totalNonOperasional = 0;

        foreach ($finalMap as $node) {
            if ($node['level'] === 1) {
                $firstDigit = substr($node['code'], 0, 1);
                if ($firstDigit === '4') {
                    $totalPendapatan += $node['balance'];
                } elseif ($firstDigit === '5') {
                    $totalBeban += $node['balance'];
                } elseif ($firstDigit === '6') {
                    $totalNonOperasional += $node['balance'];
                }
            }
        }

        $surplusDefisitOperasi = $totalPendapatan - $totalBeban;
        $surplusDefisitLo = $surplusDefisitOperasi + $totalNonOperasional;

        return [
            'pendapatan' => $pendapatanTree,
            'beban' => $bebanTree,
            'non_operasional' => $nonOperasionalTree,
            'summary' => [
                'total_pendapatan' => (float)$totalPendapatan,
                'total_beban' => (float)$totalBeban,
                'surplus_defisit_operasi' => (float)$surplusDefisitOperasi,
                'total_non_operasional' => (float)$totalNonOperasional,
                'surplus_defisit_lo' => (float)$surplusDefisitLo,
            ]
        ];
    }
}
