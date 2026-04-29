<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Allowance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\AllowanceType;
use App\Models\User;

class DemoAllowanceSeeder extends Seeder
{
    public function run($userId)
    {
        if (Allowance::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        // Get employees (users who exist in employees table)
        $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
            ->where('created_by', $userId)
            ->pluck('id')
            ->toArray();

        $allowanceTypes = AllowanceType::where('created_by', $userId)->pluck('id')->toArray();

        // Check if we have required data
        if (empty($employees) || empty($allowanceTypes)) {
            return;
        }

        // Shuffle arrays for randomness
        shuffle($employees);
        shuffle($allowanceTypes);

        $fixedAmounts = [3000, 5000, 7500, 1000, 4200, 2800, 1600, 6400];
        $percentageAmounts = [5, 8, 10, 12, 15, 18, 20, 25];
        $types = ['fixed', 'percentage'];

        // Create multiple allowances for each employee
        foreach ($employees as $employeeId) {
            // Skip if employee already has 4 or more allowances
            $existingCount = Allowance::where('employee_id', $employeeId)
                ->where('created_by', $userId)
                ->count();

            if ($existingCount >= 4) {
                continue;
            }

            $allowancesToCreate = 4 - $existingCount;
            $shuffledAllowanceTypes = $allowanceTypes;
            shuffle($shuffledAllowanceTypes);

            for ($i = 0; $i < $allowancesToCreate && $i < count($shuffledAllowanceTypes); $i++) {
                $allowanceTypeId = $shuffledAllowanceTypes[$i];

                $type = $types[array_rand($types)];
                $amount = $type === 'fixed'
                    ? $fixedAmounts[array_rand($fixedAmounts)]
                    : $percentageAmounts[array_rand($percentageAmounts)];

                Allowance::create([
                    'employee_id' => $employeeId,
                    'allowance_type_id' => $allowanceTypeId,
                    'type' => $type,
                    'amount' => $amount,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
