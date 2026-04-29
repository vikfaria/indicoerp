<?php

namespace Workdo\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class PosItem extends Model
{
    protected $fillable = [
        'pos_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'tax_ids',
        'tax_amount',
        'total_amount',
        'creator_id',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'tax_ids' => 'array',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2'
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_id');
    }
}