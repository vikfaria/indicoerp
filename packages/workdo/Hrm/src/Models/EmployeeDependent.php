<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDependent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'full_name',
        'relationship',
        'date_of_birth',
        'document_number',
        'is_student',
        'is_tax_eligible',
        'valid_until',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'date_of_birth' => 'date',
            'is_student' => 'boolean',
            'is_tax_eligible' => 'boolean',
            'valid_until' => 'date',
            'creator_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
