<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteApplication extends Model
{
    protected $fillable = [
        'credit_note_id',
        'payment_id',
        'applied_amount',
        'application_date',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'applied_amount' => 'decimal:2',
        'application_date' => 'date'
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\Workdo\Account\Models\CustomerPayment::class, 'payment_id');
    }
}