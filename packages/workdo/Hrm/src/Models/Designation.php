<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation_name',
        'branch_id',
        'department_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [];
    }



    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
