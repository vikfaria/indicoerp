<?php

namespace Workdo\Account\Listeners;

use Workdo\Account\Services\JournalService;
use Workdo\Retainer\Events\ConvertSalesRetainer;

class ConvertSalesRetainerListener
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function handle(ConvertSalesRetainer $event)
    {
        $this->journalService->createSalesInvoiceJournal($event->invoice);
        $this->journalService->createSalesRetainerToInvoiceJournal($event->retainer);
        $this->journalService->createSalesCOGSJournal($event->invoice);

        $event->retainer->status = 'converted';
        $event->retainer->save();
    }
}
