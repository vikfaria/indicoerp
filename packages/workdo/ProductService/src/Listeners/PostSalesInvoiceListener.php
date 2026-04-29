<?php

namespace Workdo\ProductService\Listeners;

use App\Events\PostSalesInvoice;
use Workdo\ProductService\Models\WarehouseStock;

class PostSalesInvoiceListener
{
    public function handle(PostSalesInvoice $event)
    {
        $salesInvoice = $event->salesInvoice;
        
        if ($salesInvoice->type === 'product') {
            foreach ($salesInvoice->items()->get() as $item) {
                $stock = WarehouseStock::where('warehouse_id', $salesInvoice->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $item->quantity);
                }
            }
        }
    }
}
