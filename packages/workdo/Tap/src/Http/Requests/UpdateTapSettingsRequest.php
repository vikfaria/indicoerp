<?php

namespace Workdo\Tap\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTapSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.tap_enabled' => 'required|string|in:on,off',
            'settings.tap_secret_key' => 'required_if:settings.tap_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.tap_secret_key.required_if' => __('Tap secret key is required.'),
            'settings.tap_enabled.in' => __('Invalid status value.'),
        ];
    }
}
