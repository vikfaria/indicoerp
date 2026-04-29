<?php

namespace Workdo\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'training_type_id',
        'trainer_id',
        'branch_id',
        'department_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'max_participants',
        'cost',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function tasks()
    {
        return $this->hasMany(TrainingTask::class);
    }
}