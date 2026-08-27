<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Models\RbaDocument;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RbaDocumentController extends Controller
{
    public function index(Request $request)
    {
        $budgetYear = $request->session()->get('active_budget_year', date('Y'));

        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        $activeVersionName = RbaDocument::where('budget_year', $budgetYear)->where('version', $activeVersion)->value('version_name') ?? 'Induk';

        $isPendapatan = $request->routeIs('rba.pendapatan');
        $rbaType = $isPendapatan ? 'Pendapatan' : 'Belanja';
        $prefix = $isPendapatan ? '4' : '5';

        // 1. Get ALL account codes and their associated document for the active year and active version
        $allAccounts = AccountCode::where('code', 'like', $prefix . '%')
            ->with(['rbaDocuments' => function ($query) use ($budgetYear, $activeVersion) {
                $query->where('budget_year', $budgetYear)->where('version', $activeVersion);
            }])
            ->orderBy('code')
            ->get();

        // Ambil data realisasi berdasarkan tipe (Pendapatan/Belanja)
        $realizations = [];
        if ($isPendapatan) {
            $realizations = \Illuminate\Support\Facades\DB::table('receipt_details')
                ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
                ->where('receipts.status', 'submitted')
                ->whereYear('receipts.date', $budgetYear)
                ->select('receipt_details.account_code_id', \Illuminate\Support\Facades\DB::raw('SUM(receipt_details.amount) as total'))
                ->groupBy('receipt_details.account_code_id')
                ->pluck('total', 'account_code_id')->toArray();
        } else {
            $realizations = \Illuminate\Support\Facades\DB::table('expenditure_details')
                ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
                ->where('expenditures.status', 'disbursed')
                ->whereYear('expenditures.date', $budgetYear)
                ->select('expenditure_details.account_code_id', \Illuminate\Support\Facades\DB::raw('SUM(expenditure_details.amount) as total'))
                ->groupBy('expenditure_details.account_code_id')
                ->pluck('total', 'account_code_id')->toArray();
        }

        // 2. Build Tree and Aggregate Totals
        $map = [];
        $roots = [];
        
        foreach ($allAccounts as $acc) {
            $doc = $acc->rbaDocuments->first();
            $map[$acc->id] = $acc->toArray();
            $map[$acc->id]['children'] = [];
            
            // Map the document properties to the node
            $map[$acc->id]['rba_document_id'] = $doc ? $doc->id : null;
            $map[$acc->id]['tree_jumlah'] = $doc ? (float) $doc->total_budget : 0;
            $map[$acc->id]['tree_has_rba'] = $doc ? true : false;
            
            // Map realisasi
            $map[$acc->id]['tree_realisasi'] = isset($realizations[$acc->id]) ? (float) $realizations[$acc->id] : 0;
            $map[$acc->id]['tree_sisa_pagu'] = 0; // calculated later
        }

        foreach ($map as $id => &$node) {
            if ($node['parent_id'] !== null && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }

        // Recursive function to aggregate totals bottom-up and determine tree_has_rba
        $aggregateFn = function (&$nodes) use (&$aggregateFn) {
            $totalSum = 0;
            $totalRealisasi = 0;
            $anyHasRba = false;

            foreach ($nodes as &$node) {
                $childTotals = 0;
                $childRealisasi = 0;
                $childHasRba = false;

                if (!empty($node['children'])) {
                    $result = $aggregateFn($node['children']);
                    $childTotals = $result['sum'];
                    $childRealisasi = $result['realisasi'];
                    $childHasRba = $result['hasRba'];
                }

                $node['tree_jumlah'] += $childTotals;
                $node['tree_realisasi'] += $childRealisasi;
                $node['tree_sisa_pagu'] = $node['tree_jumlah'] - $node['tree_realisasi'];
                $node['tree_has_rba'] = $node['tree_has_rba'] || $childHasRba;

                $totalSum += $node['tree_jumlah'];
                $totalRealisasi += $node['tree_realisasi'];
                $anyHasRba = $anyHasRba || $node['tree_has_rba'];
            }

            return ['sum' => $totalSum, 'realisasi' => $totalRealisasi, 'hasRba' => $anyHasRba];
        };

        $aggregateFn($roots);

        // Recursive function to prune nodes where tree_has_rba is false
        $pruneFn = function ($nodes) use (&$pruneFn) {
            $pruned = [];
            foreach ($nodes as $node) {
                if ($node['tree_has_rba']) {
                    if (!empty($node['children'])) {
                        $node['children'] = $pruneFn($node['children']);
                    }
                    $pruned[] = $node;
                }
            }
            return $pruned;
        };

        $activeTree = $pruneFn($roots);

        // 3. Get Leaf accounts for the Add Modal search
        // Exclude those that already have a document for this year and active version!
        $leafAccounts = AccountCode::where('code', 'like', $prefix . '%')
            ->whereDoesntHave('children')
            ->whereDoesntHave('rbaDocuments', function ($query) use ($budgetYear, $activeVersion) {
                $query->where('budget_year', $budgetYear)->where('version', $activeVersion);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $fundingSources = \App\Models\FundingSource::orderBy('name')->get(['id', 'name']);
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Rba/Index', [
            'activeTree' => $activeTree,
            'leafAccounts' => $leafAccounts,
            'currentVersion' => $activeVersion,
            'currentVersionName' => $activeVersionName,
            'fundingSources' => $fundingSources,
            'users' => $users,
            'rbaType' => $rbaType
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_code_id' => 'required|exists:account_codes,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'pptk_id' => 'required|exists:users,id'
        ]);

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));

        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        $activeVersionName = RbaDocument::where('budget_year', $budgetYear)->where('version', $activeVersion)->value('version_name') ?? 'Induk';

        // Ensure not already created
        $exists = RbaDocument::where('account_code_id', $request->account_code_id)
            ->where('budget_year', $budgetYear)
            ->where('version', $activeVersion)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Dokumen RBA untuk rekening ini sudah ada.');
        }

        RbaDocument::create([
            'account_code_id' => $request->account_code_id,
            'funding_source_id' => $request->funding_source_id,
            'pptk_id' => $request->pptk_id,
            'budget_year' => $budgetYear,
            'version' => $activeVersion,
            'version_name' => $activeVersionName,
            'status' => 'draft',
            'total_budget' => 0
        ]);

        return back()->with('message', 'Dokumen RBA berhasil ditambahkan.');
    }

    public function update(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'funding_source_id' => 'required|exists:funding_sources,id',
            'pptk_id' => 'required|exists:users,id'
        ]);

        $rbaDocument->update($validated);

        return back()->with('message', 'Pengaturan dokumen RBA berhasil diperbarui.');
    }

    public function destroy(RbaDocument $rbaDocument)
    {
        $rbaDocument->delete();
        return back()->with('message', 'Dokumen RBA beserta seluruh rinciannya berhasil dihapus.');
    }
}
