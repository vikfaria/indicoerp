<?php

namespace Workdo\Account\Listeners;

use App\Events\PostSalesInvoice;
use Workdo\Account\Services\JournalService;

class PostSalesInvoiceListener
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function handle(PostSalesInvoice $event)
    {
       if(Module_is_active('Account'))
       {
           if ($event->salesInvoice->type === 'product') {
               $this->journalService->createSalesInvoiceJournal($event->salesInvoice);
               $this->journalService->createSalesCOGSJournal($event->salesInvoice);
           } else {
               $this->journalService->createServiceInvoiceJournal($event->salesInvoice);
           }
       }
    }
}
