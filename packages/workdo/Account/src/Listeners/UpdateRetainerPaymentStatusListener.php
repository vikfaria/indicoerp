<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\BankTransactionsService;
use Workdo\Retainer\Events\UpdateRetainerPaymentStatus;
use Workdo\Account\Services\JournalService;

class UpdateRetainerPaymentStatusListener
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle(UpdateRetainerPaymentStatus $event)
    {
        if ($event->request->status === 'cleared') {
            $event->retainerPayment->loadMissing('bankAccount.glAccount', 'customer', 'allocations.retainer');

            $this->journalService->createRetainerPaymentJournal($event->retainerPayment);
            $this->bankTransactionsService->createRetainerPayment($event->retainerPayment);

            foreach ($event->retainerPayment->allocations as $allocation) {
                $retainer = $allocation->retainer;

                if (!$retainer) {
                    continue;
                }

                $retainer->balance_amount = max(0, (float) $retainer->balance_amount - (float) $allocation->allocated_amount);
                $retainer->status = $retainer->balance_amount <= 0.01 ? 'paid' : 'partial';
                $retainer->save();
            }
        }
    }
}
