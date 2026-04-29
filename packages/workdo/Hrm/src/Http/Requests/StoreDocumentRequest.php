<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'description' => 'nullable',
            'document_category_id' => 'nullable|integer',
            'document' => 'nullable',
            'effective_date' => 'nullable|date',
            'status' => 'required',
            'uploaded_by' => 'nullable|integer',
            'approved_by' => 'nullable|integer'
        ];
    }
}