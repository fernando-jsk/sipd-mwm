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

        $rbaViewType = $request->query('rba_view_type', 'gelondongan');

        // 1. Ambil hanya dokumen RBA aktif untuk tahun, versi, tipe dokumen, dan prefix jenisnya
        $rbaDocs = RbaDocument::join('account_codes', 'rba_documents.account_code_id', '=', 'account_codes.id')
            ->where('account_codes.code', 'like', $prefix . '%')
            ->where('rba_documents.budget_year', $budgetYear)
            ->where('rba_documents.version', $activeVersion)
            ->where('rba_documents.rba_type', $rbaViewType)
            ->select(
                'rba_documents.id',
                'rba_documents.account_code_id',
                'rba_documents.funding_source_id',
                'rba_documents.pptk_id',
                'rba_documents.total_budget',
                'rba_documents.mapped_to_rba_id'
            )
            ->get();

        $docMap = [];
        foreach ($rbaDocs as $doc) {
            $docMap[$doc->account_code_id] = $doc;
        }

        $activeAccountIds = array_keys($docMap);
        $activeTree = [];

        if (!empty($activeAccountIds)) {
            // 2. Kumpulkan hanya ID akun yang aktif beserta seluruh ancestor (parent) ke atas
            $neededAccountIds = $activeAccountIds;
            $currentIds = $activeAccountIds;
            while (!empty($currentIds)) {
                $parentIds = \Illuminate\Support\Facades\DB::table('account_codes')
                    ->whereIn('id', $currentIds)
                    ->whereNotNull('parent_id')
                    ->pluck('parent_id')
                    ->unique()
                    ->toArray();
                $currentIds = array_diff($parentIds, $neededAccountIds);
                $neededAccountIds = array_merge($neededAccountIds, $currentIds);
            }

            // Ambil data rekening hanya yang dibutuhkan (ringan & instan)
            $rawAccounts = \Illuminate\Support\Facades\DB::table('account_codes')
                ->whereIn('id', $neededAccountIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'parent_id', 'level', 'description']);

            // Ambil data realisasi hanya untuk akun yang dibutuhkan
            $realizations = [];
            if ($isPendapatan) {
                $realizations = \Illuminate\Support\Facades\DB::table('receipt_details')
                    ->join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
                    ->where('receipts.status', 'submitted')
                    ->whereYear('receipts.date', $budgetYear)
                    ->whereIn('receipt_details.account_code_id', $neededAccountIds)
                    ->select('receipt_details.account_code_id', \Illuminate\Support\Facades\DB::raw('SUM(receipt_details.amount) as total'))
                    ->groupBy('receipt_details.account_code_id')
                    ->pluck('total', 'account_code_id')->toArray();
            } else {
                $realizations = \Illuminate\Support\Facades\DB::table('expenditure_details')
                    ->join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
                    ->where('expenditures.status', 'disbursed')
                    ->whereYear('expenditures.date', $budgetYear)
                    ->whereIn('expenditure_details.account_code_id', $neededAccountIds)
                    ->select('expenditure_details.account_code_id', \Illuminate\Support\Facades\DB::raw('SUM(expenditure_details.amount) as total'))
                    ->groupBy('expenditure_details.account_code_id')
                    ->pluck('total', 'account_code_id')->toArray();
            }

            $map = [];
            $roots = [];

            foreach ($rawAccounts as $acc) {
                $doc = $docMap[$acc->id] ?? null;
                $map[$acc->id] = (array) $acc;
                $map[$acc->id]['children'] = [];
                $map[$acc->id]['rba_document_id'] = $doc ? $doc->id : null;
                $map[$acc->id]['funding_source_id'] = $doc ? $doc->funding_source_id : null;
                $map[$acc->id]['pptk_id'] = $doc ? $doc->pptk_id : null;
                $map[$acc->id]['tree_jumlah'] = $doc ? (float) $doc->total_budget : 0;
                $map[$acc->id]['tree_has_rba'] = $doc ? true : false;
                $map[$acc->id]['mapped_to_rba_id'] = $doc ? $doc->mapped_to_rba_id : null;
                $map[$acc->id]['tree_realisasi'] = isset($realizations[$acc->id]) ? (float) $realizations[$acc->id] : 0;
                $map[$acc->id]['tree_sisa_pagu'] = 0;
            }

            foreach ($map as $id => &$node) {
                if ($node['parent_id'] !== null && isset($map[$node['parent_id']])) {
                    $map[$node['parent_id']]['children'][] = &$node;
                } else {
                    $roots[] = &$node;
                }
            }

            // Agregasi jumlah & realisasi dari anak ke induk secara rekursif
            $aggregateFn = function (&$nodes) use (&$aggregateFn) {
                $totalSum = 0;
                $totalRealisasi = 0;
                foreach ($nodes as &$node) {
                    $childTotals = 0;
                    $childRealisasi = 0;
                    if (!empty($node['children'])) {
                        $res = $aggregateFn($node['children']);
                        $childTotals = $res['sum'];
                        $childRealisasi = $res['realisasi'];
                    }
                    $node['tree_jumlah'] += $childTotals;
                    $node['tree_realisasi'] += $childRealisasi;
                    $node['tree_sisa_pagu'] = $node['tree_jumlah'] - $node['tree_realisasi'];
                    $totalSum += $node['tree_jumlah'];
                    $totalRealisasi += $node['tree_realisasi'];
                }
                return ['sum' => $totalSum, 'realisasi' => $totalRealisasi];
            };

            $aggregateFn($roots);
            $activeTree = $roots;
        }

        // 3. Leaf accounts untuk modal Tambah & Ganti Rekening (Query direct DB cepat)
        $leafAccounts = \Illuminate\Support\Facades\DB::table('account_codes as a')
            ->where('a.code', 'like', $prefix . '%')
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('account_codes as c')
                    ->whereColumn('c.parent_id', 'a.id');
            })
            ->whereNotExists(function ($q) use ($budgetYear, $activeVersion, $rbaViewType) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('rba_documents as r')
                    ->whereColumn('r.account_code_id', 'a.id')
                    ->where('r.budget_year', $budgetYear)
                    ->where('r.version', $activeVersion)
                    ->where('r.rba_type', $rbaViewType);
            })
            ->orderBy('a.code')
            ->get(['a.id', 'a.code', 'a.name']);

        $fundingSources = \App\Models\FundingSource::orderBy('name')->get(['id', 'name']);
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        $gelondonganDocs = [];
        if ($rbaViewType === 'rinci') {
            $gelondonganDocs = RbaDocument::join('account_codes', 'rba_documents.account_code_id', '=', 'account_codes.id')
                ->where('rba_documents.budget_year', $budgetYear)
                ->where('rba_documents.version', $activeVersion)
                ->where('rba_documents.rba_type', 'gelondongan')
                ->select('rba_documents.id', 'account_codes.code', 'account_codes.name')
                ->get();
        }

        return Inertia::render('Rba/Index', [
            'activeTree' => $activeTree,
            'leafAccounts' => $leafAccounts,
            'currentVersion' => $activeVersion,
            'currentVersionName' => $activeVersionName,
            'fundingSources' => $fundingSources,
            'users' => $users,
            'rbaType' => $rbaType,
            'rbaViewType' => $rbaViewType,
            'gelondonganDocs' => $gelondonganDocs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_code_id' => 'required|exists:account_codes,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'pptk_id' => 'required|exists:users,id',
            'rba_type' => 'required|in:gelondongan,rinci',
            'mapped_to_rba_id' => 'required_if:rba_type,rinci|nullable|exists:rba_documents,id',
        ]);

        $budgetYear = $request->session()->get('active_budget_year', date('Y'));

        $activeVersion = (int) (Setting::where('key', "rba_active_version_{$budgetYear}")->value('value') ?? 0);
        $activeVersionName = RbaDocument::where('budget_year', $budgetYear)->where('version', $activeVersion)->value('version_name') ?? 'Induk';

        // Ensure not already created
        $exists = RbaDocument::where('account_code_id', $request->account_code_id)
            ->where('budget_year', $budgetYear)
            ->where('version', $activeVersion)
            ->where('rba_type', $request->rba_type)
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
            'total_budget' => 0,
            'rba_type' => $request->rba_type,
            'mapped_to_rba_id' => $request->mapped_to_rba_id,
        ]);

        return back()->with('message', 'Dokumen RBA berhasil ditambahkan.');
    }

    public function update(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'account_code_id' => [
                'nullable',
                'exists:account_codes,id',
                function ($attribute, $value, $fail) use ($rbaDocument) {
                    if ($value && $value != $rbaDocument->account_code_id) {
                        // Harus merupakan rekening level terakhir (tidak memiliki sub rekening)
                        $hasChildren = AccountCode::where('parent_id', $value)->exists();
                        if ($hasChildren) {
                            $fail('Rekening yang dipilih harus merupakan rekening rincian (level terakhir).');
                        }

                        // Harus memiliki prefix jenis yang sama (Belanja atau Pendapatan)
                        $targetAccount = AccountCode::find($value);
                        $currentAccount = $rbaDocument->accountCode;
                        if ($targetAccount && $currentAccount) {
                            $currentPrefix = substr($currentAccount->code, 0, 1);
                            $targetPrefix = substr($targetAccount->code, 0, 1);
                            if ($currentPrefix !== $targetPrefix) {
                                $fail('Rekening baru harus memiliki jenis yang sama (Pendapatan/Belanja).');
                            }
                        }

                        // Pastikan belum digunakan oleh dokumen RBA lain pada tahun dan versi yang sama
                        $query = RbaDocument::where('account_code_id', $value)
                            ->where('budget_year', $rbaDocument->budget_year)
                            ->where('version', $rbaDocument->version)
                            ->where('id', '!=', $rbaDocument->id);
                        if (isset($rbaDocument->rba_type)) {
                            $query->where('rba_type', $rbaDocument->rba_type);
                        }
                        $exists = $query->exists();

                        if ($exists) {
                            $fail('Dokumen RBA untuk rekening tujuan sudah ada pada tahun anggaran ini.');
                        }
                    }
                }
            ],
            'funding_source_id' => 'sometimes|required|exists:funding_sources,id',
            'pptk_id' => 'sometimes|required|exists:users,id',
            'mapped_to_rba_id' => 'nullable|exists:rba_documents,id',
        ]);

        $updateData = [];
        if (!empty($validated['account_code_id'])) {
            $updateData['account_code_id'] = $validated['account_code_id'];
        }
        if (isset($validated['funding_source_id'])) {
            $updateData['funding_source_id'] = $validated['funding_source_id'];
        }
        if (isset($validated['pptk_id'])) {
            $updateData['pptk_id'] = $validated['pptk_id'];
        }
        if (array_key_exists('mapped_to_rba_id', $validated)) {
            $updateData['mapped_to_rba_id'] = $validated['mapped_to_rba_id'];
        }

        $rbaDocument->update($updateData);

        return back()->with('message', 'Dokumen RBA berhasil diperbarui.');
    }

    public function destroy(RbaDocument $rbaDocument)
    {
        $rbaDocument->delete();
        return back()->with('message', 'Dokumen RBA beserta seluruh rinciannya berhasil dihapus.');
    }
}
