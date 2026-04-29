<?php

namespace Workdo\Goal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required|max:100',
            'category_code' => 'required|string|max:255',
            'description' => 'nullable',
            'is_active' => 'required'
        ];
    }
}