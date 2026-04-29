<?php

namespace Workdo\FormBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormConversionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'module_name' => 'required|string',
            'submodule_name' => 'required|string',
            'is_active' => 'boolean',
            'field_mappings' => 'required|array',
            'field_mappings.*' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'module_name.required' => __('The module name is required.'),
            'module_name.string' => __('The module name must be a string.'),
            'submodule_name.required' => __('The submodule name is required.'),
            'submodule_name.string' => __('The submodule name must be a string.'),
            'field_mappings.required' => __('The field mappings are required.'),
            'field_mappings.array' => __('The field mappings must be an array.'),
        ];
    }
}