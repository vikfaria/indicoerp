<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'max_days_per_year' => 'required|integer|min:1',
            'is_paid' => 'boolean',
            'color' => 'required',
            'description' => 'nullable',
        ];
    }
}
