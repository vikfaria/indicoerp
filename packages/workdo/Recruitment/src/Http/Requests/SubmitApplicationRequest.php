<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $maxUploadSize = (int) (admin_setting('maxUploadSize') ?? 10) * 1024;

        return [
            'name' => 'required|max:200',
            'email' => 'required|email',
            'phone' => 'nullable|max:20',
            'dateOfBirth' => 'nullable|date|before:today',
            'country' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'city' => 'nullable|max:100',
            'gender' => 'nullable|in:male,female,other',
            'currentCompany' => 'nullable|max:100',
            'currentPosition' => 'nullable|max:100',
            'experienceYears' => 'required|integer|min:0',
            'currentSalary' => 'nullable|numeric|min:0',
            'expectedSalary' => 'nullable|numeric|min:0',
            'noticePeriod' => 'nullable|max:50',
            'skills' => 'nullable',
            'education' => 'nullable',
            'portfolioUrl' => 'nullable|url|max:255',
            'linkedinUrl' => 'nullable|url|max:255',
            'profilePhoto' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:' . $maxUploadSize,
            'resume' => 'nullable|file|max:' . $maxUploadSize,
            'coverLetter' => 'nullable|file|max:' . $maxUploadSize
        ];
    }
}
