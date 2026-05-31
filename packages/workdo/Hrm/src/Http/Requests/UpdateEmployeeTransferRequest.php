<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'to_branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'to_department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'to_designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'effective_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
            'document' => 'nullable|string'
        ];
    }
}
