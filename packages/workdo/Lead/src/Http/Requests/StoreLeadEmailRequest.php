<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadEmailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ];
    }
}