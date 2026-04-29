<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'logo_dark' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
            'titleText' => 'required|string|max:255',
            'footerText' => 'required|string|max:500',
        ];
    }
}