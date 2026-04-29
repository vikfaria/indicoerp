<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'priority' => 'required|in:Low,Medium,High',
            'status' => 'required|in:On Going,Complete'
        ];
    }
}