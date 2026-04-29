<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'designation_name' => 'required|max:100',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id'
        ];
    }
}