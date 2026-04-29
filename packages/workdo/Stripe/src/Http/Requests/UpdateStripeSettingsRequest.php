<?php

namespace Workdo\Stripe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStripeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.stripe_key' => 'required|string',
            'settings.stripe_secret' => 'required|string',
            'settings.stripe_enabled' => 'string|in:on,off',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.stripe_key.required' => __('Stripe key is required.'),
            'settings.stripe_secret.required' => __('Stripe secret is required.'),
            'settings.stripe_enabled.in' => __('Stripe enabled must be either on or off.'),
        ];
    }
}