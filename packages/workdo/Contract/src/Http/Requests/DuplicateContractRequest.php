<?php

namespace Workdo\Contract\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|max:255',
            'user_id' => 'required|exists:users,id',
            'value' => 'required',
            'type_id' => 'required|exists:contract_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable',
            'status' => 'required'
        ];
    }
}