<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToDealRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'numeric|min:0',
            'client_check' => 'required|in:exist,new',
            'is_transfer' => 'array'
        ];

        if ($this->client_check === 'exist') {
            $rules['clients'] = 'required|email|exists:users,email';
        } else {
            $rules['client_name'] = 'required|string|max:255';
            $rules['client_email'] = 'required|email|unique:users,email';
            $rules['client_password'] = 'required|string|min:6';
        }

        return $rules;
    }
}