<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'last_working_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document' => 'nullable|string',
        ];
    }
}