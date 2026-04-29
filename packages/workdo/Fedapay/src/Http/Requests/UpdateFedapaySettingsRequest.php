<?php

namespace Workdo\Fedapay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFedapaySettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings.fedapay_enabled' => 'required|string|in:on,off',
            'settings.fedapay_public_key' => 'required_if:settings.fedapay_enabled,on|nullable|string',
            'settings.fedapay_secret_key' => 'required_if:settings.fedapay_enabled,on|nullable|string',
            'settings.fedapay_mode' => 'required_if:settings.fedapay_enabled,on|string|in:sandbox,live',
        ];
    }

    public function messages()
    {
        return [
            'settings.fedapay_public_key.required_if' => __('FedaPay public key is required.'),
            'settings.fedapay_secret_key.required_if' => __('FedaPay secret key is required.'),
            'settings.fedapay_enabled.in' => __('FedaPay enabled must be either on or off.'),
            'settings.fedapay_mode.required_if' => __('FedaPay mode is required.'),
            'settings.fedapay_mode.in' => __('FedaPay mode must be either sandbox or live.'),
        ];
    }
}
