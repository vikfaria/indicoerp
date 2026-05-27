<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertEmployeeSocialSecurityProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inss_number' => 'nullable|string|max:80',
            'registration_date' => 'nullable|date',
            'registration_status' => 'required|string|in:pending,registered,suspended,inactive',
            'identification_document_type' => 'nullable|string|max:80',
            'identification_document_number' => 'nullable|string|max:120',
            'evidence_file_path' => 'nullable|string|max:255',
        ];
    }
}
