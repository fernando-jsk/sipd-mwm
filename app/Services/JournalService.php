<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalService
{
    /**
     * Store a new journal entry with its details.
     *
     * @param array $data
     * @return Journal
     * @throws Exception
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $this->validateBalance($data['details']);

            $journal = Journal::create([
                'date' => $data['date'],
                'reference_no' => $data['reference_no'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'general',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $this->saveDetails($journal, $data['details']);

            return $journal;
        });
    }

    /**
     * Update an existing journal entry.
     *
     * @param Journal $journal
     * @param array $data
     * @return Journal
     * @throws Exception
     */
    public function update(Journal $journal, array $data)
    {
        if ($journal->status === 'posted') {
            throw new Exception("Jurnal yang sudah di-posting tidak dapat diubah.");
        }

        return DB::transaction(function () use ($journal, $data) {
            $this->validateBalance($data['details']);

            $journal->update([
                'date' => $data['date'],
                'reference_no' => $data['reference_no'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'general',
            ]);

            // Remove old details and insert new ones
            $journal->details()->delete();
            $this->saveDetails($journal, $data['details']);

            return $journal;
        });
    }

    /**
     * Delete a journal entry.
     *
     * @param Journal $journal
     * @throws Exception
     */
    public function delete(Journal $journal)
    {
        if ($journal->status === 'posted') {
            throw new Exception("Jurnal yang sudah di-posting tidak dapat dihapus.");
        }

        $journal->delete();
    }

    /**
     * Post a journal entry.
     *
     * @param Journal $journal
     * @return Journal
     */
    public function post(Journal $journal)
    {
        $journal->update(['status' => 'posted']);
        
        activity('journal')
            ->performedOn($journal)
            ->log("Posting Jurnal Umum {$journal->reference_no}");
            
        return $journal;
    }

    /**
     * Validate that total debit equals total credit.
     *
     * @param array $details
     * @throws Exception
     */
    private function validateBalance(array $details)
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($details as $detail) {
            $totalDebit += (float) ($detail['debit'] ?? 0);
            $totalCredit += (float) ($detail['credit'] ?? 0);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new Exception("Total Debit (Rp " . number_format($totalDebit, 2) . ") tidak sama dengan Total Kredit (Rp " . number_format($totalCredit, 2) . "). Jurnal tidak seimbang (Not Balanced).");
        }
    }

    /**
     * Save journal details.
     *
     * @param Journal $journal
     * @param array $details
     */
    private function saveDetails(Journal $journal, array $details)
    {
        foreach ($details as $detail) {
            $debit = (float) ($detail['debit'] ?? 0);
            $credit = (float) ($detail['credit'] ?? 0);
            
            // Only save if either debit or credit is > 0
            if ($debit > 0 || $credit > 0) {
                $journal->details()->create([
                    'account_code_id' => $detail['account_code_id'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $detail['description'] ?? null,
                ]);
            }
        }
    }

    /**
     * Create automated journal for a Receipt.
     */
    public function createFromReceipt(\App\Models\Receipt $receipt)
    {
        $defaultKasId = \App\Models\Setting::where('key', 'default_receipt_account')->value('value');
        if (!$defaultKasId) {
            throw new Exception("Akun Kas Penerimaan Default belum diatur di Pengaturan Sistem.");
        }

        $totalAmount = $receipt->details()->sum('amount');
        if ($totalAmount <= 0) return null;

        $details = [];
        // Debit: Kas Bendahara Penerimaan
        $details[] = [
            'account_code_id' => $defaultKasId,
            'debit' => $totalAmount,
            'credit' => 0,
            'description' => 'Penerimaan Kas - ' . $receipt->document_number
        ];

        // Kredit: Akun-akun Pendapatan
        foreach ($receipt->details as $detail) {
            if (!$detail->account_code_id) {
                throw new Exception("Ada rincian penerimaan yang belum memiliki pemetaan Akun Pendapatan.");
            }
            $details[] = [
                'account_code_id' => $detail->account_code_id,
                'debit' => 0,
                'credit' => $detail->amount,
                'description' => $receipt->description
            ];
        }

        return DB::transaction(function () use ($receipt, $details) {
            $this->validateBalance($details);

            // Delete existing journal if any to prevent duplicates on status flip-flop
            $this->deleteRelatedJournal($receipt);

            $journal = $receipt->journal()->create([
                'date' => $receipt->date,
                'reference_no' => 'JRN-REC-' . $receipt->id . '-' . time(),
                'description' => 'Penerimaan otomatis dari dokumen: ' . ($receipt->document_number ?? $receipt->id),
                'type' => 'general',
                'status' => 'posted', // Langsung posted agar terkunci
                'created_by' => auth()->id() ?? 1,
            ]);

            $this->saveDetails($journal, $details);

            return $journal;
        });
    }

    /**
     * Create automated journal for an Expenditure.
     */
    public function createFromExpenditure(\App\Models\Expenditure $expenditure)
    {
        $defaultKasId = \App\Models\Setting::where('key', 'default_expenditure_account')->value('value');
        if (!$defaultKasId) {
            throw new Exception("Akun Kas Pengeluaran Default belum diatur di Pengaturan Sistem.");
        }

        $totalAmount = $expenditure->details()->sum('amount');
        if ($totalAmount <= 0) return null;

        $details = [];
        
        // Debit: Akun-akun Belanja
        foreach ($expenditure->details as $detail) {
            if (!$detail->account_code_id) {
                throw new Exception("Ada rincian pengeluaran yang belum memiliki pemetaan Akun Belanja.");
            }
            $details[] = [
                'account_code_id' => $detail->account_code_id,
                'debit' => $detail->amount,
                'credit' => 0,
                'description' => $expenditure->description
            ];
        }

        // Kredit: Kas Bendahara Pengeluaran
        $details[] = [
            'account_code_id' => $defaultKasId,
            'debit' => 0,
            'credit' => $totalAmount,
            'description' => 'Pengeluaran Kas - ' . $expenditure->document_number
        ];

        return DB::transaction(function () use ($expenditure, $details) {
            $this->validateBalance($details);

            // Delete existing journal if any
            $this->deleteRelatedJournal($expenditure);

            $journal = $expenditure->journal()->create([
                'date' => $expenditure->date,
                'reference_no' => 'JRN-EXP-' . $expenditure->id . '-' . time(),
                'description' => 'Pengeluaran otomatis dari dokumen: ' . ($expenditure->document_number ?? $expenditure->id),
                'type' => 'general',
                'status' => 'posted', // Langsung posted
                'created_by' => auth()->id() ?? 1,
            ]);

            $this->saveDetails($journal, $details);

            return $journal;
        });
    }

    /**
     * Delete related journal if the parent transaction is cancelled/rolled back.
     */
    public function deleteRelatedJournal($model)
    {
        if ($model->journal) {
            $model->journal->details()->delete();
            $model->journal->delete();
        }
    }
}
