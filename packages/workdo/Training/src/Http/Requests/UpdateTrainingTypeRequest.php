<?php

namespace Workdo\Training\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',            
        ];
    }
}