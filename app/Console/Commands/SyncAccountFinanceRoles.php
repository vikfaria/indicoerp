<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncAccountFinanceRoles extends Command
{
    protected $signature = 'account:sync-finance-roles
                            {--company_id= : Restrict sync to one company user ID}';

    protected $description = 'Create/update standard finance roles and permission mappings for each company.';

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

        $createdRoles = 0;
        $updatedRoles = 0;

        foreach ($companies as $company) {
            foreach ($this->roleBlueprints() as $blueprint) {
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
                count($this->roleBlueprints())
            ));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(sprintf(
            'Finance role sync completed: created=%d, updated=%d.',
            $createdRoles,
            $updatedRoles
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, label: string, permissions: array<int, string>}>
     */
    private function roleBlueprints(): array
    {
        return [
            [
                'name' => 'finance-administrator',
                'label' => 'Administrador Financeiro',
                'permissions' => [
                    'manage-account',
                    'manage-account-dashboard',
                    'manage-account-reports',
                    'view-tax-summary',
                    'print-tax-summary',
                    'manage-bank-accounts',
                    'manage-any-bank-accounts',
                    'view-bank-accounts',
                    'create-bank-accounts',
                    'edit-bank-accounts',
                    'manage-vendor-payments',
                    'manage-any-vendor-payments',
                    'view-vendor-payments',
                    'create-vendor-payments',
                    'create-high-value-vendor-payments',
                    'create-foreign-currency-vendor-payments',
                    'use-all-bank-accounts-for-vendor-payments',
                    'approve-vendor-payments',
                    'cleared-vendor-payments',
                    'manage-customer-payments',
                    'manage-any-customer-payments',
                    'view-customer-payments',
                    'create-customer-payments',
                    'create-high-value-customer-payments',
                    'create-foreign-currency-customer-payments',
                    'use-all-bank-accounts-for-customer-payments',
                    'approve-customer-payments',
                    'cleared-customer-payments',
                    'manage-bank-transactions',
                    'reconcile-bank-transactions',
                ],
            ],
            [
                'name' => 'finance-billing',
                'label' => 'Faturacao',
                'permissions' => [
                    'manage-account-dashboard',
                    'view-customers',
                    'create-customers',
                    'edit-customers',
                    'manage-customer-payments',
                    'manage-own-customer-payments',
                    'view-customer-payments',
                    'create-customer-payments',
                    'create-foreign-currency-customer-payments',
                    'manage-account-reports',
                    'view-customer-balance',
                    'view-customer-detail-report',
                    'view-invoice-aging',
                ],
            ],
            [
                'name' => 'finance-treasury',
                'label' => 'Tesouraria',
                'permissions' => [
                    'manage-account-dashboard',
                    'manage-bank-accounts',
                    'manage-own-bank-accounts',
                    'view-bank-accounts',
                    'create-bank-accounts',
                    'edit-bank-accounts',
                    'manage-vendor-payments',
                    'manage-own-vendor-payments',
                    'view-vendor-payments',
                    'create-vendor-payments',
                    'create-foreign-currency-vendor-payments',
                    'manage-customer-payments',
                    'manage-own-customer-payments',
                    'view-customer-payments',
                    'create-customer-payments',
                    'create-foreign-currency-customer-payments',
                    'manage-bank-transactions',
                    'reconcile-bank-transactions',
                ],
            ],
            [
                'name' => 'finance-accountant',
                'label' => 'Contabilista',
                'permissions' => [
                    'manage-account',
                    'manage-account-dashboard',
                    'manage-account-reports',
                    'view-tax-summary',
                    'print-tax-summary',
                    'manage-vendor-payments',
                    'view-vendor-payments',
                    'manage-customer-payments',
                    'view-customer-payments',
                    'manage-bank-transactions',
                    'reconcile-bank-transactions',
                ],
            ],
            [
                'name' => 'finance-tax-specialist',
                'label' => 'Fiscalista',
                'permissions' => [
                    'manage-account-reports',
                    'view-tax-summary',
                    'print-tax-summary',
                    'view-vendor-payments',
                    'view-customer-payments',
                    'view-customer-detail-report',
                    'view-vendor-detail-report',
                ],
            ],
            [
                'name' => 'finance-auditor',
                'label' => 'Auditor Financeiro',
                'permissions' => [
                    'manage-account-dashboard',
                    'manage-account-reports',
                    'view-bank-accounts',
                    'view-vendor-payments',
                    'view-customer-payments',
                    'view-tax-summary',
                    'view-invoice-aging',
                    'view-bill-aging',
                    'view-customer-detail-report',
                    'view-vendor-detail-report',
                ],
            ],
            [
                'name' => 'finance-manager',
                'label' => 'Gestor Financeiro',
                'permissions' => [
                    'manage-account',
                    'manage-account-dashboard',
                    'manage-account-reports',
                    'view-tax-summary',
                    'print-tax-summary',
                    'manage-vendor-payments',
                    'manage-customer-payments',
                    'approve-vendor-payments',
                    'approve-customer-payments',
                    'create-high-value-vendor-payments',
                    'create-high-value-customer-payments',
                    'manage-bank-transactions',
                ],
            ],
            [
                'name' => 'finance-payment-approver',
                'label' => 'Aprovador de Pagamentos',
                'permissions' => [
                    'manage-vendor-payments',
                    'manage-own-vendor-payments',
                    'view-vendor-payments',
                    'approve-vendor-payments',
                    'cleared-vendor-payments',
                    'manage-customer-payments',
                    'manage-own-customer-payments',
                    'view-customer-payments',
                    'approve-customer-payments',
                    'cleared-customer-payments',
                    'create-high-value-vendor-payments',
                    'create-high-value-customer-payments',
                ],
            ],
            [
                'name' => 'finance-cash-operator',
                'label' => 'Operador de Caixa',
                'permissions' => [
                    'manage-account-dashboard',
                    'manage-bank-accounts',
                    'manage-own-bank-accounts',
                    'view-bank-accounts',
                    'manage-vendor-payments',
                    'manage-own-vendor-payments',
                    'view-vendor-payments',
                    'create-vendor-payments',
                    'manage-customer-payments',
                    'manage-own-customer-payments',
                    'view-customer-payments',
                    'create-customer-payments',
                    'manage-bank-transactions',
                ],
            ],
            [
                'name' => 'finance-compliance-supervisor',
                'label' => 'Supervisor de Compliance',
                'permissions' => [
                    'manage-account-dashboard',
                    'manage-account-reports',
                    'view-tax-summary',
                    'print-tax-summary',
                    'view-vendor-payments',
                    'view-customer-payments',
                    'approve-vendor-payments',
                    'approve-customer-payments',
                    'create-high-value-vendor-payments',
                    'create-high-value-customer-payments',
                    'create-foreign-currency-vendor-payments',
                    'create-foreign-currency-customer-payments',
                    'view-vendor-detail-report',
                    'view-customer-detail-report',
                ],
            ],
        ];
    }
}
