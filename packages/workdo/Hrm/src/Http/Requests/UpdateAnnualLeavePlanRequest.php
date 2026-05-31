<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnualLeavePlanRequest extends FormRequest
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
            'leave_year' => 'required|integer|min:2000|max:3000',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date|after_or_equal:planned_start_date',
            'notes' => 'nullable|string|max:5000',
        ];
    }
}
