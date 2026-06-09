<?php

namespace Database\Seeders;

use App\Models\PgcAccountCatalog;
use Illuminate\Database\Seeder;

class PgcNirfSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/pgc_nirf_accounts.json');

        if (!file_exists($path)) {
            $this->command?->error('PGC-NIRF data file not found: ' . $path);
            return;
        }

        $accounts = json_decode(file_get_contents($path), true);

        if (!is_array($accounts)) {
            $this->command?->error('Invalid PGC-NIRF data file.');
            return;
        }

        $count = 0;

        foreach ($accounts as $account) {
            PgcAccountCatalog::updateOrCreate(
                [
                    'framework' => 'pgc_nirf',
                    'version' => '2025',
                    'account_code' => $account['code'],
                ],
                [
                    'class_number' => $account['class'],
                    'account_name' => $account['name'],
                    'parent_code' => $account['parent'],
                    'level' => $account['level'],
                    'normal_balance' => $account['balance'],
                    'is_movement_account' => $account['movement'],
                    'tax_type' => $account['tax'],
                    'financial_statement_line' => $account['fs_line'],
                    'description' => $account['description'] ?? null,
                ]
            );
            $count++;
        }

        $this->command?->info("PGC-NIRF: {$count} contas carregadas.");
    }
}
