<?php

namespace Workdo\Razorpay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRazorpaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.razorpay_enabled' => 'required|string|in:on,off',
            'settings.razorpay_public_key' => 'required_if:settings.razorpay_enabled,on|nullable|string',
            'settings.razorpay_secret_key' => 'required_if:settings.razorpay_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.razorpay_public_key.required_if' => __('Razorpay public key is required.'),
            'settings.razorpay_secret_key.required_if' => __('Razorpay secret key is required.'),
            'settings.razorpay_enabled.in' => __('Invalid status value.'),
        ];
    }
}