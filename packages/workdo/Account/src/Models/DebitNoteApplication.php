<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteApplication extends Model
{
    protected $fillable = [
        'debit_note_id',
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

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class, 'debit_note_id');
    }



    public function payment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class, 'payment_id');
    }
}