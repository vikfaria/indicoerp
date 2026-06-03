<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'employee_id',
        'total_days',
        'hours',
        'rate',
        'start_date',
        'end_date',
        'notes',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'creator_id',
        'created_by',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_days' => 'integer',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }
}
