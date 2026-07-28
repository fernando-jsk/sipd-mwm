<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $activeYear = session('active_budget_year', date('Y'));
        $startMonth = (int) $request->input('startMonth', 1);
        $endMonth = (int) $request->input('endMonth', date('n'));
        
        // Pastikan rentang bulan valid
        if ($startMonth < 1) $startMonth = 1;
        if ($endMonth > 12) $endMonth = 12;
        if ($startMonth > $endMonth) $startMonth = $endMonth;
        
        // --- 1. Tren Cash In & Cash Out (Jan - Dec) ---
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $cashInData = array_fill(0, 12, 0);
        $cashOutData = array_fill(0, 12, 0);
        
        // Pemasukan
        $receipts = DB::table('receipts')
            ->join('receipt_details', 'receipts.id', '=', 'receipt_details.receipt_id')
            ->whereYear('receipts.date', $activeYear)
            ->where('receipts.status', 'submitted')
            ->select(DB::raw('MONTH(receipts.date) as month'), DB::raw('SUM(receipt_details.amount) as total'))
            ->groupBy(DB::raw('MONTH(receipts.date)'))
            ->get();
            
        foreach ($receipts as $r) {
            $cashInData[$r->month - 1] = (float) $r->total;
        }
        
        // Pengeluaran
        $expenditures = DB::table('expenditures')
            ->join('expenditure_details', 'expenditures.id', '=', 'expenditure_details.expenditure_id')
            ->whereYear('expenditures.date', $activeYear)
            ->where('expenditures.status', 'disbursed')
            ->select(DB::raw('MONTH(expenditures.date) as month'), DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy(DB::raw('MONTH(expenditures.date)'))
            ->get();
            
        foreach ($expenditures as $r) {
            $cashOutData[$r->month - 1] = (float) $r->total;
        }
        
        // --- 2. Filtered Cash In & Out ---
        $currentMonthIn = 0;
        $currentMonthOut = 0;
        for ($i = $startMonth - 1; $i <= $endMonth - 1; $i++) {
            $currentMonthIn += $cashInData[$i] ?? 0;
            $currentMonthOut += $cashOutData[$i] ?? 0;
        }
        $netCashFlow = $currentMonthIn - $currentMonthOut;

        // --- 3. Saldo Akhir (Total All Time) ---
        $totalIn = DB::table('receipt_details')
            ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
            ->where('receipts.status', 'submitted')
            ->sum('receipt_details.amount');
            
        $totalOut = DB::table('expenditure_details')
            ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->where('expenditures.status', 'disbursed')
            ->sum('expenditure_details.amount');
            
        $endingBalance = (float) $totalIn - (float) $totalOut;

        // --- 4. Batas Aman (Minimum Safe Balance) ---
        $safeBalanceSetting = Setting::where('key', 'minimum_safe_balance')->first();
        $minimumSafeBalance = $safeBalanceSetting ? (float) $safeBalanceSetting->value : 500000000;

        // --- 5. Breakdown Pengeluaran (Filtered) ---
        $breakdownLabels = [];
        $breakdownValues = [];
        
        $breakdownQuery = DB::table('expenditures')
            ->join('expenditure_details', 'expenditures.id', '=', 'expenditure_details.expenditure_id')
            ->join('account_codes', 'expenditure_details.account_code_id', '=', 'account_codes.id')
            ->whereYear('expenditures.date', $activeYear)
            ->where('expenditures.status', 'disbursed')
            ->select('account_codes.name', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('account_codes.name')
            ->orderByDesc('total')
            ->whereMonth('expenditures.date', '>=', $startMonth)
            ->whereMonth('expenditures.date', '<=', $endMonth);

        $currentMonthExpenditures = $breakdownQuery->get();

        foreach ($currentMonthExpenditures as $exp) {
            $breakdownLabels[] = $exp->name;
            $breakdownValues[] = (float) $exp->total;
        }

        // --- Prepare Props ---
        $monthsCount = $endMonth - $startMonth + 1;
        
        $cashFlowData = [
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'months' => $months,
            'cashInData' => $cashInData,
            'cashOutData' => $cashOutData,
            'currentMonthIn' => $currentMonthIn,
            'currentMonthOut' => $currentMonthOut,
            'netCashFlow' => $netCashFlow,
            'endingBalance' => $endingBalance,
            'minimumSafeBalance' => $minimumSafeBalance,
            'breakdownLabels' => $breakdownLabels,
            'breakdownValues' => $breakdownValues,
            'dailyBurnRate' => $currentMonthOut / max(1, $monthsCount * 30),
        ];

        return Inertia::render('Dashboard', [
            'cashFlowData' => $cashFlowData
        ]);
    }
}
