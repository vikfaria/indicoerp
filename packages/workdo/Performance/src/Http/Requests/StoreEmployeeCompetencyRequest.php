<?php

namespace Workdo\Performance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'competency_id' => 'required|exists:competencies,id',
            'rating' => 'required|numeric|min:0|max:10',
            'assessment_date' => 'required|date',
            'comments' => 'nullable|string'
        ];
    }
}