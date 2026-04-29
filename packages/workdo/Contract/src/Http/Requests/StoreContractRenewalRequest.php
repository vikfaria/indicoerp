<?php

namespace Workdo\Contract\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRenewalRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('create-contract-renewals');
    }

    public function rules()
    {
        return [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'value' => 'required|numeric|min:0',
            'status' => 'required|in:draft,pending,approved,active,expired,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];
    }

}