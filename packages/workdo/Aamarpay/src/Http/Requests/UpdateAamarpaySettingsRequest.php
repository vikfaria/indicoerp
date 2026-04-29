<?php

namespace Workdo\Aamarpay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAamarpaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.aamarpay_enabled' => 'required|string|in:on,off',
            'settings.aamarpay_store_id' => 'required_if:settings.aamarpay_enabled,on|nullable|string',
            'settings.aamarpay_signature_key' => 'required_if:settings.aamarpay_enabled,on|nullable|string',
            'settings.aamarpay_mode' => 'required_if:settings.aamarpay_enabled,on|nullable|string|in:sandbox,live',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.aamarpay_store_id.required_if' => __('Aamarpay store ID is required.'),
            'settings.aamarpay_signature_key.required_if' => __('Aamarpay signature key is required.'),
            'settings.aamarpay_mode.required_if' => __('Aamarpay mode is required.'),
            'settings.aamarpay_enabled.in' => __('Invalid status value.'),
            'settings.aamarpay_mode.in' => __('Aamarpay mode must be either sandbox or live.'),
        ];
    }
}