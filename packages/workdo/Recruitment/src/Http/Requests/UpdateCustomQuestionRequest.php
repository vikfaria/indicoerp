<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'required|max:255',
            'type' => 'required',
            'options' => 'nullable',
            'is_required' => 'nullable',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|min:0'
        ];
    }
}