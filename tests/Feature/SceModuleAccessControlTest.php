<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SceModuleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_user_without_permissions_cannot_access_sce_suite_pages(): void
    {
        $company = $this->makeCompany();

        $this->actingAs($company)->get(route('sce.journals.index'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.fiscal.index'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.fiscal.pgc'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.fiscal.series'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.fixed-assets.index'))->assertForbidden();
        $this->actingAs($company)->get(route('sce.reports.balance-sheet'))->assertForbidden();
    }

    public function test_view_tax_summary_can_view_sce_suite_but_cannot_mutate_accounting_data(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->actingAs($company)->get(route('sce.journals.index'))->assertOk();
        $this->actingAs($company)->get(route('sce.fiscal.index'))->assertOk();
        $this->actingAs($company)->get(route('sce.fixed-assets.index'))->assertOk();
        $this->actingAs($company)->get(route('sce.reports.balance-sheet'))->assertOk();

        $this->actingAs($company)
            ->from(route('sce.journals.index'))
            ->post(route('sce.journals.store'), [
                'name' => 'Diário Bloqueado',
                'prefix' => 'DBL',
                'type' => 'general',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('accounting_journals', [
            'company_id' => $company->id,
            'code' => 'DBL',
        ]);
    }

    public function test_manage_account_reports_can_manage_fiscal_profile_but_not_accounting_mutations(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->actingAs($company)
            ->from(route('sce.fiscal.index'))
            ->post(route('sce.fiscal.update-profile'), [
                'nuit' => '400123456',
                'fiscal_regime' => 'normal',
                'accounting_framework' => 'pgc_nirf',
                'entity_classification' => 'medium',
                'province' => 'Maputo',
                'economic_activity_code' => 'A011',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('company_fiscal_profiles', [
            'company_id' => $company->id,
            'nuit' => '400123456',
            'accounting_framework' => 'pgc_nirf',
        ]);

        $this->actingAs($company)
            ->from(route('sce.journals.index'))
            ->post(route('sce.journals.store'), [
                'name' => 'Diário Fiscalista',
                'prefix' => 'DFS',
                'type' => 'general',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('accounting_journals', [
            'company_id' => $company->id,
            'code' => 'DFS',
        ]);
    }

    public function test_manage_account_can_create_journal_and_fixed_asset(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account']);

        $this->actingAs($company)
            ->from(route('sce.journals.index'))
            ->post(route('sce.journals.store'), [
                'name' => 'Diário Operacional',
                'prefix' => 'DOP',
                'type' => 'general',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounting_journals', [
            'company_id' => $company->id,
            'code' => 'DOP',
            'name' => 'Diário Operacional',
        ]);

        $this->actingAs($company)
            ->from(route('sce.fixed-assets.index'))
            ->post(route('sce.fixed-assets.store'), [
                'asset_code' => 'AF-0001',
                'name' => 'Laptop Contabilidade',
                'category' => 'tangible',
                'acquisition_date' => now()->toDateString(),
                'acquisition_cost' => 45000,
                'useful_life_months' => 36,
                'depreciation_method' => 'straight_line',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fixed_assets', [
            'company_id' => $company->id,
            'asset_code' => 'AF-0001',
            'name' => 'Laptop Contabilidade',
        ]);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
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
    }
}
