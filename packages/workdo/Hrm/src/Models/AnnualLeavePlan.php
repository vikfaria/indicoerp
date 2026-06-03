<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualLeavePlan extends Model
{
    use HasFactory;

    public const STATUS_PENDING_MANAGER = 'pending_manager';
    public const STATUS_PENDING_HR = 'pending_hr';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_year',
        'planned_start_date',
        'planned_end_date',
        'planned_days',
        'status',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'manager_approved_by',
        'manager_approved_at',
        'hr_approved_by',
        'hr_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected $casts = [
        'leave_year' => 'integer',
        'planned_days' => 'integer',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }
}
