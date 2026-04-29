<?php

namespace Workdo\Benefit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBenefitSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.benefit_enabled' => 'required|string|in:on,off',
            'settings.benefit_api_key' => 'required_if:settings.benefit_enabled,on|nullable|string',
            'settings.benefit_secret_key' => 'required_if:settings.benefit_enabled,on|nullable|string',
            'settings.benefit_processing_channel_id' => 'required_if:settings.benefit_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.benefit_api_key.required_if' => __('Benefit API key is required.'),
            'settings.benefit_secret_key.required_if' => __('Benefit secret key is required.'),
            'settings.benefit_processing_channel_id.required_if' => __('Benefit processing channel ID is required.'),
            'settings.benefit_enabled.in' => __('Invalid status value.'),
        ];
    }
}