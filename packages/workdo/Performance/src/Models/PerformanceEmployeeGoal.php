<?php

namespace Workdo\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\Employee;

class PerformanceEmployeeGoal extends Model
{
    use HasFactory;

    protected $table = 'performance_employee_goals';

    protected $fillable = [
        'employee_id',
        'goal_type_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'target',
        'progress',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress' => 'decimal:2',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function goalType()
    {
        return $this->belongsTo(PerformanceGoalType::class, 'goal_type_id');
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target == 0) {
            return 0;
        }
        return min(100, ($this->progress / $this->target) * 100);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}