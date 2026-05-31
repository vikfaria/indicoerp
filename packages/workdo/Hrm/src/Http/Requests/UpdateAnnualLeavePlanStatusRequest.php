<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnualLeavePlanStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['manager_approve', 'hr_approve', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:5000', 'required_if:action,reject'],
        ];
    }
}
