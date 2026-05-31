<?php

namespace Workdo\Recruitment\Http\Requests;

use Carbon\Carbon;
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
            'nationality' => 'nullable|string|max:100',
            'identification_document_type' => 'nullable|string|max:50',
            'identification_document_number' => 'nullable|string|max:100',
            'nuit' => 'nullable|string|max:32|regex:/^[0-9]{9}$/',
            'desired_professional_category' => 'nullable|string|max:255',
            'is_regulated_profession' => 'nullable|boolean',
            'professional_license_type' => 'nullable|string|max:100',
            'professional_license_number' => 'nullable|string|max:100',
            'professional_license_expiry_date' => 'nullable|date',
            'minor_work_authorization_path' => 'nullable|string|max:255',
            'legal_exception_notes' => 'nullable|string|max:2000',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('dob')) {
                $dob = Carbon::parse((string) $this->input('dob'));
                $age = $dob->age;

                if ($age < 12) {
                    $validator->errors()->add('dob', __('Hiring candidates under 12 years old is not allowed by labour compliance rules.'));
                }

                if ($age >= 12 && $age < 15) {
                    if (!$this->filled('minor_work_authorization_path')) {
                        $validator->errors()->add('minor_work_authorization_path', __('Special authorization evidence is required for candidates aged between 12 and 15.'));
                    }

                    if (!$this->filled('legal_exception_notes')) {
                        $validator->errors()->add('legal_exception_notes', __('Legal justification notes are required for candidates aged between 12 and 15.'));
                    }
                }
            }

            $hasDocumentType = $this->filled('identification_document_type');
            $hasDocumentNumber = $this->filled('identification_document_number');
            if ($hasDocumentType xor $hasDocumentNumber) {
                $validator->errors()->add('identification_document_type', __('Identification type and number must be provided together.'));
                $validator->errors()->add('identification_document_number', __('Identification type and number must be provided together.'));
            }

            $isRegulatedProfession = filter_var($this->input('is_regulated_profession', false), FILTER_VALIDATE_BOOLEAN);
            if ($isRegulatedProfession) {
                if (!$this->filled('professional_license_type')) {
                    $validator->errors()->add('professional_license_type', __('Professional license type is required for regulated professions.'));
                }

                if (!$this->filled('professional_license_number')) {
                    $validator->errors()->add('professional_license_number', __('Professional license number is required for regulated professions.'));
                }
            }
        });
    }
}
