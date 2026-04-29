<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:100',
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:100',
            'account_type' => 'required',
//            'payment_gateway' => 'nullable|string|max:100',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
            'iban' => 'nullable|string|max:34',
            'swift_code' => 'nullable|string|max:11',
            'routing_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'gl_account_id' => 'required|exists:chart_of_accounts,id'
        ];
    }
}