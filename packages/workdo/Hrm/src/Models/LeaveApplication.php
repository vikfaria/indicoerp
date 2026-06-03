<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\LeaveType;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'legal_reference_date',
        'total_days',
        'compensated_days',
        'effective_rest_days',
        'reason',
        'attachment',
        'status',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'approver_comment',
        'approved_at',
        'employee_id',  
        'leave_type_id',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'legal_reference_date' => 'date',
            'compensated_days' => 'integer',
            'effective_rest_days' => 'integer',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'attachment' => 'string',
            'approved_at' => 'datetime'
        ];
    }

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }



    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leave_type()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class,'approved_by','id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
