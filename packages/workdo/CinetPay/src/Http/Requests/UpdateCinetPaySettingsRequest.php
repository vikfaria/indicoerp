<?php

namespace Workdo\CinetPay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCinetPaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.cinetpay_enabled' => 'required|in:on,off',
            'settings.cinetpay_api_key' => 'required_if:settings.cinetpay_enabled,on|string|max:255',
            'settings.cinetpay_site_id' => 'required_if:settings.cinetpay_enabled,on|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.cinetpay_enabled.required' => __('CinetPay enabled status is required.'),
            'settings.cinetpay_enabled.in' => __('CinetPay enabled status must be either "on" or "off".'),
            'settings.cinetpay_api_key.required_if' => __('CinetPay API key is required when CinetPay is enabled.'),
            'settings.cinetpay_api_key.string' => __('CinetPay API key must be a string.'),
            'settings.cinetpay_api_key.max' => __('CinetPay API key may not be greater than 255 characters.'),
            'settings.cinetpay_site_id.required_if' => __('CinetPay Site ID is required when CinetPay is enabled.'),
            'settings.cinetpay_site_id.string' => __('CinetPay Site ID must be a string.'),
            'settings.cinetpay_site_id.max' => __('CinetPay Site ID may not be greater than 255 characters.'),
        ];
    }
}