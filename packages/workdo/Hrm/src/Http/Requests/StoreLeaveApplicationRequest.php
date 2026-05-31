<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveApplicationRequest extends FormRequest
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
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
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
