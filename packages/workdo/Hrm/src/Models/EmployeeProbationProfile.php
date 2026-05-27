<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProbationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'probation_category',
        'starts_at',
        'expected_end_at',
        'legal_max_days',
        'evaluation_status',
        'technical_score',
        'attendance_score',
        'punctuality_score',
        'conduct_score',
        'adaptation_score',
        'recommendation',
        'decision_status',
        'decision_date',
        'cessation_reason',
        'notes',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'starts_at' => 'date',
            'expected_end_at' => 'date',
            'legal_max_days' => 'integer',
            'technical_score' => 'integer',
            'attendance_score' => 'integer',
            'punctuality_score' => 'integer',
            'conduct_score' => 'integer',
            'adaptation_score' => 'integer',
            'decision_date' => 'date',
            'creator_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
