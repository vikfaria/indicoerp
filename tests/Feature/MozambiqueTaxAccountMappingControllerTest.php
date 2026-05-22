<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MozambiqueTaxAccountMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_index_gracefully_handles_missing_mapping_table(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-chart-of-accounts']);

        if (Schema::hasTable('mz_tax_account_mappings')) {
            Schema::drop('mz_tax_account_mappings');
        }

        $response = $this->actingAs($company)->get(route('account.mozambique-tax-account-mappings.index'));

        $response->assertOk();
    }

    public function test_store_returns_error_when_mapping_table_is_missing(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-chart-of-accounts']);

        if (Schema::hasTable('mz_tax_account_mappings')) {
            Schema::drop('mz_tax_account_mappings');
        }

        $response = $this->actingAs($company)->post(route('account.mozambique-tax-account-mappings.store'), [
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response->assertSessionHas('error');
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
