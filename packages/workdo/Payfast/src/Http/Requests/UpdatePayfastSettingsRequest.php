<?php

namespace Workdo\Payfast\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayfastSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.payfast_enabled' => 'required|string|in:on,off',
            'settings.payfast_merchant_id' => 'required_if:settings.payfast_enabled,on|nullable|string',
            'settings.payfast_merchant_key' => 'required_if:settings.payfast_enabled,on|nullable|string',
            'settings.payfast_salt_passphrase' => 'required_if:settings.payfast_enabled,on|nullable|string',
            'settings.payfast_mode' => 'required_if:settings.payfast_enabled,on|nullable|string|in:sandbox,live',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.payfast_merchant_id.required_if' => __('Payfast merchant ID is required.'),
            'settings.payfast_merchant_key.required_if' => __('Payfast merchant key is required.'),
            'settings.payfast_salt_passphrase.required_if' => __('Payfast salt passphrase is required.'),
            'settings.payfast_mode.required_if' => __('Payfast mode is required.'),
            'settings.payfast_enabled.in' => __('Invalid status value.'),
            'settings.payfast_mode.in' => __('Payfast mode must be either sandbox or live.'),
        ];
    }
}