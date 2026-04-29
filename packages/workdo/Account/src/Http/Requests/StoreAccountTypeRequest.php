<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:account_categories,id',
            'name' => 'required|max:100|unique:account_types,name,NULL,id,created_by,' . creatorId(),
            'code' => 'required|max:10|unique:account_types,code,NULL,id,created_by,' . creatorId(),
            'normal_balance' => 'required',
            'description' => 'nullable',
            'is_active' => 'boolean'
        ];
    }
}
