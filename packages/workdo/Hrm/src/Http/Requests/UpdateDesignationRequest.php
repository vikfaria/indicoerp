<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'designation_name' => 'required|max:100',
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);

                    if ($this->filled('branch_id')) {
                        $query->where('branch_id', $this->input('branch_id'));
                    }
                }),
            ],
        ];
    }
}
