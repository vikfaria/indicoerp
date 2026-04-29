<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|max:50',
            'date_of_birth' => 'required|date',
            'gender' => 'required',
            'shift_id' => 'required|exists:shifts,id',
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
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'documents' => 'required|array|min:1',
            'documents.*.document_type_id' => 'required|exists:employee_document_types,id',
            'documents.*.file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048'
        ];
    }
}