<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertEmployeeForeignWorkerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_foreign_worker' => 'required|boolean',
            'nationality' => 'nullable|string|max:120|required_if:is_foreign_worker,1',
            'residency_status' => 'nullable|string|in:resident,non_resident',
            'passport_number' => 'nullable|string|max:120',
            'passport_expires_at' => 'nullable|date',
            'visa_type' => 'nullable|string|max:80',
            'visa_expires_at' => 'nullable|date',
            'work_authorization_number' => 'nullable|string|max:120',
            'work_authorization_expires_at' => 'nullable|date',
            'hiring_regime' => 'nullable|string|in:quota,authorization,large_project,short_term,special',
            'work_province' => 'nullable|string|max:120',
            'mozambique_entry_date' => 'nullable|date',
            'cessation_effective_date' => 'nullable|date',
            'cessation_notification_due_at' => 'nullable|date|after_or_equal:cessation_effective_date',
            'cessation_notified_at' => 'nullable|date|after_or_equal:cessation_effective_date',
        ];
    }
}
