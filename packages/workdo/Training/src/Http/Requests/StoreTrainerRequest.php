<?php

namespace Workdo\Training\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'email' => 'required|email|unique:trainers,email',
            'experience' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
            'expertise' => 'nullable|string',
            'qualification' => 'nullable|string',
        ];
    }
}