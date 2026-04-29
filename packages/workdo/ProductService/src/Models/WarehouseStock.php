<?php

namespace Workdo\ProductService\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id', 
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }
}