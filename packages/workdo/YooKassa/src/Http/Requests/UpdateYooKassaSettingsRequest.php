<?php

namespace Workdo\YooKassa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateYooKassaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings.yookassa_enabled' => 'required|string|in:on,off',
            'settings.yookassa_shop_id' => 'required_if:settings.yookassa_enabled,on|nullable|string',
            'settings.yookassa_secret_key' => 'required_if:settings.yookassa_enabled,on|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.yookassa_shop_id.required_if' => __('YooKassa shop ID is required.'),
            'settings.yookassa_secret_key.required_if' => __('YooKassa secret key is required.'),
            'settings.yookassa_enabled.in' => __('Invalid status value.'),
        ];
    }
}