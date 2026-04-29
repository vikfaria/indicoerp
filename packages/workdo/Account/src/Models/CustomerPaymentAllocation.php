<?php

namespace Workdo\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SalesInvoice;

class CustomerPaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'allocated_amount',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2'
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }
}