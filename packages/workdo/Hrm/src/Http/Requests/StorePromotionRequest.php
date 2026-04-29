<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'current_branch_id' => 'required|exists:branches,id',
            'current_department_id' => 'required|exists:departments,id',
            'current_designation_id' => 'required|exists:designations,id',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string',
            'document' => 'nullable|string',
        ];
    }
}