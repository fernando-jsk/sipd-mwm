<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountCode;
use Inertia\Inertia;

class AccountCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountCode::query();
        
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
        }

        $accountCodes = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('AccountCodes/Index', [
            'accountCodes' => $accountCodes,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        return Inertia::render('AccountCodes/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:account_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $accountCode = AccountCode::create($validated);

        return redirect()->route('account-codes.index')->with('message', 'Kode rekening berhasil ditambahkan');
    }

    public function edit(AccountCode $accountCode)
    {
        return Inertia::render('AccountCodes/Edit', [
            'accountCode' => $accountCode
        ]);
    }

    public function update(Request $request, AccountCode $accountCode)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:account_codes,code,' . $accountCode->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $accountCode->update($validated);

        return redirect()->route('account-codes.index')->with('message', 'Kode rekening berhasil diperbarui');
    }

    public function destroy(AccountCode $accountCode)
    {
        $code = $accountCode->code;
        $accountCode->delete();

        return redirect()->route('account-codes.index')->with('message', 'Kode rekening berhasil dihapus');
    }
}
