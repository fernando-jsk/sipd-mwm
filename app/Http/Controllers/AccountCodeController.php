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

        $accountCodes = $query->orderBy('code')->paginate(20)->withQueryString();

        return Inertia::render('AccountCodes/Index', [
            'accountCodes' => $accountCodes,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        $parents = AccountCode::orderBy('code')->get(['id', 'code', 'name', 'level']);
        return Inertia::render('AccountCodes/Create', [
            'parents' => $parents
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:account_codes,id',
            'level' => 'required|integer|min:1|max:6',
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
        $parents = AccountCode::where('id', '!=', $accountCode->id)->orderBy('code')->get(['id', 'code', 'name', 'level']);
        return Inertia::render('AccountCodes/Edit', [
            'accountCode' => $accountCode,
            'parents' => $parents
        ]);
    }

    public function update(Request $request, AccountCode $accountCode)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:account_codes,id',
            'level' => 'required|integer|min:1|max:6',
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
        try {
            $code = $accountCode->code;
            $accountCode->delete();
            return redirect()->route('account-codes.index')->with('message', 'Kode rekening berhasil dihapus');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") { // Integrity constraint violation
                return redirect()->route('account-codes.index')->with('error', 'Gagal dihapus: Kode Rekening ini sudah memiliki rincian RBA atau masih memiliki sub-rekening.');
            }
            throw $e;
        }
    }
}
