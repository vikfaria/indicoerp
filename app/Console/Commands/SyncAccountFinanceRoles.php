<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use App\Services\AssistantActivation\PermissionMatrixService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncAccountFinanceRoles extends Command
{
    protected $signature = 'account:sync-finance-roles
                            {--company_id= : Restrict sync to one company user ID}';

    protected $description = 'Create/update standard finance roles and permission mappings for each company.';

    public function __construct(
        private readonly PermissionMatrixService $permissionMatrixService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $companyIdOption = $this->option('company_id');
        $companyId = is_numeric($companyIdOption) ? (int) $companyIdOption : null;

        $companies = User::query()
            ->where('type', 'company')
            ->when($companyId !== null && $companyId > 0, fn ($query) => $query->where('id', $companyId))
            ->orderBy('id')
            ->get(['id', 'email']);

        if ($companies->isEmpty()) {
            $this->info('No company users found for finance role sync.');

            return self::SUCCESS;
        }

        $availablePermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->flip();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roleTemplates = $this->permissionMatrixService->roleTemplates();
        $createdRoles = 0;
        $updatedRoles = 0;

        foreach ($companies as $company) {
            foreach ($roleTemplates as $blueprint) {
                $role = Role::query()->firstOrCreate(
                    [
                        'name' => $blueprint['name'],
                        'guard_name' => 'web',
                        'created_by' => (int) $company->id,
                    ],
                    [
                        'label' => $blueprint['label'],
                        'editable' => false,
                    ]
                );

                if ($role->wasRecentlyCreated) {
                    $createdRoles++;
                } else {
                    $updatedRoles++;
                }

                if ($role->label !== $blueprint['label'] || (bool) $role->editable !== false) {
                    $role->forceFill([
                        'label' => $blueprint['label'],
                        'editable' => false,
                    ])->save();
                }

                $permissionsToSync = collect($blueprint['permissions'])
                    ->filter(fn (string $permissionName) => $availablePermissions->has($permissionName))
                    ->values()
                    ->all();

                $role->syncPermissions($permissionsToSync);
            }

            $this->line(sprintf(
                'Company #%d (%s): synced %d finance roles.',
                (int) $company->id,
                (string) $company->email,
                count($roleTemplates)
            ));

            app(AssistantActivationCacheService::class)->touchCompanyVersion((int) $company->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(sprintf(
            'Finance role sync completed: created=%d, updated=%d.',
            $createdRoles,
            $updatedRoles
        ));

        return self::SUCCESS;
    }
}
