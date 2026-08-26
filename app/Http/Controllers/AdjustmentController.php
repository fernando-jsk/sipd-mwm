<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\AccountCode;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class AdjustmentController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = Journal::with(['createdBy'])->latest('date')
                    ->where('type', 'adjustment');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $adjustments = $query->paginate(15)->withQueryString();

        return Inertia::render('Adjustments/Index', [
            'adjustments' => $adjustments,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        $accountCodes = AccountCode::where('is_active', true)->orderBy('code')->get();

        $nextId = Journal::max('id') + 1;
        $defaultRef = 'JP-' . date('Ym') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Adjustments/CreateEdit', [
            'accountCodes' => $accountCodes,
            'defaultRef' => $defaultRef
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reference_no' => 'required|string|unique:journals,reference_no',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.debit' => 'numeric|min:0',
            'details.*.credit' => 'numeric|min:0',
            'details.*.description' => 'nullable|string'
        ]);

        $validated['type'] = 'adjustment';

        try {
            $this->journalService->store($validated);
            return redirect()->route('adjustments.index')->with('success', 'Jurnal Penyesuaian berhasil disimpan.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Journal $adjustment)
    {
        $adjustment->load('details.accountCode');

        if ($adjustment->type !== 'adjustment') {
            return redirect()->route('adjustments.index')->with('error', 'Data ini bukan Jurnal Penyesuaian.');
        }

        if ($adjustment->status === 'posted') {
            return redirect()->route('adjustments.index')->with('error', 'Jurnal Penyesuaian yang sudah di-posting tidak dapat diubah.');
        }

        $accountCodes = AccountCode::where('is_active', true)->orderBy('code')->get();

        return Inertia::render('Adjustments/CreateEdit', [
            'adjustment' => $adjustment,
            'accountCodes' => $accountCodes
        ]);
    }

    public function update(Request $request, Journal $adjustment)
    {
        if ($adjustment->type !== 'adjustment') {
            return redirect()->route('adjustments.index')->with('error', 'Data ini bukan Jurnal Penyesuaian.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reference_no' => 'required|string|unique:journals,reference_no,' . $adjustment->id,
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.debit' => 'numeric|min:0',
            'details.*.credit' => 'numeric|min:0',
            'details.*.description' => 'nullable|string'
        ]);

        $validated['type'] = 'adjustment';

        try {
            $this->journalService->update($adjustment, $validated);
            return redirect()->route('adjustments.index')->with('success', 'Jurnal Penyesuaian berhasil diperbarui.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Journal $adjustment)
    {
        if ($adjustment->type !== 'adjustment') {
            return redirect()->route('adjustments.index')->with('error', 'Data ini bukan Jurnal Penyesuaian.');
        }

        try {
            $this->journalService->delete($adjustment);
            return redirect()->route('adjustments.index')->with('success', 'Jurnal Penyesuaian berhasil dihapus.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Journal $adjustment)
    {
        if ($adjustment->type !== 'adjustment') {
            return redirect()->route('adjustments.index')->with('error', 'Data ini bukan Jurnal Penyesuaian.');
        }

        $adjustment->load(['details.accountCode', 'createdBy']);
        return Inertia::render('Adjustments/Show', [
            'adjustment' => $adjustment
        ]);
    }

    public function post(Journal $adjustment)
    {
        if ($adjustment->type !== 'adjustment') {
            return redirect()->route('adjustments.index')->with('error', 'Data ini bukan Jurnal Penyesuaian.');
        }

        try {
            $this->journalService->post($adjustment);
            return back()->with('success', 'Jurnal Penyesuaian berhasil di-posting.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
