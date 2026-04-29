<?php

namespace Workdo\Coingate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoingateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.coingate_enabled' => 'required|string|in:on,off',
            'settings.coingate_mode' => 'required_if:settings.coingate_enabled,on|nullable|string|in:sandbox,live',
            'settings.coingate_auth_token' => 'required_if:settings.coingate_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.coingate_mode.required_if' => __('CoinGate mode is required.'),
            'settings.coingate_auth_token.required_if' => __('CoinGate auth token is required.'),
            'settings.coingate_enabled.in' => __('Invalid status value.'),
            'settings.coingate_mode.in' => __('CoinGate mode must be either sandbox or live.'),
        ];
    }
}
