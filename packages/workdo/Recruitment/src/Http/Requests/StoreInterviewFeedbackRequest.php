<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technical_rating' => 'nullable|integer|between:1,5',
            'communication_rating' => 'nullable|integer|between:1,5',
            'cultural_fit_rating' => 'nullable|integer|between:1,5',
            'overall_rating' => 'nullable|integer|between:1,5',
            'strengths' => 'nullable',
            'weaknesses' => 'nullable',
            'comments' => 'nullable',
            'recommendation' => 'required',
            'interview_id' => 'required|exists:interviews,id',
            'interviewer_ids' => 'required|array|min:1',
            'interviewer_ids.*' => 'exists:users,id'
        ];
    }

    public function messages(): array
    {
        return [
            'interview_id.required' => __('Please select an interview.'),
            'interviewer_ids.required' => __('Please select at least one interviewer.'),
            'interviewer_ids.min' => __('Please select at least one interviewer.'),
            'technical_rating.integer' => __('Technical rating must be a number.'),
            'technical_rating.between' => __('Technical rating must be between 1 and 5.'),
            'communication_rating.integer' => __('Communication rating must be a number.'),
            'communication_rating.between' => __('Communication rating must be between 1 and 5.'),
            'cultural_fit_rating.integer' => __('Cultural fit rating must be a number.'),
            'cultural_fit_rating.between' => __('Cultural fit rating must be between 1 and 5.'),
            'overall_rating.integer' => __('Overall rating must be a number.'),
            'overall_rating.between' => __('Overall rating must be between 1 and 5.'),
        ];
    }
}