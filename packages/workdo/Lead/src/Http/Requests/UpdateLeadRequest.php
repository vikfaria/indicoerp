<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'email' => 'required|email',
            'subject' => 'required|max:200',
            'user_id' => 'required|exists:users,id',
            'pipeline_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',
            'sources' => 'nullable|max:100',
            'products' => 'nullable|max:100',
            'notes' => 'nullable|max:1000',
            'phone' => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
            'date' => 'nullable|date',
        ];
    }
}