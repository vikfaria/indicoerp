<?php

namespace Workdo\Iyzipay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIyzipaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.iyzipay_enabled' => 'required|string|in:on,off',
            'settings.iyzipay_api_key' => 'required_if:settings.iyzipay_enabled,on|nullable|string',
            'settings.iyzipay_secret_key' => 'required_if:settings.iyzipay_enabled,on|nullable|string',
            'settings.iyzipay_mode' => 'required_if:settings.iyzipay_enabled,on|nullable|string|in:sandbox,live',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.iyzipay_api_key.required_if' => __('Iyzipay API key is required.'),
            'settings.iyzipay_secret_key.required_if' => __('Iyzipay secret key is required.'),
            'settings.iyzipay_mode.required_if' => __('Iyzipay mode is required.'),
            'settings.iyzipay_enabled.in' => __('Invalid status value.'),
            'settings.iyzipay_mode.in' => __('Iyzipay mode must be either sandbox or live.'),
        ];
    }
}