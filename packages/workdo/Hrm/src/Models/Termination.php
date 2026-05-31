<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Workdo\Hrm\Models\TerminationType;

class Termination extends Model
{
    use HasFactory;

    protected $fillable = [
        'notice_date',
        'termination_date',
        'offboarding_letter_delivered_at',
        'offboarding_assets_returned_at',
        'offboarding_access_revoked_at',
        'offboarding_final_payment_at',
        'offboarding_certificate_issued_at',
        'offboarding_inss_notified_at',
        'offboarding_migration_notified_at',
        'offboarding_archive_completed_at',
        'offboarding_completed_at',
        'offboarding_notes',
        'reason',
        'description',
        'document',
        'employee_id',
        'termination_type_id',
        'status',
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
            'notice_date' => 'date',
            'termination_date' => 'date',
            'offboarding_letter_delivered_at' => 'date',
            'offboarding_assets_returned_at' => 'date',
            'offboarding_access_revoked_at' => 'date',
            'offboarding_final_payment_at' => 'date',
            'offboarding_certificate_issued_at' => 'date',
            'offboarding_inss_notified_at' => 'date',
            'offboarding_migration_notified_at' => 'date',
            'offboarding_archive_completed_at' => 'date',
            'offboarding_completed_at' => 'date',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'document' => 'string',
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
        return $this->belongsTo(User::class);
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
