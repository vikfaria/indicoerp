<?php

namespace Workdo\PayTab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayTabSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.paytab_payment_is_on' => 'required|string|in:on,off',
            'settings.paytab_profile_id' => 'required_if:settings.paytab_payment_is_on,on|nullable|string',
            'settings.paytab_server_key' => 'required_if:settings.paytab_payment_is_on,on|nullable|string',
            'settings.paytab_region' => 'required_if:settings.paytab_payment_is_on,on|nullable|string|in:GLOBAL,EGY,JOR,OMN,SAU,ARE',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.paytab_profile_id.required_if' => __('PayTab profile ID is required.'),
            'settings.paytab_server_key.required_if' => __('PayTab server key is required.'),
            'settings.paytab_region.required_if' => __('PayTab region is required.'),
            'settings.paytab_payment_is_on.in' => __('Invalid status value.'),
            'settings.paytab_region.in' => __('Invalid region selected.'),
        ];
    }
}