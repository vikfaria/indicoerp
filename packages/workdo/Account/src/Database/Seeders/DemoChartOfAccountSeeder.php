<?php

namespace Workdo\Account\Database\Seeders;

use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Workdo\Account\Models\AccountType;


class DemoChartOfAccountSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 25; $i++) {
            ChartOfAccount::create([
                'account_code' => fake()->words(2, true),
                'account_name' => fake()->words(2, true),
                'level' => fake()->numberBetween(0, 1000),
                'normal_balance' => fake()->randomElement(["0", "1"]),
                'opening_balance' => fake()->randomFloat(2, 10, 1000),
                'current_balance' => fake()->randomFloat(2, 10, 1000),
                'is_active' => fake()->boolean(70),
                'is_system_account' => fake()->boolean(70),
                'description' => fake()->sentence(10),
                'account_type_id' => AccountType::where('created_by', $userId)->inRandomOrder()->first()?->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}