<?php

namespace Workdo\Account\Listeners;

use App\Events\PostPurchaseInvoice;
use Workdo\Account\Services\JournalService;

class PostPurchaseInvoiceListener
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function handle(PostPurchaseInvoice $event)
    {
       if(Module_is_active('Account'))
       {
           $this->journalService->createPurchaseInventoryJournal($event->purchaseInvoice);
       }
    }
}
