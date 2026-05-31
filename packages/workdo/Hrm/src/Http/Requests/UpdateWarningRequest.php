<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'warning_by' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'warning_type_id' => [
                'required',
                'integer',
                Rule::exists('warning_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'subject' => 'required|max:255',
            'severity' => 'required',
            'warning_date' => 'required|date',
            'note_of_culpa_issued_at' => 'nullable|date|after_or_equal:warning_date',
            'note_of_culpa_delivered_at' => 'nullable|date|after_or_equal:note_of_culpa_issued_at',
            'worker_refused_note_of_culpa' => 'nullable|boolean',
            'refusal_witness_one_name' => 'nullable|string|max:120|required_if:worker_refused_note_of_culpa,1,true',
            'refusal_witness_two_name' => 'nullable|string|max:120|required_if:worker_refused_note_of_culpa,1,true|different:refusal_witness_one_name',
            'response_deadline_at' => 'nullable|date|after_or_equal:note_of_culpa_delivered_at',
            'decision_deadline_at' => 'nullable|date|after_or_equal:response_deadline_at',
            'disciplinary_sanction' => ['nullable', 'string', Rule::in(['warning', 'reprimand', 'suspension', 'demotion', 'dismissal', 'archived'])],
            'disciplinary_decision_at' => 'nullable|date|after_or_equal:note_of_culpa_issued_at',
            'description' => 'nullable',
            'document' => 'nullable|string',

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'worker_refused_note_of_culpa' => $this->boolean('worker_refused_note_of_culpa'),
        ]);
    }
}
