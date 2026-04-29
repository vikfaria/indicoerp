<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\ExpenseCategories;
use Illuminate\Database\Seeder;
use Workdo\Account\Models\ChartOfAccount;

class DemoExpenseCategoriesSeeder extends Seeder
{
    public function run($userId): void
    {
        $expenseCategories = [
            [
                'category_name' => 'Office Supplies',
                'category_code' => 'EXP-001',
                'description' => 'Expenses for office supplies and stationery',
                'is_active' => true,
            ],
            [
                'category_name' => 'Utilities',
                'category_code' => 'EXP-002',
                'description' => 'Expenses for electricity, water, and internet',
                'is_active' => true,
            ],
            [
                'category_name' => 'Rent',
                'category_code' => 'EXP-003',
                'description' => 'Office and property rent expenses',
                'is_active' => true,
            ],
            [
                'category_name' => 'Marketing',
                'category_code' => 'EXP-004',
                'description' => 'Marketing and advertising expenses',
                'is_active' => true,
            ],
            [
                'category_name' => 'Travel',
                'category_code' => 'EXP-005',
                'description' => 'Business travel and transportation expenses',
                'is_active' => true,
            ],
        ];

        $expenseGLAccounts = ChartOfAccount::where('created_by', $userId)
            ->whereBetween('account_code', ['5000', '5999'])
            ->pluck('id')
            ->toArray();

        foreach ($expenseCategories as $index => $category) {
            ExpenseCategories::create(array_merge($category, [
                'gl_account_id' => $expenseGLAccounts[$index % count($expenseGLAccounts)] ?? null,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
