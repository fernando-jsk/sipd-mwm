<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\BalanceSheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RbaDocument;

class BalanceSheetController extends Controller
{
    protected $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
    }

    public function index()
    {
        // Get available budget years to display in the UI filter
        $years = RbaDocument::select('budget_year')
            ->distinct()
            ->orderBy('budget_year', 'desc')
            ->pluck('budget_year')
            ->toArray();

        $activeYear = count($years) > 0 ? $years[0] : date('Y');

        return Inertia::render('Reports/BalanceSheet/Index', [
            'years' => $years,
            'defaultYear' => $activeYear
        ]);
    }

    public function data(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));

        $data = $this->balanceSheetService->getBalanceSheetData($date);

        return response()->json($data);
    }
}
