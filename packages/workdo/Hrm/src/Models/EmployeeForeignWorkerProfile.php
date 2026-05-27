<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeForeignWorkerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'is_foreign_worker',
        'nationality',
        'residency_status',
        'passport_number',
        'passport_expires_at',
        'visa_type',
        'visa_expires_at',
        'work_authorization_number',
        'work_authorization_expires_at',
        'hiring_regime',
        'work_province',
        'mozambique_entry_date',
        'cessation_effective_date',
        'cessation_notification_due_at',
        'cessation_notified_at',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'is_foreign_worker' => 'boolean',
            'passport_expires_at' => 'date',
            'visa_expires_at' => 'date',
            'work_authorization_expires_at' => 'date',
            'mozambique_entry_date' => 'date',
            'cessation_effective_date' => 'date',
            'cessation_notification_due_at' => 'date',
            'cessation_notified_at' => 'date',
            'creator_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
