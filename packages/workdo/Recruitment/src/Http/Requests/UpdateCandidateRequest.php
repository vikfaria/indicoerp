<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Models\JobPosting;

class UpdateCandidateRequest extends FormRequest
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
            'email' => 'required|email|unique:candidates,email,' . $this->route('candidate')->id,
            'phone' => 'nullable|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'country' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'city' => 'nullable|max:100',
            'current_company' => 'nullable|max:100',
            'current_position' => 'nullable|max:100',
            'experience_years' => 'required|numeric|min:0',
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
            'application_date' => 'required|date',
            'custom_question' => 'nullable',
            'job_id' => 'required|numeric|exists:job_postings,id',
            'source_id' => 'required|numeric|exists:candidate_sources,id'
        ];

        // Add dynamic validation for custom question fields
        if ($this->has('job_id')) {
            $jobPosting = JobPosting::find($this->input('job_id'));
            if ($jobPosting && $jobPosting->custom_questions) {
                $customQuestions = CustomQuestion::whereIn('id', $jobPosting->custom_questions)
                    ->where('is_active', true)
                    ->get();

                foreach ($customQuestions as $question) {
                    $fieldName = 'custom_question_' . $question->id;
                    $rules[$fieldName] = $question->is_required ? 'required' : 'nullable';
                }
            }
        }

        // Fallback for any other custom question fields
        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, 'custom_question_') && !isset($rules[$key])) {
                $rules[$key] = 'nullable';
            }
        }

        return $rules;
    }
}