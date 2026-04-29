<?php

namespace Workdo\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class PerformanceGoalType extends Model
{
    use HasFactory;

    protected $table = 'performance_goal_types';

    protected $fillable = [
        'name',
        'description',
        'status',
        'creator_id',
        'created_by',
    ];

    public function employeeGoals()
    {
        return $this->hasMany(PerformanceEmployeeGoal::class, 'goal_type_id');
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