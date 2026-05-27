<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Workdo\Hrm\Models\LeaveType;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'legal_code' => ['nullable', Rule::in(LeaveType::LEGAL_CODES)],
            'max_days_per_year' => 'required|integer|min:1',
            'is_paid' => 'boolean',
            'requires_supporting_document' => 'boolean',
            'must_be_consecutive' => 'boolean',
            'fixed_duration_days' => 'nullable|integer|min:1',
            'min_advance_notice_days' => 'nullable|integer|min:0',
            'pre_event_start_window_days' => 'nullable|integer|min:0',
            'post_event_start_offset_days' => 'nullable|integer|min:0',
            'allow_cash_out' => 'boolean',
            'min_effective_rest_days' => 'nullable|integer|min:1',
            'color' => 'required',
            'description' => 'nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_paid' => $this->boolean('is_paid'),
            'requires_supporting_document' => $this->boolean('requires_supporting_document'),
            'must_be_consecutive' => $this->boolean('must_be_consecutive'),
            'allow_cash_out' => $this->boolean('allow_cash_out'),
            'legal_code' => $this->input('legal_code') !== '' ? $this->input('legal_code') : null,
        ]);
    }
}
