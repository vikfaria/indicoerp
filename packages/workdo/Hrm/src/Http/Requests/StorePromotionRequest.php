<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
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
            'current_branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'current_department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'current_designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'effective_date' => 'required|date',
            'reason' => 'nullable|string',
            'document' => 'nullable|string',
        ];
    }
}
