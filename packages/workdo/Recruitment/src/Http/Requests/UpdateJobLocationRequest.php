<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'remote_work' => 'boolean',
            'address' => 'nullable|string',
            'city' => 'nullable|max:50',
            'state' => 'nullable|max:50',
            'country' => 'nullable|max:50',
            'postal_code' => 'nullable|max:20',
            'status' => 'boolean'
        ];
    }
}