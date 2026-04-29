<?php

namespace Workdo\ProductService\Listeners;

use App\Events\PostPurchaseInvoice;
use Workdo\ProductService\Models\WarehouseStock;

class PostPurchaseInvoiceListener
{
    public function handle(PostPurchaseInvoice $event)
    {
        $purchaseInvoice = $event->purchaseInvoice;
        foreach ($purchaseInvoice->items()->get() as $item) {
            $stock = WarehouseStock::where('warehouse_id', $purchaseInvoice->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($stock) {
                $stock->increment('quantity', $item->quantity);
            } else {
                WarehouseStock::create([
                    'warehouse_id' => $purchaseInvoice->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity
                ]);
            }
        }
    }
}
