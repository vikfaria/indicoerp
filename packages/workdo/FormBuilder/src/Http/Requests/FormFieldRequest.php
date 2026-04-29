<?php

namespace Workdo\FormBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormFieldRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fields' => 'required|array',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,email,number,tel,url,password,textarea,select,radio,checkbox,date,time',
            'fields.*.required' => 'boolean',
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.options' => 'nullable|array',
            'fields.*.order' => 'integer|min:0',
        ];
    }
}