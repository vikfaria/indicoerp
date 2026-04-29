<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\Revenue;
use Workdo\Account\Models\RevenueCategories;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class DemoRevenueSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = \Faker\Factory::create();

        $revenues = [
            [
                'revenue_date' => now()->subDays(30),
                'amount' => 5000.00,
                'description' => 'Product sales revenue',
                'reference_number' => 'REF-001',
                'status' => 'draft',
            ],
            [
                'revenue_date' => now()->subDays(25),
                'amount' => 3500.00,
                'description' => 'Service revenue',
                'reference_number' => 'REF-002',
                'status' => 'draft',
            ],
            [
                'revenue_date' => now()->subDays(20),
                'amount' => 7500.00,
                'description' => 'Consulting revenue',
                'reference_number' => 'REF-003',
                'status' => 'draft',
            ],
            [
                'revenue_date' => now()->subDays(15),
                'amount' => 2000.00,
                'description' => 'License revenue',
                'reference_number' => 'REF-004',
                'status' => 'draft',
            ],
            [
                'revenue_date' => now()->subDays(10),
                'amount' => 4200.00,
                'description' => 'Subscription revenue',
                'reference_number' => 'REF-005',
                'status' => 'draft',
            ],
        ];
        foreach ($revenues as $revenue) {
            Revenue::create(array_merge($revenue, [
                'category_id' => RevenueCategories::where('created_by', $userId)->where('is_active', true)->inRandomOrder()->first()->id,
                'bank_account_id' => BankAccount::where('created_by', $userId)->where('is_active', true)->inRandomOrder()->first()->id,
                'chart_of_account_id' => ChartOfAccount::where('created_by', $userId)
                    ->where('is_active', true)
                    ->whereBetween('account_code', ['4000', '4999'])
                    ->inRandomOrder()->first()->id,
                'approved_by' => null,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
