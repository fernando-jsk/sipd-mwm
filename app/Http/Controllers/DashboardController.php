<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Services\BudgetRealizationService;

class DashboardController extends Controller
{
    protected BudgetRealizationService $budgetRealizationService;

    public function __construct(BudgetRealizationService $budgetRealizationService)
    {
        $this->budgetRealizationService = $budgetRealizationService;
    }

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
        
        // Pengeluaran Belanja Riil per bulan dari SSOT
        $monthlyCashOut = $this->budgetRealizationService->getMonthlyCashOut($activeYear);
        for ($m = 1; $m <= 12; $m++) {
            $cashOutData[$m - 1] = $monthlyCashOut[$m] ?? 0.0;
        }
        
        // --- 2. Filtered Cash In & Out ---
        $currentMonthIn = 0;
        $currentMonthOut = 0;
        for ($i = $startMonth - 1; $i <= $endMonth - 1; $i++) {
            $currentMonthIn += $cashInData[$i] ?? 0;
            $currentMonthOut += $cashOutData[$i] ?? 0;
        }
        $netCashFlow = $currentMonthIn - $currentMonthOut;

        // --- 3. Saldo Akhir (Total All Time) dari SSOT ---
        $totalIn = $this->budgetRealizationService->getTotalAllTimeRevenues();
        $totalOut = $this->budgetRealizationService->getTotalAllTimeExpenditures();
        $endingBalance = (float) $totalIn - (float) $totalOut;

        // --- 4. Batas Aman (Minimum Safe Balance) ---
        $safeBalanceSetting = Setting::where('key', 'minimum_safe_balance')->first();
        $minimumSafeBalance = $safeBalanceSetting ? (float) $safeBalanceSetting->value : 500000000;

        // --- 5. Breakdown Pengeluaran (Filtered) dari SSOT ---
        $expBreakdown = $this->budgetRealizationService->getExpenditureBreakdown($activeYear, $startMonth, $endMonth, 10);
        $breakdownLabels = $expBreakdown['labels'];
        $breakdownValues = $expBreakdown['values'];

        // --- 5b. Breakdown Penerimaan (Filtered by Active Year & Month Range) ---
        $receiptBreakdownParent = DB::table('receipts')
            ->join('receipt_details', 'receipts.id', '=', 'receipt_details.receipt_id')
            ->leftJoin('receipt_types', 'receipts.receipt_type_id', '=', 'receipt_types.id')
            ->whereYear('receipts.date', $activeYear)
            ->where('receipts.status', 'submitted')
            ->whereMonth('receipts.date', '>=', $startMonth)
            ->whereMonth('receipts.date', '<=', $endMonth)
            ->select(
                'receipt_types.id as id',
                DB::raw('COALESCE(receipt_types.name, "Lainnya") as name'),
                DB::raw('SUM(receipt_details.amount) as total')
            )
            ->groupBy('receipt_types.id', 'receipt_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (string) ($item->id ?? 'other'),
                    'name' => $item->name,
                    'total' => (float) $item->total,
                ];
            });

        $receiptBreakdownSub = DB::table('receipts')
            ->join('receipt_details', 'receipts.id', '=', 'receipt_details.receipt_id')
            ->leftJoin('receipt_types as parent', 'receipts.receipt_type_id', '=', 'parent.id')
            ->leftJoin('receipt_types as sub', 'receipts.receipt_sub_type_id', '=', 'sub.id')
            ->whereYear('receipts.date', $activeYear)
            ->where('receipts.status', 'submitted')
            ->whereMonth('receipts.date', '>=', $startMonth)
            ->whereMonth('receipts.date', '<=', $endMonth)
            ->select(
                'parent.id as parent_id',
                DB::raw('COALESCE(parent.name, "Lainnya") as parent_name'),
                'sub.id as sub_id',
                DB::raw('COALESCE(sub.name, "Tanpa Sub Jenis") as sub_name'),
                DB::raw('SUM(receipt_details.amount) as total')
            )
            ->groupBy('parent.id', 'parent.name', 'sub.id', 'sub.name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'parent_id' => (string) ($item->parent_id ?? 'other'),
                    'parent_name' => $item->parent_name,
                    'sub_id' => (string) ($item->sub_id ?? 'none'),
                    'sub_name' => $item->sub_name,
                    'total' => (float) $item->total,
                ];
            });

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
            'receiptBreakdown' => [
                'parents' => $receiptBreakdownParent,
                'subs' => $receiptBreakdownSub,
            ],
            'dailyBurnRate' => $currentMonthOut / max(1, $monthsCount * 30),
        ];

        // --- 6. Variance Budget (Selisih Anggaran) ---
        $activeVersionSetting = Setting::where('key', 'rba_active_version_' . $activeYear)->first();
        $activeVersion = $activeVersionSetting ? (int)$activeVersionSetting->value : 0;

        $totalRevenueBudget = (float) DB::table('rba_documents')
            ->join('account_codes', 'rba_documents.account_code_id', '=', 'account_codes.id')
            ->where('rba_documents.budget_year', $activeYear)
            ->where('rba_documents.version', $activeVersion)
            ->where('account_codes.code', 'like', '4%')
            ->sum('rba_documents.total_budget');

        $totalExpenseBudget = (float) DB::table('rba_documents')
            ->join('account_codes', 'rba_documents.account_code_id', '=', 'account_codes.id')
            ->where('rba_documents.budget_year', $activeYear)
            ->where('rba_documents.version', $activeVersion)
            ->where('account_codes.code', 'like', '5%')
            ->sum('rba_documents.total_budget');
            
        $monthlyRevenueBudget = $totalRevenueBudget / 12;
        $monthlyExpenseBudget = $totalExpenseBudget / 12;

        $revenueBudgetData = array_fill(0, 12, $monthlyRevenueBudget);
        $expenseBudgetData = array_fill(0, 12, $monthlyExpenseBudget);

        $varianceData = [
            'revenueBudget' => $revenueBudgetData,
            'revenueActual' => $cashInData,
            'expenseBudget' => $expenseBudgetData,
            'expenseActual' => $cashOutData,
            'totalRevenueBudget' => $totalRevenueBudget,
            'totalExpenseBudget' => $totalExpenseBudget,
        ];

        return Inertia::render('Dashboard', [
            'cashFlowData' => $cashFlowData,
            'varianceData' => $varianceData
        ]);
    }
}
