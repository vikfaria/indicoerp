<?php

namespace Workdo\ProductService\Listeners;

use App\Events\CompleteSalesReturn;
use Workdo\ProductService\Models\WarehouseStock;

class CompleteSalesReturnListener
{
    public function handle(CompleteSalesReturn $event)
    {
        $salesReturn = $event->salesReturn;
        foreach ($salesReturn->items()->get() as $item) {
            $stock = WarehouseStock::where('warehouse_id', $salesReturn->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($stock) {
                $stock->increment('quantity', $item->return_quantity);
            } else {
                WarehouseStock::create([
                    'warehouse_id' => $salesReturn->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->return_quantity
                ]);
            }
        }
    }
}