<?php

namespace Workdo\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountTypeId = $this->route('accounttype') ? $this->route('accounttype')->id : null;
        
        return [
            'category_id' => 'required',
            'name' => 'required|max:100|unique:account_types,name,' . $accountTypeId . ',id,created_by,' . creatorId(),
            'code' => 'required|max:10|unique:account_types,code,' . $accountTypeId . ',id,created_by,' . creatorId(),
            'normal_balance' => 'required',
            'description' => 'nullable',
            'is_active' => 'boolean'
        ];
    }
}
