<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'termination_type_id' => 'required|exists:termination_types,id',
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
            'reason' => 'required|max:255',
            'description' => 'nullable',
            'document' => 'nullable|string',
        ];
    }
}
