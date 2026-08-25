<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AccountCode;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/TrialBalance/Index');
    }

    public function data(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Parameter tidak lengkap'], 400);
        }

        // We only want to show accounts that are used for transactions (typically level 4 or leaf nodes)
        $accounts = AccountCode::where('is_active', true)
            ->where('level', '>=', 4)
            ->orderBy('code')
            ->get();

        // Get Opening Balances
        $openingBalances = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->where('journals.date', '<', $startDate)
            ->selectRaw('account_code_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        // Get Period Mutations
        $mutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereBetween('journals.date', [$startDate, $endDate])
            ->selectRaw('account_code_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        $result = [];
        $grandTotals = [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'mutation_debit' => 0,
            'mutation_credit' => 0,
            'closing_debit' => 0,
            'closing_credit' => 0,
        ];

        foreach ($accounts as $account) {
            $normalBalance = $account->getNormalBalance();
            
            $openDebit = $openingBalances->get($account->id)->total_debit ?? 0;
            $openCredit = $openingBalances->get($account->id)->total_credit ?? 0;
            
            // Calculate opening net balance based on normal balance
            $openingBal = $normalBalance === 'D' 
                ? ($openDebit - $openCredit) 
                : ($openCredit - $openDebit);

            $mutDebit = $mutations->get($account->id)->total_debit ?? 0;
            $mutCredit = $mutations->get($account->id)->total_credit ?? 0;

            // Calculate closing net balance
            $closingBal = $openingBal;
            if ($normalBalance === 'D') {
                $closingBal += $mutDebit - $mutCredit;
            } else {
                $closingBal += $mutCredit - $mutDebit;
            }

            // Determine Debit/Credit column for opening balance
            $openingD = ($normalBalance === 'D' && $openingBal > 0) ? $openingBal : (($normalBalance === 'K' && $openingBal < 0) ? abs($openingBal) : 0);
            $openingK = ($normalBalance === 'K' && $openingBal > 0) ? $openingBal : (($normalBalance === 'D' && $openingBal < 0) ? abs($openingBal) : 0);
            
            // Determine Debit/Credit column for closing balance
            $closingD = ($normalBalance === 'D' && $closingBal > 0) ? $closingBal : (($normalBalance === 'K' && $closingBal < 0) ? abs($closingBal) : 0);
            $closingK = ($normalBalance === 'K' && $closingBal > 0) ? $closingBal : (($normalBalance === 'D' && $closingBal < 0) ? abs($closingBal) : 0);

            // Only include accounts that have any activity or balance
            if ($openingBal != 0 || $mutDebit != 0 || $mutCredit != 0 || $closingBal != 0) {
                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'normal_balance' => $normalBalance,
                    'opening_debit' => $openingD,
                    'opening_credit' => $openingK,
                    'mutation_debit' => $mutDebit,
                    'mutation_credit' => $mutCredit,
                    'closing_debit' => $closingD,
                    'closing_credit' => $closingK
                ];

                $grandTotals['opening_debit'] += $openingD;
                $grandTotals['opening_credit'] += $openingK;
                $grandTotals['mutation_debit'] += $mutDebit;
                $grandTotals['mutation_credit'] += $mutCredit;
                $grandTotals['closing_debit'] += $closingD;
                $grandTotals['closing_credit'] += $closingK;
            }
        }

        return response()->json([
            'data' => $result,
            'totals' => $grandTotals
        ]);
    }
}
