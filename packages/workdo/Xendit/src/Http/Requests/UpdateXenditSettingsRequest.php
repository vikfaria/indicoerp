<?php

namespace Workdo\Xendit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateXenditSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.xendit_enabled' => 'required|string|in:on,off',
            'settings.xendit_key' => 'required_if:settings.xendit_enabled,on|nullable|string',
            'settings.xendit_token' => 'required_if:settings.xendit_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.xendit_key.required_if' => __('Xendit key is required.'),
            'settings.xendit_token.required_if' => __('Xendit token is required.'),
            'settings.xendit_enabled.in' => __('Invalid status value.'),
        ];
    }
}