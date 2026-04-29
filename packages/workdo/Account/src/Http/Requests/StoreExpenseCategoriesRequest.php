<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required',
            'category_code' => 'required|unique:expense_categories,category_code',
            'gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable',
            'is_active' => 'boolean'
        ];
    }
}