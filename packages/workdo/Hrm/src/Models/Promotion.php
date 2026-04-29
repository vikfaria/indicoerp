<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'previous_branch_id',
        'previous_department_id',
        'previous_designation_id',
        'current_branch_id',
        'current_department_id',
        'current_designation_id',
        'effective_date',
        'reason',
        'document',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }

    public function previousBranch()
    {
        return $this->belongsTo(Branch::class, 'previous_branch_id');
    }

    public function previousDepartment()
    {
        return $this->belongsTo(Department::class, 'previous_department_id');
    }

    public function previousDesignation()
    {
        return $this->belongsTo(Designation::class, 'previous_designation_id');
    }

    public function currentBranch()
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function currentDepartment()
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    public function currentDesignation()
    {
        return $this->belongsTo(Designation::class, 'current_designation_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}