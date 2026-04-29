<?php

namespace Workdo\Paypal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaypalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.paypal_client_id' => 'required_if:settings.paypal_enabled,on|string',
            'settings.paypal_secret_key' => 'required_if:settings.paypal_enabled,on|string',
            'settings.paypal_enabled' => 'string|in:on,off',
            'settings.paypal_mode' => 'required_if:settings.paypal_enabled,on|string|in:sandbox,live',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.paypal_client_id.required_if' => __('PayPal client ID is required when PayPal is enabled.'),
            'settings.paypal_secret_key.required_if' => __('PayPal secret key is required when PayPal is enabled.'),
            'settings.paypal_enabled.in' => __('PayPal enabled must be either on or off.'),
            'settings.paypal_mode.required_if' => __('PayPal mode is required when PayPal is enabled.'),
            'settings.paypal_mode.in' => __('PayPal mode must be either sandbox or live.'),
        ];
    }
}