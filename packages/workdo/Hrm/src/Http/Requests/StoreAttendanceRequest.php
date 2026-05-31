<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
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
                Rule::exists('employees', 'user_id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'date' => 'required|date',
            'clock_in' => 'required|date_format:Y-m-d H:i',
            'clock_out' => 'required|date_format:Y-m-d H:i',
            'is_justified' => 'nullable|boolean',
            'absence_category' => ['nullable', 'string', Rule::in([
                'unjustified',
                'medical',
                'work_accident',
                'family_assistance',
                'legal_leave',
                'manager_authorized',
                'public_service',
                'other',
            ])],
            'notes' => 'nullable|string'
        ];
    }

    protected function prepareForValidation(): void
    {
        $isJustified = $this->input('is_justified');
        if ($isJustified === '' || $isJustified === null || $isJustified === 'auto') {
            $normalizedIsJustified = null;
        } else {
            $normalizedIsJustified = filter_var($isJustified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $absenceCategory = $this->input('absence_category');
        if ($absenceCategory === '' || $absenceCategory === null || $absenceCategory === 'none') {
            $absenceCategory = null;
        }

        $this->merge([
            'is_justified' => $normalizedIsJustified,
            'absence_category' => $absenceCategory,
        ]);
    }
}
