<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealTask extends Model
{
    protected $fillable = [
        'deal_id',
        'name',
        'date',
        'time',
        'priority',
        'status',
        'created_by',
        'creator_id'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i'
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
    public static $priorities = [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
    ];
    public static $status = [
        0 => 'On Going',
        1 => 'Completed'
    ];
}