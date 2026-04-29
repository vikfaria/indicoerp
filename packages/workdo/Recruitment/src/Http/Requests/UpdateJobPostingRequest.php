<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:100',
            'position' => 'required|integer|min:1',
            'priority' => 'required|in:0,1,2',
            'job_application' => 'required|in:existing,custom',
            'application_url' => 'required_if:job_application,custom|nullable|url',
            'branch_id' => 'required|exists:branches,id',
            'applicant' => 'nullable|array',
            'applicant.*' => 'string|in:gender,date_of_birth,country',
            'visibility' => 'nullable|array',
            'visibility.*' => 'string|in:profile_image,resume,cover_letter',
            'min_experience' => 'required|numeric|min:0',
            'max_experience' => 'nullable|numeric|min:0|gte:min_experience',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'description' => 'nullable',
            'requirements' => 'nullable',
            'benefits' => 'nullable',
            'terms_condition' => 'required',
            'show_terms_condition' => 'nullable|boolean',
            'application_deadline' => 'nullable|date|after_or_equal:today',
            'is_published' => 'nullable',
            'publish_date' => 'nullable',
            'is_featured' => 'nullable',
            'status' => 'required',
            'job_type_id' => 'required|exists:job_types,id',
            'location_id' => 'required|exists:job_locations,id',
            'custom_questions' => 'nullable|array',
            'custom_questions.*' => 'integer|exists:custom_questions,id',
            'skills' => 'required|array',
            'skills.*' => 'string|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'max_experience.gte' => __('Maximum experience must be greater than or equal to minimum experience.'),
            'max_salary.gte' => __('Maximum salary must be greater than or equal to minimum salary.'),
            'min_experience.required' => __('Minimum experience is required.'),
            'min_experience.numeric' => __('Minimum experience must be a number.'),
            'min_experience.min' => __('Minimum experience cannot be negative.'),
            'max_experience.numeric' => __('Maximum experience must be a number.'),
            'max_experience.min' => __('Maximum experience cannot be negative.'),
            'min_salary.numeric' => __('Minimum salary must be a number.'),
            'min_salary.min' => __('Minimum salary cannot be negative.'),
            'max_salary.numeric' => __('Maximum salary must be a number.'),
            'max_salary.min' => __('Maximum salary cannot be negative.'),
        ];
    }
}
