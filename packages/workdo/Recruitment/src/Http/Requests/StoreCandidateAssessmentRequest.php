<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'assessment_name' => 'required|string|max:255',
            'score' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:1',
            'pass_fail_status' => 'required|in:0,1,2',
            'conducted_by' => 'required|exists:users,id',
            'assessment_date' => 'required|date',
            'comments' => 'nullable|string'
        ];
    }
}