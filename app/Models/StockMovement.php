<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id', 'product_id', 'movement_type', 'movement_date',
        'quantity', 'unit_cost', 'total_cost', 'running_quantity', 'running_value',
        'reference_type', 'reference_id', 'warehouse_code', 'notes',
        'journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'running_quantity' => 'decimal:4',
            'running_value' => 'decimal:2',
        ];
    }

    public function costLayers()
    {
        return $this->hasMany(StockCostLayer::class);
    }
}
