<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\Expense;
use Workdo\Account\Models\ExpenseCategories;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class DemoExpenseSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = \Faker\Factory::create();

        $expenses = [
            [
                'expense_date' => now()->subDays(30),
                'amount' => 1500.00,
                'description' => 'Office rent payment',
                'reference_number' => 'EXP-001',
                'status' => 'draft',
            ],
            [
                'expense_date' => now()->subDays(25),
                'amount' => 800.00,
                'description' => 'Utility bills payment',
                'reference_number' => 'EXP-002',
                'status' => 'draft',
            ],
            [
                'expense_date' => now()->subDays(20),
                'amount' => 2500.00,
                'description' => 'Marketing campaign expense',
                'reference_number' => 'EXP-003',
                'status' => 'draft',
            ],
            [
                'expense_date' => now()->subDays(15),
                'amount' => 600.00,
                'description' => 'Office supplies purchase',
                'reference_number' => 'EXP-004',
                'status' => 'draft',
            ],
            [
                'expense_date' => now()->subDays(10),
                'amount' => 1200.00,
                'description' => 'Travel and accommodation',
                'reference_number' => 'EXP-005',
                'status' => 'draft',
            ],
        ];

        foreach ($expenses as $expense) {
            Expense::create(array_merge($expense, [
                'category_id' => ExpenseCategories::where('created_by', $userId)->where('is_active', true)->inRandomOrder()->first()->id,
                'bank_account_id' => BankAccount::where('created_by', $userId)->where('is_active', true)->inRandomOrder()->first()->id,
                'chart_of_account_id' => ChartOfAccount::where('created_by', $userId)
                    ->where('is_active', true)
                    ->whereBetween('account_code', ['5000', '6999'])
                    ->inRandomOrder()->first()?->id,
                'approved_by' => null,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
