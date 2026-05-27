<?php

namespace Workdo\Hrm\Http\Requests;

use App\Services\MozambiqueProbationPolicyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEmployeeProbationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = app(MozambiqueProbationPolicyService::class)->categories();

        return [
            'probation_category' => ['required', 'string', Rule::in($categories)],
            'starts_at' => ['required', 'date'],
            'expected_end_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'evaluation_status' => ['required', 'string', Rule::in(['pending', 'approved', 'failed'])],
            'technical_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'attendance_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'punctuality_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'conduct_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'adaptation_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['nullable', 'string', Rule::in(['continue', 'cease'])],
            'decision_status' => ['required', 'string', Rule::in(['ongoing', 'confirmed', 'ceased'])],
            'decision_date' => ['nullable', 'date'],
            'cessation_reason' => ['nullable', 'string', 'max:5000', 'required_if:decision_status,ceased'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
