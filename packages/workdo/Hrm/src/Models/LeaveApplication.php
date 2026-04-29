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
        'total_days',
        'reason',
        'attachment',
        'status',
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
            'attachment' => 'string',
            'approved_at' => 'datetime'
        ];
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
}