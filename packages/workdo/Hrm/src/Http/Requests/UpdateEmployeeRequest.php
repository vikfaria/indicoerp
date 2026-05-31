<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'date_of_birth' => 'required|date',
            'gender' => 'required',
            'shift_id' => [
                'required',
                'integer',
                Rule::exists('shifts', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'date_of_joining' => 'required|date',
            'employment_type' => 'required',
            'address_line_1' => 'required|max:255',
            'address_line_2' => 'nullable|max:255',
            'city' => 'required|max:100',
            'state' => 'required|max:100',
            'country' => 'required|max:100',
            'postal_code' => 'required|max:20',
            'emergency_contact_name' => 'required|max:100',
            'emergency_contact_relationship' => 'required|max:100',
            'emergency_contact_number' => 'required|max:20',
            'bank_name' => 'required|max:100',
            'account_holder_name' => 'required|max:100',
            'account_number' => 'required|max:50',
            'bank_identifier_code' => 'required|max:50',
            'bank_branch' => 'required|max:100',
            'tax_payer_id' => 'nullable|max:50',
            'basic_salary' => 'required|numeric|min:0',
            'hours_per_day' => 'required|numeric|min:0|max:24',
            'days_per_week' => 'required|numeric|min:0|max:7',
            'rate_per_hour' => 'required|numeric|min:0',
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
            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where(function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);

                    if ($this->filled('branch_id')) {
                        $query->where('branch_id', $this->input('branch_id'));
                    }

                    if ($this->filled('department_id')) {
                        $query->where('department_id', $this->input('department_id'));
                    }
                }),
            ],
            'documents' => 'nullable|array',
            'documents.*.document_type_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_document_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'documents.*.file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ];
    }
}
