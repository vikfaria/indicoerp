<?php

namespace Workdo\Hrm\Database\Seeders;

use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Shift;

class DemoEmployeeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Employee::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        // Get available staff users who don't already have employee records
        $availableUsers = User::where('created_by', $userId)
            ->where('type', 'staff')
            ->whereNotIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
            ->pluck('id')
            ->toArray();

        $branches = Branch::where('created_by', $userId)->pluck('id')->toArray();
        $shifts = Shift::where('created_by', $userId)->pluck('id')->toArray();

        // Check if we have required data
        if (empty($availableUsers) || empty($branches) || empty($shifts)) {
            return;
        }

        $genders = ['Male', 'Female', 'Other'];

        $employmentTypes = ['Full Time','Part Time','Temporary','Contract'];
        $relationships = ['Spouse', 'Parent', 'Sibling', 'Friend', 'Relative'];
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego'];
        $states = ['California', 'Texas', 'Florida', 'New York', 'Pennsylvania', 'Illinois', 'Ohio', 'Georgia'];
        $countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Japan', 'India'];
        $bankNames = ['Chase Bank', 'Bank of America', 'Wells Fargo', 'Citibank', 'US Bank', 'PNC Bank', 'Capital One', 'TD Bank'];

        // Create employees for available users (limit to prevent too many)
        $usersToProcess = array_slice($availableUsers, 0, min(15, count($availableUsers)));

        foreach ($usersToProcess as $index => $userId_staff) {
            $employeeId = Employee::generateEmployeeId();
            
            // Select a random branch
            $branchId = $branches[array_rand($branches)];
            

            // Get departments for this branch
            $branchDepartments = Department::where('created_by', $userId)
                ->where('branch_id', $branchId)
                ->pluck('id')
                ->toArray();


            // Skip if no departments for this branch
            if (empty($branchDepartments)) {
                continue;
            }

            // Select a random department from this branch
            $departmentId = $branchDepartments[array_rand($branchDepartments)];

            // Get designations for this branch and department
            $branchDesignations = Designation::where('created_by', $userId)
                ->where('branch_id', $branchId)
                ->where('department_id', $departmentId)
                ->pluck('id')
                ->toArray();

            // Skip if no designations for this branch and department
            if (empty($branchDesignations)) {
                continue;
            }

            // Select a random designation from this branch and department
            $designationId = $branchDesignations[array_rand($branchDesignations)];

            Employee::updateOrCreate(
                [
                    'user_id' => $userId_staff,
                    'created_by' => $userId
                ],
                [
                    'employee_id' => $employeeId,
                    'date_of_birth' => now()->subYears(rand(25, 55))->subDays(rand(1, 365))->format('Y-m-d'),
                    'gender' => $genders[array_rand($genders)],
                    'shift' => $shifts[array_rand($shifts)],
                    'date_of_joining' => now()->subDays(rand(30, 1095))->format('Y-m-d'),
                    'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                    'address_line_1' => (rand(100, 9999)) . ' ' . ['Main St', 'Oak Ave', 'Park Blvd', 'First St', 'Second Ave'][array_rand(['Main St', 'Oak Ave', 'Park Blvd', 'First St', 'Second Ave'])],
                    'address_line_2' => rand(0, 1) ? 'Apt ' . rand(1, 999) : null,
                    'city' => $cities[array_rand($cities)],
                    'state' => $states[array_rand($states)],
                    'country' => $countries[array_rand($countries)],
                    'postal_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => ['John Smith', 'Jane Doe', 'Michael Johnson', 'Sarah Wilson', 'David Brown'][array_rand(['John Smith', 'Jane Doe', 'Michael Johnson', 'Sarah Wilson', 'David Brown'])],
                    'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                    'emergency_contact_number' => '+1' . rand(2000000000, 9999999999),
                    'bank_name' => $bankNames[array_rand($bankNames)],
                    'account_holder_name' => ['John Smith', 'Jane Doe', 'Michael Johnson', 'Sarah Wilson', 'David Brown'][array_rand(['John Smith', 'Jane Doe', 'Michael Johnson', 'Sarah Wilson', 'David Brown'])],
                    'account_number' => str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                    'bank_identifier_code' => strtoupper(substr($bankNames[array_rand($bankNames)], 0, 4)) . rand(1000, 9999),
                    'bank_branch' => ['Downtown', 'Uptown', 'Main Branch', 'North Branch', 'South Branch'][array_rand(['Downtown', 'Uptown', 'Main Branch', 'North Branch', 'South Branch'])],
                    'tax_payer_id' => 'TAX' . rand(100000000, 999999999),
                    'basic_salary' => rand(30000, 80000) + (rand(0, 99) / 100),
                    'hours_per_day' => rand(6, 8) + (rand(0, 1) ? 0.5 : 0),
                    'days_per_week' => rand(5, 7),
                    'rate_per_hour' => rand(15, 50) + (rand(0, 99) / 100),
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'designation_id' => $designationId,
                    'creator_id' => $userId,
                ]
            );
        }
    }
}
