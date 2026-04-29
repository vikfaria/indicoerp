<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'warning_by' => 'required|exists:users,id',
            'warning_type_id' => 'required|exists:warning_types,id',
            'subject' => 'required|max:255',
            'severity' => 'required',
            'warning_date' => 'required|date',
            'description' => 'nullable',
            'document' => 'nullable|string',

        ];
    }
}
