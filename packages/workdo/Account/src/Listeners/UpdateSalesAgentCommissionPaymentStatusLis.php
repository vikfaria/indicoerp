<?php

namespace Workdo\Account\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;

class UpdateSalesAgentCommissionPaymentStatusLis
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function handle($event)
    {
        if(Module_is_active('Account'))
        {
            $this->bankTransactionsService->createUpdateSalesAgentCommissionPayment($event->payment);
            $this->journalService->createUpdateSalesAgentCommissionPaymentJournal($event->payment);
        }
    }
}
