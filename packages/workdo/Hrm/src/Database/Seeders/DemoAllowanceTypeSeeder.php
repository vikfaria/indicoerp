<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\AllowanceType;

class DemoAllowanceTypeSeeder extends Seeder
{
    public function run($userId)
    {
        if (AllowanceType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $allowanceTypes = [
            [
                'name' => 'House Rent Allowance (HRA)',
                'description' => 'Monthly allowance for accommodation expenses including rent and utilities.'
            ],
            [
                'name' => 'Medical Allowance',
                'description' => 'Healthcare allowance covering medical expenses and insurance premiums.'
            ],
            [
                'name' => 'Transport Allowance',
                'description' => 'Travel allowance for daily commuting and transportation expenses.'
            ],
            [
                'name' => 'Food Allowance',
                'description' => 'Meal allowance covering lunch and food expenses during working hours.'
            ],
            [
                'name' => 'Mobile Allowance',
                'description' => 'Communication allowance for mobile phone and internet expenses.'
            ],
            [
                'name' => 'Education Allowance',
                'description' => 'Educational support allowance for employees children school fees.'
            ],
            [
                'name' => 'Performance Bonus',
                'description' => 'Merit-based allowance for exceptional performance and achievements.'
            ],
            [
                'name' => 'Overtime Allowance',
                'description' => 'Additional compensation for work beyond regular working hours.'
            ],
            [
                'name' => 'Shift Allowance',
                'description' => 'Extra allowance for night shifts and non-standard working hours.'
            ],
            [
                'name' => 'Travel Allowance',
                'description' => 'Business travel expenses including accommodation and meals.'
            ]
        ];

        foreach ($allowanceTypes as $allowanceType) {
            AllowanceType::updateOrCreate(
                [
                    'name' => $allowanceType['name'],
                    'created_by' => $userId
                ],
                [
                    'description' => $allowanceType['description'],
                    'creator_id' => $userId
                ]
            );
        }
    }
}