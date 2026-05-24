<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Helpers\AccountUtility;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncCatalog('pt');
    }

    public function down(): void
    {
        $this->syncCatalog('en');
    }

    private function syncCatalog(string $locale): void
    {
        foreach (AccountUtility::accountCategoryDefinitions($locale) as $category) {
            DB::table('account_categories')
                ->where('code', $category['code'])
                ->update([
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'updated_at' => now(),
                ]);
        }

        foreach (AccountUtility::accountTypeDefinitions($locale) as $type) {
            DB::table('account_types')
                ->where('code', $type['code'])
                ->where('is_system_type', true)
                ->update([
                    'name' => $type['name'],
                    'normal_balance' => $type['normal_balance'],
                    'description' => $type['description'],
                    'updated_at' => now(),
                ]);
        }

        foreach (AccountUtility::chartOfAccountDefinitions($locale) as $account) {
            DB::table('chart_of_accounts')
                ->where('account_code', $account['account_code'])
                ->where('is_system_account', true)
                ->update([
                    'account_name' => $account['account_name'],
                    'normal_balance' => $account['normal_balance'],
                    'description' => $account['description'],
                    'updated_at' => now(),
                ]);
        }
    }
};
