<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;

class DemoDesignationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Designation::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $branches = Branch::where('created_by', $userId)->get();
        
        if ($branches->isEmpty()) {
            return;
        }

        $designations = [
            'Director', 'Manager', 'Assistant Manager', 'Team Lead', 'Senior Executive',
            'Executive', 'Senior Analyst', 'Analyst', 'Specialist', 'Coordinator',
            'Supervisor', 'Officer', 'Associate', 'Senior Associate', 'Junior Executive',
            'Senior Consultant', 'Consultant', 'Administrator', 'Senior Administrator', 'Assistant'
        ];

        foreach ($branches as $branch) {
            $departments = Department::where('created_by', $userId)
                ->where('branch_id', $branch->id)
                ->get();
            
            foreach ($departments as $department) {
                $designationCount = rand(4, 5);
                
                for ($i = 0; $i < $designationCount; $i++) {
                    $designationName = $designations[($department->id * 5 + $i) % count($designations)];
                    
                    Designation::updateOrCreate(
                        [
                            'designation_name' => $designationName,
                            'branch_id' => $branch->id,
                            'department_id' => $department->id,
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
}