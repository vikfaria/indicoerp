<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSocialSecurityProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'inss_number',
        'registration_date',
        'registration_status',
        'identification_document_type',
        'identification_document_number',
        'evidence_file_path',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'registration_date' => 'date',
            'creator_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
