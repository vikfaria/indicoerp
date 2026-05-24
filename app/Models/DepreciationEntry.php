<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    protected $fillable = [
        'fixed_asset_id', 'depreciation_date', 'fiscal_year', 'period_number',
        'depreciation_amount', 'accumulated_after', 'net_book_value_after',
        'journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'depreciation_amount' => 'decimal:2',
            'accumulated_after' => 'decimal:2',
            'net_book_value_after' => 'decimal:2',
            'period_number' => 'integer',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
