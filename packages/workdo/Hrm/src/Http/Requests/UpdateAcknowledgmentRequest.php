<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcknowledgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'document_id' => 'required|exists:hrm_documents,id',
            'acknowledgment_note' => 'nullable|string'
        ];
    }
}