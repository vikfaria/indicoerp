<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chartOfAccountId = $this->route('chartofaccount') ? $this->route('chartofaccount')->id : null;
        
        return [
            'account_code' => 'required|string|max:255|unique:chart_of_accounts,account_code,' . $chartOfAccountId . ',id,created_by,' . creatorId(),
            'account_name' => 'required|string|max:255',
            'level' => 'nullable|integer|min:1',
            'normal_balance' => 'required|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'account_type_id' => 'nullable|exists:account_types,id',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id'
        ];
    }
}