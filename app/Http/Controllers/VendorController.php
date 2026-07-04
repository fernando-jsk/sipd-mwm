<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('director_name', 'like', '%' . $request->search . '%')
                  ->orWhere('npwp', 'like', '%' . $request->search . '%');
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $vendors = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'type'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Vendors/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:PT,CV,UD,Koperasi,Perorangan',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'director_name' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('message', 'Data rekanan berhasil ditambahkan');
    }

    public function edit(Vendor $vendor)
    {
        return Inertia::render('Vendors/Edit', [
            'vendor' => $vendor
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:PT,CV,UD,Koperasi,Perorangan',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'director_name' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('message', 'Data rekanan berhasil diperbarui');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('message', 'Data rekanan berhasil dihapus');
    }
}
