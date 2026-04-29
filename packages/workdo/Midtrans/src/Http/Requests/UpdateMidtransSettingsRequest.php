<?php

namespace Workdo\Midtrans\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMidtransSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings.midtrans_enabled' => 'required|string|in:on,off',
            'settings.midtrans_secret_key' => 'required_if:settings.midtrans_enabled,on|nullable|string',
            'settings.midtrans_mode' => 'required_if:settings.midtrans_enabled,on|nullable|in:sandbox,live',
        ];
    }

    public function messages()
    {
       return [
            'settings.midtrans_secret_key.required_if' => __('Midtrans secret key is required.'),
            'settings.midtrans_mode.required_if' => __('Midtrans mode is required.'),
            'settings.midtrans_enabled.in' => __('Invalid status value.'),
            'settings.midtrans_mode.in' => __('Midtrans mode must be either sandbox or live.'),
        ];
    }
}