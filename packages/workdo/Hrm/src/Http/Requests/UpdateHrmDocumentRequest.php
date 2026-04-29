<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrmDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'document_category_id' => 'required|exists:document_categories,id',
            'description' => 'nullable|string',
            'document' => 'required|string',
        ];
    }
}