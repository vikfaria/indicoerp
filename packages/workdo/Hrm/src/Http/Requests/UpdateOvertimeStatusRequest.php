<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOvertimeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'max:5000', 'required_if:status,rejected'],
        ];
    }
}
