<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Branch;

class CompanyFinanceRoleSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_dashboard_access_syncs_real_profile_matrix(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        $this->provisionPermissions([
            'manage-dashboard',
            'manage-plans',
            'view-plans',
            'manage-profile',
            'edit-profile',
            'change-password-profile',
            'manage-media',
            'manage-own-media',
            'create-media',
            'download-media',
            'delete-media',
            'manage-media-directories',
            'manage-own-media-directories',
            'create-media-directories',
            'edit-media-directories',
            'delete-media-directories',
            'manage-messenger',
            'send-messages',
            'view-messages',
            'toggle-favorite-messages',
            'toggle-pinned-messages',
            'manage-sales-invoices',
            'manage-own-sales-invoices',
            'view-sales-invoices',
            'print-sales-invoices',
            'manage-sales-return-invoices',
            'manage-own-sales-return-invoices',
            'view-sales-return-invoices',
            'manage-sales-proposals',
            'manage-own-sales-proposals',
            'view-sales-proposals',
            'print-sales-proposals',
            'accept-sales-proposals',
            'reject-sales-proposals',
            'manage-purchase-invoices',
            'manage-own-purchase-invoices',
            'view-purchase-invoices',
            'print-purchase-invoices',
            'manage-purchase-return-invoices',
            'manage-own-purchase-return-invoices',
            'view-purchase-return-invoices',
            'manage-hrm-dashboard',
            'manage-hrm',
            'manage-employees',
            'manage-any-employees',
            'view-employees',
            'manage-attendances',
            'manage-any-attendances',
            'view-attendances',
            'manage-leave-applications',
            'manage-any-leave-applications',
            'view-leave-applications',
            'manage-payrolls',
            'manage-any-payrolls',
            'view-payrolls',
            'view-any-payrolls',
            'view-own-payrolls',
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
            'view-customer-balance',
            'view-vendor-balance',
        ]);

        $this->assertSame(0, Role::query()->where('created_by', $company->id)->count());

        $response = $this->actingAs($company)->get(route('dashboard'));

        $response->assertOk();

        $this->assertDatabaseHas('branches', [
            'created_by' => $company->id,
            'branch_name' => 'Main Office',
        ]);

        $roles = Role::query()
            ->where('created_by', $company->id)
            ->pluck('name')
            ->all();

        $this->assertCount(15, $roles);
        $this->assertContains('company', $roles);
        $this->assertContains('staff', $roles);
        $this->assertContains('client', $roles);
        $this->assertContains('vendor', $roles);
        $this->assertContains('hr', $roles);
        $this->assertContains('finance-administrator', $roles);
        $this->assertContains('finance-billing', $roles);
        $this->assertContains('finance-treasury', $roles);
        $this->assertContains('finance-accountant', $roles);
        $this->assertContains('finance-tax-specialist', $roles);
        $this->assertContains('finance-auditor', $roles);
        $this->assertContains('finance-manager', $roles);
        $this->assertContains('finance-payment-approver', $roles);
        $this->assertContains('finance-cash-operator', $roles);
        $this->assertContains('finance-compliance-supervisor', $roles);

        $companyRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'company')
            ->firstOrFail();
        $this->assertTrue($companyRole->hasPermissionTo('manage-dashboard'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-plans'));
        $this->assertTrue($companyRole->hasPermissionTo('view-plans'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-profile'));

        $staffRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'staff')
            ->firstOrFail();
        $this->assertTrue($staffRole->hasPermissionTo('manage-media'));
        $this->assertTrue($staffRole->hasPermissionTo('manage-messenger'));
        $this->assertTrue($staffRole->hasPermissionTo('send-messages'));
        $this->assertTrue($staffRole->hasPermissionTo('toggle-pinned-messages'));

        $clientRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'client')
            ->firstOrFail();
        $this->assertTrue($clientRole->hasPermissionTo('manage-sales-invoices'));
        $this->assertTrue($clientRole->hasPermissionTo('manage-sales-proposals'));
        $this->assertTrue($clientRole->hasPermissionTo('accept-sales-proposals'));

        $vendorRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'vendor')
            ->firstOrFail();
        $this->assertTrue($vendorRole->hasPermissionTo('manage-purchase-invoices'));
        $this->assertTrue($vendorRole->hasPermissionTo('manage-purchase-return-invoices'));

        $hrRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'hr')
            ->firstOrFail();
        $this->assertTrue($hrRole->hasPermissionTo('manage-hrm'));
        $this->assertTrue($hrRole->hasPermissionTo('manage-employees'));
        $this->assertTrue($hrRole->hasPermissionTo('manage-payrolls'));
        $this->assertTrue($hrRole->hasPermissionTo('manage-leave-applications'));

        $financeManager = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'finance-manager')
            ->firstOrFail();

        $this->assertTrue($financeManager->hasPermissionTo('manage-account-reports'));
        $this->assertTrue($financeManager->hasPermissionTo('approve-vendor-payments'));
        $this->assertTrue($financeManager->hasPermissionTo('create-high-value-vendor-payments'));
        $this->assertTrue($financeManager->hasPermissionTo('create-high-value-customer-payments'));

        $financeTreasury = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'finance-treasury')
            ->firstOrFail();
        $this->assertTrue($financeTreasury->hasPermissionTo('manage-bank-accounts'));
        $this->assertTrue($financeTreasury->hasPermissionTo('manage-bank-transactions'));
        $this->assertTrue($financeTreasury->hasPermissionTo('reconcile-bank-transactions'));

        $financeAuditor = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'finance-auditor')
            ->firstOrFail();
        $this->assertTrue($financeAuditor->hasPermissionTo('view-bank-accounts'));
        $this->assertTrue($financeAuditor->hasPermissionTo('view-tax-summary'));
        $this->assertFalse($financeAuditor->hasPermissionTo('create-vendor-payments'));

        $financeComplianceSupervisor = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'finance-compliance-supervisor')
            ->firstOrFail();
        $this->assertTrue($financeComplianceSupervisor->hasPermissionTo('manage-account-reports'));
        $this->assertTrue($financeComplianceSupervisor->hasPermissionTo('view-tax-summary'));
    }

    public function test_company_access_role_backfills_missing_permissions_from_the_template_role(): void
    {
        $this->provisionPermissions([
            'manage-dashboard',
            'manage-stock',
            'create-stock',
            'manage-bank-accounts',
            'manage-any-bank-accounts',
            'manage-own-bank-accounts',
            'view-bank-accounts',
            'create-bank-accounts',
            'edit-bank-accounts',
            'delete-bank-accounts',
            'manage-bank-transactions',
            'reconcile-bank-transactions',
            'manage-bank-transfers',
            'manage-any-bank-transfers',
            'manage-own-bank-transfers',
            'view-bank-transfers',
            'create-bank-transfers',
            'edit-bank-transfers',
            'delete-bank-transfers',
            'process-bank-transfers',
        ]);

        $superadmin = User::forceCreate([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => 'password',
            'type' => 'superadmin',
            'email_verified_at' => now(),
        ]);

        $templateRole = Role::firstOrCreate(
            [
                'name' => 'company',
                'guard_name' => 'web',
                'created_by' => $superadmin->id,
            ],
            [
                'label' => 'Company',
                'editable' => false,
            ]
        );

        $templateRole->syncPermissions(['manage-dashboard', 'manage-stock']);

        $company = User::forceCreate([
            'name' => 'Company User',
            'email' => 'company@example.com',
            'password' => 'password',
            'type' => 'company',
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        $companyRole = Role::firstOrCreate(
            [
                'name' => 'company',
                'guard_name' => 'web',
                'created_by' => $company->id,
            ],
            [
                'label' => 'Company',
                'editable' => false,
            ]
        );

        $companyRole->syncPermissions(['manage-dashboard']);
        $company->syncRoles([$companyRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $company->ensureCompanyAccessRole();

        $companyRole->refresh();

        $this->assertTrue($companyRole->hasPermissionTo('manage-dashboard'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-stock'));
        $this->assertTrue($companyRole->hasPermissionTo('create-stock'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-any-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-own-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('create-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('edit-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('delete-bank-accounts'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-bank-transactions'));
        $this->assertTrue($companyRole->hasPermissionTo('reconcile-bank-transactions'));
        $this->assertTrue($companyRole->hasPermissionTo('manage-bank-transfers'));
        $this->assertTrue($companyRole->hasPermissionTo('process-bank-transfers'));
    }

    /**
     * @param array<int, string> $permissionNames
     */
    private function provisionPermissions(array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
