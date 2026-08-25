<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\AccountCode;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class JournalController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = Journal::with(['createdBy'])->latest('date');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $journals = $query->paginate(15)->withQueryString();

        return Inertia::render('Journals/Index', [
            'journals' => $journals,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function create()
    {
        $accountCodes = AccountCode::where('is_active', true)->orderBy('code')->get();

        $nextId = Journal::max('id') + 1;
        $defaultRef = 'JU-' . date('Ym') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Journals/CreateEdit', [
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
            'type' => 'required|in:opening_balance,general,adjustment,closing',
            'details' => 'required|array|min:2',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.debit' => 'numeric|min:0',
            'details.*.credit' => 'numeric|min:0',
            'details.*.description' => 'nullable|string'
        ]);

        try {
            $this->journalService->store($validated);
            return redirect()->route('journals.index')->with('success', 'Jurnal berhasil disimpan.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Journal $journal)
    {
        $journal->load('details.accountCode');

        if ($journal->status === 'posted') {
            return redirect()->route('journals.index')->with('error', 'Jurnal yang sudah di-posting tidak dapat diubah.');
        }

        $accountCodes = AccountCode::where('is_active', true)->orderBy('code')->get();

        return Inertia::render('Journals/CreateEdit', [
            'journal' => $journal,
            'accountCodes' => $accountCodes
        ]);
    }

    public function update(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reference_no' => 'required|string|unique:journals,reference_no,' . $journal->id,
            'description' => 'nullable|string',
            'type' => 'required|in:opening_balance,general,adjustment,closing',
            'details' => 'required|array|min:2',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.debit' => 'numeric|min:0',
            'details.*.credit' => 'numeric|min:0',
            'details.*.description' => 'nullable|string'
        ]);

        try {
            $this->journalService->update($journal, $validated);
            return redirect()->route('journals.index')->with('success', 'Jurnal berhasil diperbarui.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Journal $journal)
    {
        try {
            $this->journalService->delete($journal);
            return redirect()->route('journals.index')->with('success', 'Jurnal berhasil dihapus.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Journal $journal)
    {
        $journal->load(['details.accountCode', 'createdBy']);
        return Inertia::render('Journals/Show', [
            'journal' => $journal
        ]);
    }

    public function post(Journal $journal)
    {
        try {
            $this->journalService->post($journal);
            return back()->with('success', 'Jurnal berhasil di-posting.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
