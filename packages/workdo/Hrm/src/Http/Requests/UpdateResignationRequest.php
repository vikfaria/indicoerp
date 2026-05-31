<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResignationRequest extends FormRequest
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
            'last_working_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document' => 'nullable|string',
            'settlement_unused_leave_days' => 'nullable|numeric|min:0|max:365',
            'settlement_other_earnings_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'settlement_other_deductions_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'settlement_apply_indemnity' => 'nullable|boolean',
            'settlement_indemnity_days_per_year' => 'nullable|numeric|min:0|max:365',
        ];
    }
}
