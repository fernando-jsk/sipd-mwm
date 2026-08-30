<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\LpeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LpeController extends Controller
{
    protected $lpeService;

    public function __construct(LpeService $lpeService)
    {
        $this->lpeService = $lpeService;
    }

    public function index(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));

        return Inertia::render('Reports/LPE/Index', [
            'year' => $year,
        ]);
    }

    public function data(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));
        
        $data = $this->lpeService->getLpeData($year);

        return response()->json($data);
    }
}
