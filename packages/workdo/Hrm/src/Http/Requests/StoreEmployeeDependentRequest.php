<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDependentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:160',
            'relationship' => 'required|string|max:50',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'document_number' => 'nullable|string|max:120',
            'is_student' => 'nullable|boolean',
            'is_tax_eligible' => 'required|boolean',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
