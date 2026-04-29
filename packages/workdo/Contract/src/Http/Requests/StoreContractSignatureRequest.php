<?php

namespace Workdo\Contract\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractSignatureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'signature_data' => 'required|string|min:10',
            'signature_type' => 'required|in:digital,drawn',
        ];
    }

    public function messages()
    {
        return [
            'signature_data.required' => 'Signature is required.',
            'signature_data.string' => 'Signature must be a valid string.',
            'signature_data.min' => 'Signature data is too short.',
            'signature_type.required' => 'Signature type is required.',
            'signature_type.in' => 'Signature type must be either digital or drawn.',
        ];
    }
}
