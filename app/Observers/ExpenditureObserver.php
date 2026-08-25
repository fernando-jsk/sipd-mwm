<?php

namespace App\Observers;

use App\Models\Expenditure;
use App\Services\JournalService;

class ExpenditureObserver
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Handle the Expenditure "created" event.
     */
    public function created(Expenditure $expenditure): void
    {
        if ($expenditure->status === 'disbursed') {
            $this->journalService->createFromExpenditure($expenditure);
        }
    }

    /**
     * Handle the Expenditure "updated" event.
     */
    public function updated(Expenditure $expenditure): void
    {
        // If status changed to disbursed
        if ($expenditure->isDirty('status') && $expenditure->status === 'disbursed') {
            $this->journalService->createFromExpenditure($expenditure);
        }

        // If status changed FROM disbursed TO something else (rollback)
        if ($expenditure->isDirty('status') && $expenditure->getOriginal('status') === 'disbursed' && $expenditure->status !== 'disbursed') {
            $this->journalService->deleteRelatedJournal($expenditure);
        }
    }

    /**
     * Handle the Expenditure "deleted" event.
     */
    public function deleted(Expenditure $expenditure): void
    {
        $this->journalService->deleteRelatedJournal($expenditure);
    }
}
