<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\AccountCode;
use App\Models\RbaDetail;
use App\Models\RbaDocument;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RbaDetailController extends Controller
{
    public function builder(Request $request, RbaDocument $rbaDocument)
    {
        $rbaDocument->load(['accountCode', 'pptk']);
        
        // Fetch all rba details for this document, order by ID to maintain insertion order
        $rbaDetails = RbaDetail::where('rba_document_id', $rbaDocument->id)
            ->orderBy('id', 'asc')
            ->get();

        $fundingSources = \App\Models\FundingSource::orderBy('name')->get(['id', 'name', 'code']);
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Rba/Builder', [
            'rbaDocument' => $rbaDocument,
            'rbaDetails' => $rbaDetails,
            'fundingSources' => $fundingSources,
            'users' => $users
        ]);
    }

    public function store(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('rba_details', 'id')->where(function ($query) use ($rbaDocument) {
                    return $query->where('rba_document_id', $rbaDocument->id)
                                 ->where('type', 'header');
                }),
            ],
            'type' => 'required|in:header,item',
            'uraian' => 'required|string|max:255',
            'vol_1' => 'nullable|numeric',
            'satuan_1' => 'nullable|string',
            'vol_2' => 'nullable|numeric',
            'satuan_2' => 'nullable|string',
            'vol_3' => 'nullable|numeric',
            'satuan_3' => 'nullable|string',
            'vol_4' => 'nullable|numeric',
            'satuan_4' => 'nullable|string',
            'harga' => 'nullable|numeric',
        ]);
        
        // Calculate koefisien and jumlah if it's an item
        if ($validated['type'] === 'item') {
            $koefisien = 1;
            $satuanArray = [];
            
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($validated["vol_{$i}"])) {
                    $koefisien *= $validated["vol_{$i}"];
                    if (!empty($validated["satuan_{$i}"])) {
                        $satuanArray[] = $validated["satuan_{$i}"];
                    }
                }
            }
            
            $validated['koefisien'] = $koefisien;
            $validated['satuan'] = implode('/', $satuanArray);
            $validated['jumlah'] = $koefisien * ($validated['harga'] ?? 0);
        }

        $validated['rba_document_id'] = $rbaDocument->id;

        RbaDetail::create($validated);
        $rbaDocument->recalculateTotalBudget();

        return redirect()->back()->with('message', 'Rincian RBA berhasil ditambahkan.');
    }

    public function update(Request $request, RbaDetail $rbaDetail)
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('rba_details', 'id')->where(function ($query) use ($rbaDetail) {
                    return $query->where('rba_document_id', $rbaDetail->rba_document_id)
                                 ->where('type', 'header');
                }),
            ],
            'uraian' => 'sometimes|required|string|max:255',
            'vol_1' => 'nullable|numeric',
            'satuan_1' => 'nullable|string',
            'vol_2' => 'nullable|numeric',
            'satuan_2' => 'nullable|string',
            'vol_3' => 'nullable|numeric',
            'satuan_3' => 'nullable|string',
            'vol_4' => 'nullable|numeric',
            'satuan_4' => 'nullable|string',
            'harga' => 'nullable|numeric',
        ]);

        if ($request->has('parent_id') && $request->parent_id !== null) {
            if ($request->parent_id == $rbaDetail->id) {
                throw ValidationException::withMessages(['parent_id' => 'Tidak dapat memilih diri sendiri sebagai induk.']);
            }
            $descendants = $this->getDescendantHeaderIds($rbaDetail->id, $rbaDetail->rba_document_id);
            if (in_array($request->parent_id, $descendants)) {
                throw ValidationException::withMessages(['parent_id' => 'Tidak dapat memindahkan grup ke dalam turunannya sendiri.']);
            }
        }

        if ($rbaDetail->type === 'item') {
            if ($request->has('vol_1') || $request->has('harga')) {
                $koefisien = 1;
                $satuanArray = [];
                
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($validated["vol_{$i}"])) {
                        $koefisien *= $validated["vol_{$i}"];
                        if (!empty($validated["satuan_{$i}"])) {
                            $satuanArray[] = $validated["satuan_{$i}"];
                        }
                    }
                }
                
                $validated['koefisien'] = $koefisien;
                $validated['satuan'] = implode('/', $satuanArray);
                $validated['jumlah'] = $koefisien * ($validated['harga'] ?? 0);
            }
        }

        $rbaDetail->update($validated);
        $rbaDetail->rbaDocument->recalculateTotalBudget();

        return redirect()->back()->with('message', 'Rincian RBA berhasil diperbarui.');
    }

    private function getDescendantHeaderIds(int $targetId, int $documentId): array
    {
        // Ambil HANYA baris bertipe header dalam 1 query efisien (hanya belasan baris, bukan ribuan item)
        $headers = RbaDetail::where('rba_document_id', $documentId)
            ->where('type', 'header')
            ->get(['id', 'parent_id']);

        $childrenMap = [];
        foreach ($headers as $header) {
            $childrenMap[$header->parent_id][] = $header->id;
        }

        $descendants = [];
        $collect = function ($id) use (&$collect, &$descendants, $childrenMap) {
            if (!empty($childrenMap[$id])) {
                foreach ($childrenMap[$id] as $childId) {
                    $descendants[] = $childId;
                    $collect($childId);
                }
            }
        };
        $collect($targetId);

        return $descendants;
    }

    public function destroy(RbaDetail $rbaDetail)
    {
        $document = $rbaDetail->rbaDocument;
        // Because of cascadeOnDelete in DB, deleting a parent will delete its children.
        $rbaDetail->delete();
        $document->recalculateTotalBudget();
        
        return redirect()->back()->with('message', 'Rincian RBA berhasil dihapus.');
    }

    public function bulkChangeParent(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'detail_ids' => 'required|array|min:1',
            'detail_ids.*' => [
                'required',
                Rule::exists('rba_details', 'id')->where('rba_document_id', $rbaDocument->id),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('rba_details', 'id')->where(function ($query) use ($rbaDocument) {
                    return $query->where('rba_document_id', $rbaDocument->id)
                                 ->where('type', 'header');
                }),
            ],
        ]);

        $detailIds = array_map('intval', $validated['detail_ids']);
        $targetParentId = !empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($targetParentId !== null) {
            // Induk tujuan tidak boleh merupakan salah satu dari rincian yang dipilih
            if (in_array($targetParentId, $detailIds)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Induk tujuan tidak boleh merupakan salah satu dari rincian yang dipilih.',
                ]);
            }

            // Induk tujuan tidak boleh merupakan turunan dari header yang dipilih
            foreach ($detailIds as $id) {
                $descendants = $this->getDescendantHeaderIds($id, $rbaDocument->id);
                if (in_array($targetParentId, $descendants)) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Induk tujuan tidak boleh merupakan turunan dari rincian grup yang dipilih.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($detailIds, $targetParentId, $rbaDocument) {
            $details = RbaDetail::whereIn('id', $detailIds)
                ->where('rba_document_id', $rbaDocument->id)
                ->get();

            foreach ($details as $detail) {
                $detail->update(['parent_id' => $targetParentId]);
            }

            $rbaDocument->recalculateTotalBudget();
        });

        return redirect()->back()->with('message', count($detailIds) . ' rincian berhasil dipindahkan.');
    }

    public function bulkDestroy(Request $request, RbaDocument $rbaDocument)
    {
        $validated = $request->validate([
            'detail_ids' => 'required|array|min:1',
            'detail_ids.*' => [
                'required',
                Rule::exists('rba_details', 'id')->where('rba_document_id', $rbaDocument->id),
            ],
        ]);

        $detailIds = array_map('intval', $validated['detail_ids']);

        DB::transaction(function () use ($detailIds, $rbaDocument) {
            $details = RbaDetail::whereIn('id', $detailIds)
                ->where('rba_document_id', $rbaDocument->id)
                ->get();

            foreach ($details as $detail) {
                $detail->delete();
            }

            $rbaDocument->recalculateTotalBudget();
        });

        return redirect()->back()->with('message', count($detailIds) . ' rincian berhasil dihapus.');
    }
}
