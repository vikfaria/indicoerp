<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;

class EmployeeTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_date',
        'effective_date',
        'reason',
        'status',
        'document',
        'employee_id',
        'from_branch_id',
        'from_department_id',
        'from_designation_id',
        'to_branch_id',
        'to_department_id',
        'to_designation_id',
        'approved_by',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'effective_date' => 'date',
            'document' => 'string'
        ];
    }



    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function from_branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function from_department()
    {
        return $this->belongsTo(Department::class);
    }

    public function from_designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function to_branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function to_department()
    {
        return $this->belongsTo(Department::class);
    }

    public function to_designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class,'approved_by','id');
    }
}