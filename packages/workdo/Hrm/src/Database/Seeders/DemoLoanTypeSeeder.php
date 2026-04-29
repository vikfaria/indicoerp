<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\LoanType;

class DemoLoanTypeSeeder extends Seeder
{
    public function run($userId)
    {
        if (LoanType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $loanTypes = [
            [
                'name' => 'Personal Loan',
                'description' => 'General purpose loan for personal financial needs and emergencies.'
            ],
            [
                'name' => 'Home Loan',
                'description' => 'Housing loan for purchasing or constructing residential property.'
            ],
            [
                'name' => 'Vehicle Loan',
                'description' => 'Loan for purchasing cars, motorcycles, or other vehicles.'
            ],
            [
                'name' => 'Education Loan',
                'description' => 'Educational loan for higher studies and professional courses.'
            ],
            [
                'name' => 'Medical Loan',
                'description' => 'Emergency loan for medical expenses and healthcare costs.'
            ],
            [
                'name' => 'Salary Advance',
                'description' => 'Short-term advance against future salary payments.'
            ],
            [
                'name' => 'Festival Advance',
                'description' => 'Seasonal advance for festival expenses and celebrations.'
            ],
            [
                'name' => 'Travel Loan',
                'description' => 'Loan for vacation, travel, and tourism expenses.'
            ],
            [
                'name' => 'Equipment Loan',
                'description' => 'Loan for purchasing work-related equipment and tools.'
            ],
            [
                'name' => 'Emergency Loan',
                'description' => 'Urgent financial assistance for unexpected emergencies.'
            ]
        ];

        foreach ($loanTypes as $loanType) {
            LoanType::updateOrCreate(
                [
                    'name' => $loanType['name'],
                    'created_by' => $userId
                ],
                [
                    'description' => $loanType['description'],
                    'creator_id' => $userId
                ]
            );
        }
    }
}