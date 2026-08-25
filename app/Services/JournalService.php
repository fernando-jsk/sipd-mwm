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
}
