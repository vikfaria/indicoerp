<?php

namespace Workdo\AuthorizeNet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorizeNetSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings.authorizenet_enabled' => 'required|string|in:on,off',
            'settings.authorizenet_merchant_login_id' => 'required_if:settings.authorizenet_enabled,on|nullable|string|max:255',
            'settings.authorizenet_merchant_transaction_key' => 'required_if:settings.authorizenet_enabled,on|nullable|string|max:255',
            'settings.authorizenet_mode' => 'required_if:settings.authorizenet_enabled,on|nullable|string|in:sandbox,live',
        ];
    }

    public function messages()
    {
        return [
            'settings.authorizenet_merchant_login_id.required_if' => __('AuthorizeNet merchant login ID is required.'),
            'settings.authorizenet_merchant_transaction_key.required_if' => __('AuthorizeNet merchant transaction key is required.'),
            'settings.authorizenet_mode.required_if' => __('AuthorizeNet mode is required.'),
            'settings.authorizenet_enabled.in' => __('Invalid status value.'),
            'settings.authorizenet_mode.in' => __('AuthorizeNet mode must be either sandbox or live.'),
        ];
    }
}