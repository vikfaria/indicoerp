<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Loan;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LoanType;
use App\Models\User;

class DemoLoanSeeder extends Seeder
{
    public function run($userId)
    {
        if (Loan::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        // Get employees (users who exist in employees table)
        $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
            ->where('created_by', $userId)
            ->pluck('id')
            ->toArray();

        $loanTypes = LoanType::where('created_by', $userId)->pluck('id')->toArray();

        // Check if we have required data
        if (empty($employees) || empty($loanTypes)) {
            return;
        }

        // Shuffle arrays for randomness
        shuffle($employees);
        shuffle($loanTypes);

        $fixedAmounts = [1000, 2000, 5000, 10000, 1500, 15000, 5000, 2000];
        $percentageAmounts = [2, 5, 10, 3, 5, 4, 2, 5];
        $types = ['fixed', 'percentage'];

        $loanTitles = [
            'Emergency Fund Request',
            'Home Purchase Loan',
            'Vehicle Finance',
            'Education Expenses',
            'Medical Emergency',
            'Personal Development',
            'Family Support',
            'Business Investment'
        ];

        $reasons = [
            'Required for urgent financial needs',
            'Investment in property purchase',
            'Vehicle acquisition for transportation',
            'Educational advancement and skill development',
            'Medical treatment and healthcare expenses',
            'Personal growth and development activities',
            'Supporting family financial requirements',
            'Business expansion and investment opportunities'
        ];

        // Create loans for each employee
        foreach ($employees as $employeeId) {
            // Skip if employee already has 4 or more loans
            $existingCount = Loan::where('employee_id', $employeeId)
                ->where('created_by', $userId)
                ->count();

            if ($existingCount >= 4) {
                continue;
            }

            $loansToCreate = 4 - $existingCount;
            $shuffledLoanTypes = $loanTypes;
            shuffle($shuffledLoanTypes);

            for ($i = 0; $i < $loansToCreate && $i < count($shuffledLoanTypes); $i++) {
                $loanTypeId = $shuffledLoanTypes[$i];

                $type = $types[array_rand($types)];
                $amount = $type === 'fixed'
                    ? $fixedAmounts[array_rand($fixedAmounts)]
                    : $percentageAmounts[array_rand($percentageAmounts)];

                $startDate = now()->subDays(rand(0, 30));
                $endDate = $startDate->copy()->addMonths(rand(6, 24));

                Loan::create([
                    'title' => $loanTitles[array_rand($loanTitles)],
                    'employee_id' => $employeeId,
                    'loan_type_id' => $loanTypeId,
                    'type' => $type,
                    'amount' => $amount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => $reasons[array_rand($reasons)],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
