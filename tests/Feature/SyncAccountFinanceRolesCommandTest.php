<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SyncAccountFinanceRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_account_finance_roles_command_creates_standard_finance_roles_for_company(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->provisionPermission('manage-account');
        $this->provisionPermission('manage-account-reports');
        $this->provisionPermission('create-vendor-payments');
        $this->provisionPermission('create-high-value-vendor-payments');
        $this->provisionPermission('create-customer-payments');
        $this->provisionPermission('create-high-value-customer-payments');

        $this->artisan('account:sync-finance-roles', [
            '--company_id' => $company->id,
        ])->assertExitCode(0);

        $roles = Role::query()
            ->where('created_by', $company->id)
            ->pluck('name')
            ->all();

        $this->assertCount(10, $roles);
        $this->assertContains('finance-administrator', $roles);
        $this->assertContains('finance-manager', $roles);
        $this->assertContains('finance-compliance-supervisor', $roles);

        $managerRole = Role::query()
            ->where('created_by', $company->id)
            ->where('name', 'finance-manager')
            ->firstOrFail();

        $this->assertTrue($managerRole->hasPermissionTo('create-high-value-vendor-payments'));
        $this->assertTrue($managerRole->hasPermissionTo('create-high-value-customer-payments'));
    }

    private function provisionPermission(string $permissionName): void
    {
        Permission::firstOrCreate(
            ['name' => $permissionName, 'guard_name' => 'web'],
            [
                'add_on' => 'general',
                'module' => 'tests',
                'label' => $permissionName,
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
