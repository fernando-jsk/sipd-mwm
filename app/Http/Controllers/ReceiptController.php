<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\ReceiptType;
use App\Models\AccountCode;
use App\Models\FundingSource;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::with(['type', 'subType', 'treasurer'])->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('payer_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $receipts = $query->paginate(15)->withQueryString();

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only('search', 'status')
        ]);
    }

    public function create()
    {
        $receiptTypes = ReceiptType::with('children')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
        // Get revenue account codes (starting with 4) that exist in RBA documents
        $accountCodes = AccountCode::whereHas('rbaDocuments')
            ->where('code', 'like', '4%')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $fundingSources = FundingSource::orderBy('name')->get();

        return Inertia::render('Receipts/CreateEdit', [
            'receiptTypes' => $receiptTypes,
            'accountCodes' => $accountCodes,
            'fundingSources' => $fundingSources,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'nullable|string|unique:receipts,document_number',
            'date' => 'required|date',
            'receipt_type_id' => 'required|exists:receipt_types,id',
            'receipt_sub_type_id' => 'nullable|exists:receipt_types,id',
            'description' => 'required|string',
            'payer_name' => 'required|string|max:255',
            'payment_method' => 'required|in:tunai,transfer,giro',
            'bank_name' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'bank_account_number' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            
            'details' => 'required|array|min:1',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.funding_source_id' => 'nullable|exists:funding_sources,id',
            'details.*.amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('receipts', 'public');
            }

            $receipt = Receipt::create([
                'document_number' => $validated['document_number'],
                'date' => $validated['date'],
                'receipt_type_id' => $validated['receipt_type_id'],
                'receipt_sub_type_id' => $validated['receipt_sub_type_id'] ?? null,
                'description' => $validated['description'],
                'payer_name' => $validated['payer_name'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
                'attachment_path' => $attachmentPath,
                'status' => 'draft',
                'treasurer_id' => Auth::id(), // assuming logged in user is the treasurer
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['details'] as $detail) {
                $receipt->details()->create([
                    'account_code_id' => $detail['account_code_id'],
                    'funding_source_id' => $detail['funding_source_id'] ?? null,
                    'amount' => $detail['amount'],
                ]);
            }
        });

        return redirect()->route('receipts.index')->with('message', 'Tanda bukti penerimaan berhasil dibuat.');
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['type', 'subType', 'treasurer', 'creator', 'details.accountCode', 'details.fundingSource']);
        
        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt
        ]);
    }

    public function edit(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return redirect()->route('receipts.index')->with('error', 'Hanya dokumen draft yang dapat diedit.');
        }

        $receipt->load(['details']);
        $receiptTypes = ReceiptType::with('children')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
        $accountCodes = AccountCode::whereHas('rbaDocuments')
            ->where('code', 'like', '4%')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $fundingSources = FundingSource::orderBy('name')->get();

        return Inertia::render('Receipts/CreateEdit', [
            'receipt' => $receipt,
            'receiptTypes' => $receiptTypes,
            'accountCodes' => $accountCodes,
            'fundingSources' => $fundingSources,
        ]);
    }

    public function update(Request $request, Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return redirect()->route('receipts.index')->with('error', 'Hanya dokumen draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'document_number' => 'nullable|string|unique:receipts,document_number,' . $receipt->id,
            'date' => 'required|date',
            'receipt_type_id' => 'required|exists:receipt_types,id',
            'receipt_sub_type_id' => 'nullable|exists:receipt_types,id',
            'description' => 'required|string',
            'payer_name' => 'required|string|max:255',
            'payment_method' => 'required|in:tunai,transfer,giro',
            'bank_name' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'bank_account_number' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            
            'details' => 'required|array|min:1',
            'details.*.id' => 'nullable|integer',
            'details.*.account_code_id' => 'required|exists:account_codes,id',
            'details.*.funding_source_id' => 'nullable|exists:funding_sources,id',
            'details.*.amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $request, $receipt) {
            $attachmentPath = $receipt->attachment_path;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $attachmentPath = $request->file('attachment')->store('receipts', 'public');
            }

            $receipt->update([
                'document_number' => $validated['document_number'],
                'date' => $validated['date'],
                'receipt_type_id' => $validated['receipt_type_id'],
                'receipt_sub_type_id' => $validated['receipt_sub_type_id'] ?? null,
                'description' => $validated['description'],
                'payer_name' => $validated['payer_name'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
                'attachment_path' => $attachmentPath,
            ]);

            $existingDetailIds = $receipt->details()->pluck('id')->toArray();
            $submittedDetailIds = [];

            foreach ($validated['details'] as $detail) {
                if (isset($detail['id']) && in_array($detail['id'], $existingDetailIds)) {
                    // Update existing
                    $receipt->details()->where('id', $detail['id'])->update([
                        'account_code_id' => $detail['account_code_id'],
                        'funding_source_id' => $detail['funding_source_id'] ?? null,
                        'amount' => $detail['amount'],
                    ]);
                    $submittedDetailIds[] = $detail['id'];
                } else {
                    // Create new
                    $newDetail = $receipt->details()->create([
                        'account_code_id' => $detail['account_code_id'],
                        'funding_source_id' => $detail['funding_source_id'] ?? null,
                        'amount' => $detail['amount'],
                    ]);
                    $submittedDetailIds[] = $newDetail->id;
                }
            }

            // Delete removed details
            $detailsToDelete = array_diff($existingDetailIds, $submittedDetailIds);
            if (!empty($detailsToDelete)) {
                $receipt->details()->whereIn('id', $detailsToDelete)->delete();
            }
        });

        return redirect()->route('receipts.index')->with('message', 'Tanda bukti penerimaan berhasil diperbarui.');
    }

    public function destroy(Receipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return redirect()->route('receipts.index')->with('error', 'Hanya dokumen draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($receipt) {
            if ($receipt->attachment_path) {
                Storage::disk('public')->delete($receipt->attachment_path);
            }
            $receipt->delete();
        });

        return redirect()->route('receipts.index')->with('message', 'Tanda bukti penerimaan berhasil dihapus.');
    }

    public function updateStatus(Request $request, Receipt $receipt)
    {
        $validated = $request->validate([
            'status' => 'required|in:submitted',
        ]);

        if ($receipt->status !== 'draft') {
            return redirect()->back()->with('error', 'Status tidak valid untuk diubah.');
        }

        $receipt->update([
            'status' => $validated['status']
        ]);

        return redirect()->back()->with('message', 'Status dokumen berhasil diperbarui menjadi Submitted.');
    }

    public function print(Receipt $receipt)
    {
        $receipt->load(['details.accountCode', 'details.fundingSource', 'type', 'subType', 'treasurer']);
        
        return Inertia::render('Receipts/Print', [
            'receipt' => $receipt
        ]);
    }
}
