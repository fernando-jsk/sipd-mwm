<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $vendors = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => Vendor::count(),
            'corporate' => Vendor::whereIn('type', ['PT', 'CV', 'UD', 'Koperasi'])->count(),
            'individual' => Vendor::where('type', 'Perorangan')->count(),
            'active' => Vendor::where('is_active', true)->count(),
        ];

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status', 'completeness']),
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $vendors = $query->orderBy('name', 'asc')->get();

        $filename = 'data-rekanan-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($vendors) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header
            fputcsv($file, [
                'No',
                'Nama Rekanan',
                'Bentuk Usaha',
                'Nama Pimpinan',
                'NPWP',
                'Nama Bank',
                'Nomor Rekening',
                'Nama Pemilik Rekening',
                'Nomor Telepon',
                'Alamat',
                'Status',
            ]);

            foreach ($vendors as $index => $v) {
                fputcsv($file, [
                    $index + 1,
                    $v->name,
                    $v->type,
                    $v->director_name ?? '-',
                    $v->npwp ? "'" . $v->npwp : '-',
                    $v->bank_name ?? '-',
                    $v->bank_account_number ? "'" . $v->bank_account_number : '-',
                    $v->bank_account_name ?? '-',
                    $v->phone ? "'" . $v->phone : '-',
                    $v->address ?? '-',
                    $v->is_active ? 'Aktif' : 'Nonaktif',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = Vendor::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('director_name', 'like', "%{$search}%")
                  ->orWhere('npwp', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account_number', 'like', "%{$search}%")
                  ->orWhere('bank_account_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('completeness') && $request->completeness !== 'all') {
            if ($request->completeness === 'no_account') {
                $query->where(function ($q) {
                    $q->whereNull('bank_account_number')
                      ->orWhere('bank_account_number', '');
                });
            } elseif ($request->completeness === 'no_npwp') {
                $query->where(function ($q) {
                    $q->whereNull('npwp')
                      ->orWhere('npwp', '');
                });
            }
        }

        return $query;
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
        if ($vendor->expenditures()->exists()) {
            return redirect()->route('vendors.index')->with('error', 'Rekanan "' . $vendor->name . '" tidak dapat dihapus karena sudah memiliki riwayat transaksi SPPD / Pengeluaran. Anda dapat mengubah statusnya menjadi Nonaktif.');
        }

        $vendor->delete();

        return redirect()->route('vendors.index')->with('message', 'Data rekanan berhasil dihapus');
    }
}
