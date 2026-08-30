<?php

namespace App\Services;

use App\Models\AccountCode;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    /**
     * Get aggregated Balance Sheet Data up to a specific date.
     *
     * @param string $date (Y-m-d)
     * @return array
     */
    public function getBalanceSheetData($date)
    {
        // 1. Fetch all active account codes for Aset (1), Kewajiban (2), Ekuitas (3)
        $accounts = AccountCode::where('is_active', true)
            ->where(function($query) {
                $query->where('code', 'like', '1%')
                      ->orWhere('code', 'like', '2%')
                      ->orWhere('code', 'like', '3%');
            })
            ->orderBy('code')
            ->get();

        // Build code to ID lookup map
        $codeToId = [];
        foreach ($accounts as $account) {
            $codeToId[$account->code] = $account->id;
        }

        // 2. Fetch all journal details up to the specified date for Balance Sheet Accounts (1, 2, 3)
        $mutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.date', '<=', $date)
            ->selectRaw('account_code_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        // 2b. Calculate Current Year Surplus/Deficit from Nominal Accounts (4: Pendapatan, 5: Belanja, 6: Pembiayaan)
        // If closing entry hasn't transferred it to Equity yet, this reflects the real-time interim operational result.
        $nominalMutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.date', '<=', $date)
            ->where(function($q) {
                $q->where('account_codes.code', 'like', '4%')
                  ->orWhere('account_codes.code', 'like', '5%')
                  ->orWhere('account_codes.code', 'like', '6%');
            })
            ->selectRaw("
                SUM(CASE WHEN account_codes.code LIKE '4%' THEN credit - debit ELSE 0 END) as total_pendapatan,
                SUM(CASE WHEN account_codes.code LIKE '5%' THEN debit - credit ELSE 0 END) as total_belanja,
                SUM(CASE WHEN account_codes.code LIKE '6%' THEN credit - debit ELSE 0 END) as total_pembiayaan
            ")
            ->first();

        $currentSurplus = (float)(($nominalMutations->total_pendapatan ?? 0) - ($nominalMutations->total_belanja ?? 0) + ($nominalMutations->total_pembiayaan ?? 0));

        // 3. Initialize flat map
        $map = [];
        foreach ($accounts as $account) {
            $id = $account->id;
            $mutDebit = $mutations->get($id)->total_debit ?? 0;
            $mutCredit = $mutations->get($id)->total_credit ?? 0;

            $firstDigit = substr($account->code, 0, 1);
            
            // Normal Balances: Aset (1) = Debit. Kewajiban (2) & Ekuitas (3) = Kredit.
            $balance = 0;
            if ($firstDigit == '1') {
                $balance = $mutDebit - $mutCredit;
            } else {
                $balance = $mutCredit - $mutDebit;
            }

            // Include Current Year Surplus/Deficit into Equity (Account 3.1.02 Surplus/Defisit-LO)
            if ($account->code === '3.1.02') {
                $balance += $currentSurplus;
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
                'first_digit' => $firstDigit,
                'balance' => (float)$balance,
                'children' => []
            ];
        }

        // 4. Rollup from deepest level to root (5 -> 4 -> 3 -> 2 -> 1)
        $nodesByLevel = [];
        foreach ($map as $id => $node) {
            $nodesByLevel[$node['level']][] = $id;
        }
        krsort($nodesByLevel);

        foreach ($nodesByLevel as $lvl => $nodeIds) {
            foreach ($nodeIds as $id) {
                $node = $map[$id];
                if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                    $map[$node['parent_id']]['balance'] += $map[$id]['balance'];
                }
            }
        }

        // 5. Structure into hierarchical tree
        $asetTree = [];
        $kewajibanTree = [];
        $ekuitasTree = [];

        // Sort by code natural/version order
        uasort($map, function ($a, $b) {
            return version_compare($a['code'], $b['code']);
        });

        // Attach children to parents
        foreach ($map as $id => &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$node;
            }
        }
        unset($node);

        // Group root nodes (level 1)
        foreach ($map as $id => &$node) {
            if (!$node['parent_id']) {
                if ($node['first_digit'] == '1') {
                    $asetTree[] = &$node;
                } elseif ($node['first_digit'] == '2') {
                    $kewajibanTree[] = &$node;
                } elseif ($node['first_digit'] == '3') {
                    $ekuitasTree[] = &$node;
                }
            }
        }
        unset($node);

        // Calculate Totals based on root trees
        $totalAset = array_sum(array_column($asetTree, 'balance'));
        $totalKewajiban = array_sum(array_column($kewajibanTree, 'balance'));
        $totalEkuitas = array_sum(array_column($ekuitasTree, 'balance'));

        return [
            'aset' => $asetTree,
            'kewajiban' => $kewajibanTree,
            'ekuitas' => $ekuitasTree,
            'summary' => [
                'total_aset' => $totalAset,
                'total_kewajiban' => $totalKewajiban,
                'total_ekuitas' => $totalEkuitas,
                'total_pasiva' => $totalKewajiban + $totalEkuitas,
                'is_balanced' => round($totalAset, 2) === round($totalKewajiban + $totalEkuitas, 2)
            ]
        ];
    }
}
