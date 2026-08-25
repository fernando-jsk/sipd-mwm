<?php

namespace App\Observers;

use App\Models\Receipt;
use App\Services\JournalService;

class ReceiptObserver
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Handle the Receipt "created" event.
     */
    public function created(Receipt $receipt): void
    {
        if ($receipt->status === 'submitted') {
            $this->journalService->createFromReceipt($receipt);
        }
    }

    /**
     * Handle the Receipt "updated" event.
     */
    public function updated(Receipt $receipt): void
    {
        // If status changed to submitted
        if ($receipt->isDirty('status') && $receipt->status === 'submitted') {
            $this->journalService->createFromReceipt($receipt);
        }
        
        // If status changed FROM submitted TO something else (rollback)
        if ($receipt->isDirty('status') && $receipt->getOriginal('status') === 'submitted' && $receipt->status !== 'submitted') {
            $this->journalService->deleteRelatedJournal($receipt);
        }
    }

    /**
     * Handle the Receipt "deleted" event.
     */
    public function deleted(Receipt $receipt): void
    {
        $this->journalService->deleteRelatedJournal($receipt);
    }
}
