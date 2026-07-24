<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReceiptType;
use Inertia\Inertia;

class ReceiptTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ReceiptType::with('children')->whereNull('parent_id');
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('children', function($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $receiptTypes = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('ReceiptTypes/Index', [
            'receiptTypes' => $receiptTypes,
            'filters' => $request->only('search')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:receipt_types,id'
        ]);

        ReceiptType::create($validated);

        return redirect()->back()->with('message', 'Jenis penerimaan berhasil ditambahkan');
    }

    public function update(Request $request, ReceiptType $receiptType)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:receipt_types,id'
        ]);

        $receiptType->update($validated);

        return redirect()->back()->with('message', 'Jenis penerimaan berhasil diperbarui');
    }

    public function destroy(ReceiptType $receiptType)
    {
        try {
            $receiptType->delete();
            return redirect()->back()->with('message', 'Jenis penerimaan berhasil dihapus');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") {
                return redirect()->back()->with('error', 'Gagal dihapus: Jenis penerimaan ini sudah digunakan pada transaksi penerimaan.');
            }
            throw $e;
        }
    }
}
