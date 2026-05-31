<?php

namespace Workdo\Training\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'compliance_code' => [
                'nullable',
                'required_if:is_mandatory,1',
                'string',
                'max:50',
                Rule::in([
                    'safety_health',
                    'equipment_usage',
                    'conduct_harassment',
                    'compliance',
                    'data_protection',
                    'onboarding',
                ]),
            ],
            'certificate_validity_days' => 'nullable|integer|min:1|max:3650',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
        ];
    }
}
