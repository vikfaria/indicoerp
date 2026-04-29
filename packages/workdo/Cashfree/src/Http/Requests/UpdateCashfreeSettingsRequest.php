<?php

namespace Workdo\Cashfree\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashfreeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.cashfree_enabled' => 'required|string|in:on,off',
            'settings.cashfree_key' => 'required_if:settings.cashfree_enabled,on|nullable|string',
            'settings.cashfree_secret' => 'required_if:settings.cashfree_enabled,on|nullable|string',
            'settings.cashfree_mode' => 'required_if:settings.cashfree_enabled,on|nullable|string|in:sandbox,production',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.cashfree_key.required_if' => __('Cashfree key is required.'),
            'settings.cashfree_secret.required_if' => __('Cashfree secret is required.'),
            'settings.cashfree_mode.required_if' => __('Cashfree mode is required.'),
            'settings.cashfree_enabled.in' => __('Invalid status value.'),
            'settings.cashfree_mode.in' => __('Cashfree mode must be either sandbox or production.'),
        ];
    }
}