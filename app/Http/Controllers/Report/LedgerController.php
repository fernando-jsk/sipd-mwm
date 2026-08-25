<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AccountCode;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public function index()
    {
        $accounts = AccountCode::where('is_active', true)->where('level', '>=', 4)->orderBy('code')->get();
        return Inertia::render('Reports/Ledger/Index', [
            'accounts' => $accounts
        ]);
    }

    public function data(Request $request)
    {
        $accountId = $request->query('account_code_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$accountId || !$startDate || !$endDate) {
            return response()->json(['error' => 'Parameter tidak lengkap'], 400);
        }

        $account = AccountCode::findOrFail($accountId);
        $normalBalance = $account->getNormalBalance();

        // 1. Calculate Opening Balance (Saldo Awal)
        $openingQuery = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journal_details.account_code_id', $accountId)
            ->where('journals.status', 'posted')
            ->where('journals.date', '<', $startDate)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $openingDebit = $openingQuery->total_debit ?? 0;
        $openingCredit = $openingQuery->total_credit ?? 0;
        
        $openingBalance = $normalBalance === 'D' 
            ? ($openingDebit - $openingCredit)
            : ($openingCredit - $openingDebit);

        // 2. Fetch mutations
        $mutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journal_details.account_code_id', $accountId)
            ->where('journals.status', 'posted')
            ->whereBetween('journals.date', [$startDate, $endDate])
            ->select(
                'journals.date',
                'journals.reference_no',
                'journals.description as journal_description',
                'journal_details.description as detail_description',
                'journal_details.debit',
                'journal_details.credit'
            )
            ->orderBy('journals.date', 'asc')
            ->orderBy('journals.id', 'asc')
            ->get();

        // 3. Calculate running balance
        $runningBalance = $openingBalance;
        $resultMutations = [];

        foreach ($mutations as $mut) {
            $debit = $mut->debit ?? 0;
            $credit = $mut->credit ?? 0;

            if ($normalBalance === 'D') {
                $runningBalance += $debit - $credit;
            } else {
                $runningBalance += $credit - $debit;
            }

            $resultMutations[] = [
                'date' => $mut->date,
                'reference_no' => $mut->reference_no,
                'description' => $mut->detail_description ?: $mut->journal_description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance
            ];
        }

        return response()->json([
            'account' => $account,
            'normal_balance' => $normalBalance,
            'opening_balance' => $openingBalance,
            'mutations' => $resultMutations,
            'closing_balance' => $runningBalance
        ]);
    }
}
