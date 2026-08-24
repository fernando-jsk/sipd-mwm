<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\RbaDocument;
use App\Services\LakService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LakController extends Controller
{
    protected $lakService;

    public function __construct(LakService $lakService)
    {
        $this->lakService = $lakService;
    }

    public function index()
    {
        // Get available budget years
        $years = RbaDocument::select('budget_year')
            ->distinct()
            ->orderBy('budget_year', 'desc')
            ->pluck('budget_year')
            ->toArray();

        // If no year exists, default to current year
        $activeYear = count($years) > 0 ? $years[0] : date('Y');

        return Inertia::render('Reports/LAK/Index', [
            'years' => $years,
            'defaultYear' => $activeYear
        ]);
    }

    public function data(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $period = $request->input('period');

        $data = $this->lakService->getLakData($year, $period);

        return response()->json($data);
    }
}
