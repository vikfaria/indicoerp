<?php

namespace Workdo\FormBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'default_layout' => 'string|in:single,two-column,card',
            'fields' => 'array',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,email,number,tel,url,password,textarea,select,radio,checkbox,date,time',
            'fields.*.required' => 'boolean',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.options' => 'nullable|array',
            'fields.*.options.*' => 'string|max:255',
            'fields.*.order' => 'integer|min:0',
            'fields.*.id' => 'nullable|integer',
        ];
    }
}