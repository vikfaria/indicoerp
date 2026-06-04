<?php

namespace Workdo\Account\Listeners;

use App\Events\PostSalesInvoice;
use App\Services\InventoryCostingService;
use Workdo\Account\Services\JournalService;

class PostSalesInvoiceListener
{
    protected $journalService;
    protected InventoryCostingService $inventoryCostingService;

    public function __construct(JournalService $journalService, InventoryCostingService $inventoryCostingService)
    {
        $this->journalService = $journalService;
        $this->inventoryCostingService = $inventoryCostingService;
    }

    public function handle(PostSalesInvoice $event)
    {
       if(Module_is_active('Account'))
       {
           if ($event->salesInvoice->type === 'product') {
               $salesInvoice = $event->salesInvoice->loadMissing('items');
               $companyId = (int) ($salesInvoice->created_by ?? creatorId());
               $warehouseCode = $salesInvoice->warehouse_id ? (string) $salesInvoice->warehouse_id : null;

               foreach ($salesInvoice->items as $item) {
                   $quantity = (float) $item->quantity;
                   if ($quantity <= 0) {
                       continue;
                   }

                   $this->inventoryCostingService->recordSale(
                       $companyId,
                       (int) $item->product_id,
                       $quantity,
                       (string) ($salesInvoice->invoice_date ?? now()->toDateString()),
                       'sales_invoice',
                       (int) $salesInvoice->id,
                       $warehouseCode,
                       false
                   );
               }

               $this->journalService->createSalesInvoiceJournal($event->salesInvoice);
               $this->journalService->createSalesCOGSJournal($event->salesInvoice);
           } else {
               $this->journalService->createServiceInvoiceJournal($event->salesInvoice);
           }
       }
    }
}
