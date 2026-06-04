<?php

namespace Workdo\Account\Listeners;

use App\Events\PostPurchaseInvoice;
use App\Services\InventoryCostingService;
use Workdo\Account\Services\JournalService;

class PostPurchaseInvoiceListener
{
    protected $journalService;
    protected InventoryCostingService $inventoryCostingService;

    public function __construct(JournalService $journalService, InventoryCostingService $inventoryCostingService)
    {
        $this->journalService = $journalService;
        $this->inventoryCostingService = $inventoryCostingService;
    }

    public function handle(PostPurchaseInvoice $event)
    {
       if(Module_is_active('Account'))
       {
           $purchaseInvoice = $event->purchaseInvoice->loadMissing('items');
           $companyId = (int) ($purchaseInvoice->created_by ?? creatorId());
           $warehouseCode = $purchaseInvoice->warehouse_id ? (string) $purchaseInvoice->warehouse_id : null;

           foreach ($purchaseInvoice->items as $item) {
               $quantity = (float) $item->quantity;
               if ($quantity <= 0) {
                   continue;
               }

               $netLineAmount = max(0, ((float) $item->unit_price * $quantity) - (float) ($item->discount_amount ?? 0));
               $unitCost = round($netLineAmount / $quantity, 4);

               $this->inventoryCostingService->recordPurchase(
                   $companyId,
                   (int) $item->product_id,
                   $quantity,
                   $unitCost,
                   (string) ($purchaseInvoice->invoice_date ?? now()->toDateString()),
                   'purchase_invoice',
                   (int) $purchaseInvoice->id,
                   $warehouseCode,
                   false
               );
           }

           $this->journalService->createPurchaseInventoryJournal($event->purchaseInvoice);
       }
    }
}
