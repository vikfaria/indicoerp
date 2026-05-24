<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCostLayer extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'stock_movement_id',
        'original_quantity', 'remaining_quantity', 'unit_cost',
        'entry_date', 'is_exhausted', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'original_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'entry_date' => 'date',
            'is_exhausted' => 'boolean',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_exhausted', false)->where('remaining_quantity', '>', 0);
    }
}
