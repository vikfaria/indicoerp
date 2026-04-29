<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:500',
            'description' => 'required|string|max:10000',
        ];
    }
}