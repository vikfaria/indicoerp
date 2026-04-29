<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnboardingChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'is_default' => 'boolean',
            'status' => 'boolean',
        ];
    }
}