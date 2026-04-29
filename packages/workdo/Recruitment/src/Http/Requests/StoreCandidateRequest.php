<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Models\JobPosting;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email|unique:candidates',
            'phone' => 'nullable|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'country' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'city' => 'nullable|max:100',
            'current_company' => 'nullable|max:100',
            'current_position' => 'nullable|max:100',
            'experience_years' => 'required|min:0',
            'current_salary' => 'nullable|numeric|min:0',
            'expected_salary' => 'nullable|numeric|min:0',
            'notice_period' => 'nullable|max:50',
            'skills' => 'nullable',
            'education' => 'nullable',
            'portfolio_url' => 'nullable',
            'linkedin_url' => 'nullable',
            'profile_photo' => 'nullable|image|max:5120',
            'resume' => 'nullable|file|max:10240',
            'cover_letter' => 'nullable|file|max:10240',
            'status' => 'required',
            'application_date' => 'required|date',
            'custom_question' => 'nullable',
            'job_id' => 'required|numeric|exists:job_postings,id',
            'source_id' => 'required|numeric|exists:candidate_sources,id'
        ];

        // Add dynamic validation for custom question fields
        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, 'custom_question_')) {
                $rules[$key] = 'nullable';
            }
        }

        return $rules;
    }
}