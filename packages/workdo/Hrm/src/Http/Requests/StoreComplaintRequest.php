<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
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
            'against_employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'complaint_type_id' => [
                'required',
                'integer',
                Rule::exists('complaint_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'complaint_date' => 'required|date',
            'is_confidential' => 'nullable|boolean',
            'is_harassment_report' => 'nullable|boolean',
            'confidential_channel' => [
                'nullable',
                'string',
                'max:60',
                Rule::requiredIf(fn (): bool => $this->boolean('is_confidential') || $this->boolean('is_harassment_report')),
            ],
            'confidentiality_level' => ['nullable', 'string', Rule::in(['internal', 'restricted', 'anonymous'])],
            'confidential_access_user_ids' => ['nullable', 'array'],
            'confidential_access_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'handling_owner_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'investigation_started_at' => 'nullable|date|after_or_equal:complaint_date',
            'investigation_closed_at' => 'nullable|date|after_or_equal:investigation_started_at',
            'document' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $accessUserIds = $this->input('confidential_access_user_ids', []);

        if (is_string($accessUserIds)) {
            $accessUserIds = array_filter(array_map('trim', explode(',', $accessUserIds)));
        }

        $this->merge([
            'is_confidential' => $this->boolean('is_confidential'),
            'is_harassment_report' => $this->boolean('is_harassment_report'),
            'confidential_access_user_ids' => collect((array) $accessUserIds)
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all(),
        ]);
    }
}
