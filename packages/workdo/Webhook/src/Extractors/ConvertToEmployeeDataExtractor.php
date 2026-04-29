<?php

namespace Workdo\Webhook\Extractors;

class ConvertToEmployeeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->employee)) return [];

        $employee = $event->employee;
        $candidate = $event->offer?->candidate;

        return [
            'convert_to_employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'employee_name' => $candidate ? ($candidate->first_name . ' ' . $candidate->last_name) : null,
                'email' => $candidate?->email ?? null,
                'phone' => $candidate?->phone ?? null,
                'date_of_birth' => $employee->date_of_birth,
                'gender' => $employee->gender,
                'shift' => $employee->shift,
                'attendance_policy' => $employee->attendance_policy,
                'date_of_joining' => $employee->date_of_joining,
                'employment_type' => $employee->employment_type,
                'address_line_1' => $employee->address_line_1,
                'address_line_2' => $employee->address_line_2,
                'city' => $employee->city,
                'state' => $employee->state,
                'country' => $employee->country,
                'postal_code' => $employee->postal_code,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_relationship' => $employee->emergency_contact_relationship,
                'emergency_contact_number' => $employee->emergency_contact_number,
                'bank_name' => $employee->bank_name,
                'account_holder_name' => $employee->account_holder_name,
                'account_number' => $employee->account_number,
                'bank_identifier_code' => $employee->bank_identifier_code,
                'bank_branch' => $employee->bank_branch,
                'tax_payer_id' => $employee->tax_payer_id,
                'branch' => $employee->branch?->branch_name ?? null,
                'department' => $employee->department?->department_name ?? null,
                'designation' => $employee->designation?->designation_name ?? null,
                'created_at' => $employee->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
