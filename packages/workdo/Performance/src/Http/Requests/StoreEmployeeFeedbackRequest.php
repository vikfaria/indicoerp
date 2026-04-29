<?php

namespace Workdo\Performance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'feedback_type_id' => 'required|exists:feedback_types,id',
            'date' => 'required|date',
            'content' => 'required|string',
            'visibility' => 'in:private,team,public'
        ];
    }
}