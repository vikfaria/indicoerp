<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactInformationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'map_embed_url' => 'nullable|string|max:2000',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/|max:255',
            'email' => 'required|email|max:255',
        ];
    }
}