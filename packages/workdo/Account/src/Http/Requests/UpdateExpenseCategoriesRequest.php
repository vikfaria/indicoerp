<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required',
            'category_code' => 'required|unique:expense_categories,category_code,' . $this->route('expensecategories')->id,
            'gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable',
            'is_active' => 'boolean'
        ];
    }
}