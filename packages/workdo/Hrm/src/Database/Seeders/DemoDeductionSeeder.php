<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Deduction;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\DeductionType;
use App\Models\User;

class DemoDeductionSeeder extends Seeder
{
    public function run($userId)
    {
        if (Deduction::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        // Get employees (users who exist in employees table)
        $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
            ->where('created_by', $userId)
            ->pluck('id')
            ->toArray();

        $deductionTypes = DeductionType::where('created_by', $userId)->pluck('id')->toArray();

        // Check if we have required data
        if (empty($employees) || empty($deductionTypes)) {
            return;
        }

        // Shuffle arrays for randomness
        shuffle($employees);
        shuffle($deductionTypes);

        $fixedAmounts = [100, 200, 300, 400, 500, 250, 150, 350];
        $percentageAmounts = [2, 3, 5, 7, 10, 12, 8, 6];
        $types = ['fixed', 'percentage'];

        // Create 4 unique deductions for each employee
        foreach ($employees as $employeeId) {
            // Skip if employee already has 4 or more deductions
            $existingCount = Deduction::where('employee_id', $employeeId)
                ->where('created_by', $userId)
                ->count();
            
            if ($existingCount >= 4) {
                continue;
            }

            $deductionsToCreate = 4 - $existingCount;
            $shuffledDeductionTypes = $deductionTypes;
            shuffle($shuffledDeductionTypes);

            for ($i = 0; $i < $deductionsToCreate && $i < count($shuffledDeductionTypes); $i++) {
                $deductionTypeId = $shuffledDeductionTypes[$i];

                $type = $types[array_rand($types)];
                $amount = $type === 'fixed'
                    ? $fixedAmounts[array_rand($fixedAmounts)]
                    : $percentageAmounts[array_rand($percentageAmounts)];

                Deduction::create([
                    'employee_id' => $employeeId,
                    'deduction_type_id' => $deductionTypeId,
                    'type' => $type,
                    'amount' => $amount,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
