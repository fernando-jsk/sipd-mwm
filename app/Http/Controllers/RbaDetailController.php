<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            'parent_id' => 'nullable|exists:rba_details,id',
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

        if ($rbaDetail->type === 'item') {
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

        $rbaDetail->update($validated);
        $rbaDetail->rbaDocument->recalculateTotalBudget();

        return redirect()->back()->with('message', 'Rincian RBA berhasil diperbarui.');
    }

    public function destroy(RbaDetail $rbaDetail)
    {
        $document = $rbaDetail->rbaDocument;
        // Because of cascadeOnDelete in DB, deleting a parent will delete its children.
        $rbaDetail->delete();
        $document->recalculateTotalBudget();
        
        return redirect()->back()->with('message', 'Rincian RBA berhasil dihapus.');
    }
}
