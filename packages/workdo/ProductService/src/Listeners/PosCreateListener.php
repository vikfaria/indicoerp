<?php

namespace Workdo\ProductService\Listeners;

use Workdo\Pos\Events\CreatePos;
use Workdo\ProductService\Models\WarehouseStock;

class PosCreateListener
{
    public function handle(CreatePos $event)
    {
        $posSale = $event->posSale;
        foreach ($posSale->items()->get() as $item) {
            $stock = WarehouseStock::where('warehouse_id', $posSale->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($stock) {
                $stock->decrement('quantity', $item->quantity);
            }
        }
    }
}
