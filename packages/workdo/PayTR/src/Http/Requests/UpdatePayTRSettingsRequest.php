<?php

namespace Workdo\PayTR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayTRSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.paytr_enabled' => 'required|string|in:on,off',
            'settings.paytr_merchant_id' => 'required_if:settings.paytr_enabled,on|nullable|string',
            'settings.paytr_merchant_key' => 'required_if:settings.paytr_enabled,on|nullable|string',
            'settings.paytr_merchant_salt' => 'required_if:settings.paytr_enabled,on|nullable|string',
            'settings.paytr_mode' => 'required_if:settings.paytr_enabled,on|nullable|string|in:sandbox,live',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.paytr_merchant_id.required_if' => __('PayTR merchant ID is required.'),
            'settings.paytr_merchant_key.required_if' => __('PayTR merchant key is required.'),
            'settings.paytr_merchant_salt.required_if' => __('PayTR merchant salt is required.'),
            'settings.paytr_mode.required_if' => __('PayTR mode is required.'),
            'settings.paytr_enabled.in' => __('Invalid status value.'),
            'settings.paytr_mode.in' => __('PayTR mode must be either sandbox or live.'),
        ];
    }
}