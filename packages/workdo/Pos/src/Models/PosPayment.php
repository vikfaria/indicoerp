<?php

namespace Workdo\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    protected $fillable = [
        'pos_id',
        'discount',
        'amount',
        'discount_amount',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }
}