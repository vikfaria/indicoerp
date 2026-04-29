<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'email' => 'required|email',
            'subject' => 'required|max:200',            
            'phone' => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
            'date' => 'nullable|date',
            'user_id' => 'required|exists:users,id',
        ];
    }
}