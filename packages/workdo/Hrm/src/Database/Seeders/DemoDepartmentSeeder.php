<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Branch;

class DemoDepartmentSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Department::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $branches = Branch::where('created_by', $userId)->pluck('id')->toArray();
        
        if (empty($branches)) {
            return;
        }

        $departments = [
            'Human Resources',
            'Information Technology',
            'Finance & Accounting',
            'Sales & Marketing',
            'Operations',
            'Customer Service',
            'Research & Development',
            'Quality Assurance',
            'Legal & Compliance',
            'Administration',
            'Production',
            'Procurement'
        ];

        // Ensure each branch gets at least 3 departments
        foreach ($branches as $branchId) {
            for ($i = 0; $i < 3; $i++) {
                $departmentName = $departments[($branchId * 3 + $i) % count($departments)];
                
                Department::updateOrCreate(
                    [
                        'department_name' => $departmentName,
                        'branch_id' => $branchId,
                        'created_by' => $userId
                    ],
                    [
                        'creator_id' => $userId
                    ]
                );
            }
        }
    }
}