<?php

namespace Workdo\Retainer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetainerPaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'retainer_id',
        'allocated_amount',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RetainerPayment::class, 'payment_id');
    }

    public function retainer(): BelongsTo
    {
        return $this->belongsTo(Retainer::class, 'retainer_id');
    }
}
