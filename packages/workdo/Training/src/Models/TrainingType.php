<?php

namespace Workdo\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;

class TrainingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'department_id',
        'creator_id',
        'created_by',
    ];    

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}