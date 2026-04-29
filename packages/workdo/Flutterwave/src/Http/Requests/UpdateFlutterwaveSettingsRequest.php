<?php

namespace Workdo\Flutterwave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlutterwaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.flutterwave_enabled' => 'required|string|in:on,off',
            'settings.flutterwave_public_key' => 'required_if:settings.flutterwave_enabled,on|nullable|string',
            'settings.flutterwave_secret_key' => 'required_if:settings.flutterwave_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.flutterwave_public_key.required_if' => __('Flutterwave public key is required.'),
            'settings.flutterwave_secret_key.required_if' => __('Flutterwave secret key is required.'),
            'settings.flutterwave_enabled.in' => __('Invalid status value.'),
        ];
    }
}