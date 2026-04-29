<?php

namespace Workdo\Toyyibpay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateToyyibpaySettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings.toyyibpay_enabled' => 'required|string|in:on,off',
            'settings.toyyibpay_secret_key' => 'required_if:settings.toyyibpay_enabled,on|nullable|string',
            'settings.toyyibpay_category_code' => 'required_if:settings.toyyibpay_enabled,on|nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'settings.toyyibpay_secret_key.required_if' => __('Toyyibpay secret key is required.'),
            'settings.toyyibpay_category_code.required_if' => __('Toyyibpay category code is required.'),
            'settings.toyyibpay_enabled.in' => __('Invalid status value.'),
        ];
    }
}