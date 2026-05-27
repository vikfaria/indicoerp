<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'legal_reference_date' => 'nullable|date',
            'compensated_days' => 'nullable|integer|min:0',
            'reason' => 'required|string',
            'attachment' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'compensated_days' => $this->input('compensated_days') !== null && $this->input('compensated_days') !== ''
                ? (int) $this->input('compensated_days')
                : 0,
        ]);
    }
}
