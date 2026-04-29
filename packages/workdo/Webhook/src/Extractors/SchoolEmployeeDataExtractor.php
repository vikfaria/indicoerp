<?php

namespace Workdo\Webhook\Extractors;

class SchoolEmployeeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->employee)) return [];

        $employee = $event->employee;

        return [
            'school_employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'date_of_birth' => $employee->date_of_birth,
                'gender' => $employee->gender,
                'joining_date' => $employee->joining_date,
                'employee_type' => $employee->employee_type,
                'employment_status' => $employee->employment_status,
                'salary' => $employee->salary,
                'branch' => $employee->branch?->name ?? null,
                'department' => $employee->department?->name ?? null,
                'designation' => $employee->designation?->name ?? null,
                'created_at' => $employee->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
