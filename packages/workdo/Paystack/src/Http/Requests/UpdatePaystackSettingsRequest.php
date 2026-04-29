<?php

namespace Workdo\Paystack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaystackSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.paystack_enabled' => 'required|string|in:on,off',
            'settings.paystack_public_key' => 'required_if:settings.paystack_enabled,on|nullable|string',
            'settings.paystack_secret_key' => 'required_if:settings.paystack_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.paystack_public_key.required_if' => __('Paystack public key is required.'),
            'settings.paystack_secret_key.required_if' => __('Paystack secret key is required.'),
            'settings.paystack_enabled.in' => __('Invalid status value.'),
        ];
    }
}