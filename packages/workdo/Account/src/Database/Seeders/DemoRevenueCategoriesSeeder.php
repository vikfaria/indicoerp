<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\RevenueCategories;
use Illuminate\Database\Seeder;
use Workdo\Account\Models\ChartOfAccount;


class DemoRevenueCategoriesSeeder extends Seeder
{
    public function run($userId): void
    {
        $revenueCategories = [
            [
                'category_name' => 'Product Sales',
                'category_code' => 'REV-001',
                'description' => 'Revenue from product sales',
                'is_active' => true,
            ],
            [
                'category_name' => 'Service Income',
                'category_code' => 'REV-002',
                'description' => 'Revenue from services provided',
                'is_active' => true,
            ],
            [
                'category_name' => 'Consulting Fees',
                'category_code' => 'REV-003',
                'description' => 'Revenue from consulting services',
                'is_active' => true,
            ],
            [
                'category_name' => 'Subscription Revenue',
                'category_code' => 'REV-004',
                'description' => 'Revenue from subscription plans',
                'is_active' => true,
            ],
            [
                'category_name' => 'Interest Income',
                'category_code' => 'REV-005',
                'description' => 'Revenue from interest earnings',
                'is_active' => true,
            ],
        ];

        $revenueGLAccounts = ChartOfAccount::where('created_by', $userId)
            ->whereBetween('account_code', ['4000', '4999'])
            ->pluck('id')
            ->toArray();

        foreach ($revenueCategories as $index => $category) {
            RevenueCategories::create(array_merge($category, [
                'gl_account_id' => $revenueGLAccounts[$index % count($revenueGLAccounts)] ?? null,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
