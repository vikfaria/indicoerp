<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'user_id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'termination_type_id' => [
                'required',
                'integer',
                Rule::exists('termination_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'notice_date' => 'required|date|before:termination_date',
            'termination_date' => 'required|date',
            'offboarding_letter_delivered_at' => 'nullable|date|after_or_equal:notice_date',
            'offboarding_assets_returned_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_access_revoked_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_final_payment_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_certificate_issued_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_inss_notified_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_migration_notified_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_archive_completed_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_completed_at' => 'nullable|date|after_or_equal:termination_date',
            'offboarding_notes' => 'nullable|string|max:5000',
            'settlement_unused_leave_days' => 'nullable|numeric|min:0|max:365',
            'settlement_other_earnings_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'settlement_other_deductions_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'settlement_apply_indemnity' => 'nullable|boolean',
            'settlement_indemnity_days_per_year' => 'nullable|numeric|min:0|max:365',
            'reason' => 'required|max:255',
            'description' => 'nullable',
            'document' => 'nullable|string',
        ];
    }
}
