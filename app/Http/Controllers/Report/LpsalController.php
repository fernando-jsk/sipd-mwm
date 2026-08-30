<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\LpsalService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LpsalController extends Controller
{
    protected $lpsalService;

    public function __construct(LpsalService $lpsalService)
    {
        $this->lpsalService = $lpsalService;
    }

    public function index(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));

        return Inertia::render('Reports/LPSAL/Index', [
            'year' => $year,
        ]);
    }

    public function data(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));

        $data = $this->lpsalService->getLpsalData($year);

        return response()->json($data);
    }
}
