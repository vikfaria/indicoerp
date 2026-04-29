<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'sequence_number' => 'required|min:1',
            'description' => 'nullable',
            'status' => 'required',
            'job_id' => 'required|exists:job_postings,id'
        ];
    }
}