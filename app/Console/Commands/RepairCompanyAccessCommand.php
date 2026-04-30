<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class RepairCompanyAccessCommand extends Command
{
    protected $signature = 'app:repair-company-access';

    protected $description = 'Repair company roles and permissions so login, plans, and dashboard access work correctly.';

    public function handle(): int
    {
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->info('No company users found.');
            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($companies as $company) {
            $role = $company->ensureCompanyAccessRole();

            if ($role) {
                $fixed++;
                $this->line("Fixed company user #{$company->id} ({$company->email}) with role #{$role->id}.");
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Company access repaired for {$fixed} user(s).");

        return self::SUCCESS;
    }
}
