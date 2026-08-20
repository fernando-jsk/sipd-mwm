<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\RbaDocument;
use App\Services\LraService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LraController extends Controller
{
    protected $lraService;

    public function __construct(LraService $lraService)
    {
        $this->lraService = $lraService;
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

        return Inertia::render('Reports/LRA/Index', [
            'years' => $years,
            'defaultYear' => $activeYear
        ]);
    }

    public function data(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $version = $request->input('version');

        $data = $this->lraService->getLraData($year, $month, $version);

        return response()->json($data);
    }
}
