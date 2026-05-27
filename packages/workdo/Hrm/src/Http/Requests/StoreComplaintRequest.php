<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'against_employee_id' => 'required|exists:users,id',
            'complaint_type_id' => 'required|exists:complaint_types,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'complaint_date' => 'required|date',
            'is_confidential' => 'nullable|boolean',
            'is_harassment_report' => 'nullable|boolean',
            'confidential_channel' => 'nullable|string|max:60|required_if:is_confidential,1',
            'confidentiality_level' => ['nullable', 'string', Rule::in(['internal', 'restricted', 'anonymous'])],
            'handling_owner_id' => 'nullable|exists:users,id',
            'investigation_started_at' => 'nullable|date|after_or_equal:complaint_date',
            'investigation_closed_at' => 'nullable|date|after_or_equal:investigation_started_at',
            'document' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_confidential' => $this->boolean('is_confidential'),
            'is_harassment_report' => $this->boolean('is_harassment_report'),
        ]);
    }
}
