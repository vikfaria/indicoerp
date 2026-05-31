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
        'is_mandatory',
        'compliance_code',
        'certificate_validity_days',
        'branch_id',
        'department_id',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'certificate_validity_days' => 'integer',
        ];
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
