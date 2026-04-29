<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItemTax extends Model
{
    protected $fillable = [
        'item_id',
        'tax_name',
        'tax_rate'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CreditNoteItem::class, 'item_id');
    }
}