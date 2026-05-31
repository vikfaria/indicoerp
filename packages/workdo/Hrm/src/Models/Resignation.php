<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Resignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'last_working_date',
        'reason',
        'description',
        'status',
        'document',
        'approved_by',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'legal_notice_required_days',
        'legal_notice_provided_days',
        'legal_notice_missing_days',
        'legal_notice_compliant',
        'settlement_base_salary_amount',
        'settlement_daily_salary_amount',
        'settlement_salary_until_exit_amount',
        'settlement_unused_leave_days',
        'settlement_unused_leave_amount',
        'settlement_other_earnings_amount',
        'settlement_other_deductions_amount',
        'settlement_apply_indemnity',
        'settlement_indemnity_days_per_year',
        'settlement_indemnity_years',
        'settlement_indemnity_amount',
        'settlement_gross_amount',
        'settlement_total_deductions_amount',
        'settlement_net_amount',
        'settlement_generated_at',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'last_working_date' => 'date',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'legal_notice_compliant' => 'boolean',
            'settlement_base_salary_amount' => 'decimal:2',
            'settlement_daily_salary_amount' => 'decimal:2',
            'settlement_salary_until_exit_amount' => 'decimal:2',
            'settlement_unused_leave_days' => 'decimal:2',
            'settlement_unused_leave_amount' => 'decimal:2',
            'settlement_other_earnings_amount' => 'decimal:2',
            'settlement_other_deductions_amount' => 'decimal:2',
            'settlement_apply_indemnity' => 'boolean',
            'settlement_indemnity_days_per_year' => 'decimal:2',
            'settlement_indemnity_years' => 'decimal:4',
            'settlement_indemnity_amount' => 'decimal:2',
            'settlement_gross_amount' => 'decimal:2',
            'settlement_total_deductions_amount' => 'decimal:2',
            'settlement_net_amount' => 'decimal:2',
            'settlement_generated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where(function ($statusQuery): void {
            $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
        });
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }
}
