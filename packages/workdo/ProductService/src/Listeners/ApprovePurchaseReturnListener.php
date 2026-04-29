<?php

namespace Workdo\ProductService\Listeners;

use App\Events\ApprovePurchaseReturn;
use Workdo\ProductService\Models\WarehouseStock;

class ApprovePurchaseReturnListener
{
    public function handle(ApprovePurchaseReturn $event)
    {
        $return = $event->return;
        foreach ($return->items()->get() as $item) {
            $stock = WarehouseStock::where('warehouse_id', $return->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($stock) {
                $stock->decrement('quantity', $item->return_quantity);
            }
        }
    }
}
