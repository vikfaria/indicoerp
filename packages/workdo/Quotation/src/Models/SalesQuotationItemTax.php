<?php

namespace Workdo\Quotation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationItemTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'tax_name',
        'tax_rate',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesQuotationItem::class, 'item_id');
    }
}