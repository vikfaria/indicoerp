<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\DeductionType;

class DemoDeductionTypeSeeder extends Seeder
{
    public function run($userId)
    {
        if (DeductionType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $deductionTypes = [
            [
                'name' => 'Income Tax',
                'description' => 'Tax deducted at source from employee salary as per government regulations.'
            ],
            [
                'name' => 'Provident Fund (PF)',
                'description' => 'Employee contribution to provident fund for retirement savings.'
            ],
            [
                'name' => 'Employee State Insurance (ESI)',
                'description' => 'Medical insurance contribution deducted from employee salary.'
            ],
            [
                'name' => 'Professional Tax',
                'description' => 'State government tax deducted from employee salary.'
            ],
            [
                'name' => 'Loan Deduction',
                'description' => 'Monthly installment deduction for employee loans and advances.'
            ],
            [
                'name' => 'Late Coming Fine',
                'description' => 'Penalty deduction for late attendance and tardiness.'
            ],
            [
                'name' => 'Absence Deduction',
                'description' => 'Salary deduction for unauthorized leaves and absences.'
            ],
            [
                'name' => 'Canteen Charges',
                'description' => 'Deduction for employee meal expenses and canteen services.'
            ],
            [
                'name' => 'Insurance Premium',
                'description' => 'Employee contribution for health and life insurance premiums.'
            ],
            [
                'name' => 'Uniform Charges',
                'description' => 'Deduction for company uniform and safety equipment costs.'
            ]
        ];

        foreach ($deductionTypes as $deductionType) {
            DeductionType::updateOrCreate(
                [
                    'name' => $deductionType['name'],
                    'created_by' => $userId
                ],
                [
                    'description' => $deductionType['description'],
                    'creator_id' => $userId
                ]
            );
        }
    }
}