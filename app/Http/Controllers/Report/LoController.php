<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\LoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoController extends Controller
{
    protected $loService;

    public function __construct(LoService $loService)
    {
        $this->loService = $loService;
    }

    public function index(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));

        return Inertia::render('Reports/LO/Index', [
            'year' => $year,
        ]);
    }

    public function data(Request $request)
    {
        $year = $request->query('year', session('active_year', date('Y')));

        $data = $this->loService->getLoData($year);

        return response()->json($data);
    }
}
