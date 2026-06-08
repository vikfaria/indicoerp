<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\Plan;
use App\Models\TenantFeatureOverride;
use App\Models\User;
use App\Services\AssistantActivation\PlanFeatureResolver;
use App\Services\AssistantActivation\PlanLimitResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssistantActivationTenantFeatureOverrideTest extends TestCase
{
    use RefreshDatabase;

    private array $originalLimitDimensions = [];
    private array $originalFreeFamilyLimits = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalLimitDimensions = (array) config('assistant_activation_limits.dimensions', []);
        $this->originalFreeFamilyLimits = (array) config('assistant_activation_limits.plan_families.free.limits', []);

        Cache::flush();
        app(Module::class)->moduleCacheForget();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->registerTestRoutes();
    }

    protected function tearDown(): void
    {
        Config::set('assistant_activation_limits.dimensions', $this->originalLimitDimensions);
        Config::set('assistant_activation_limits.plan_families.free.limits', $this->originalFreeFamilyLimits);

        parent::tearDown();
    }

    public function test_it_can_create_and_delete_company_overrides_through_settings_routes(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = $this->makeCompany('Empresa Override');

        $payload = [
            'override' => [
                'company_id' => $company->id,
                'override_type' => 'feature',
                'override_key' => 'billing.product.manage',
                'notes' => 'Excepção aprovada para validação',
            ],
        ];

        $this->actingAs($superAdmin)
            ->post(route('settings.company-overrides.store'), $payload)
            ->assertRedirect();

        $override = TenantFeatureOverride::query()
            ->where('company_id', $company->id)
            ->where('override_type', 'feature')
            ->where('override_key', 'billing.product.manage')
            ->first();

        $this->assertNotNull($override);
        $this->assertSame('Excepção aprovada para validação', $override?->notes);

        $this->actingAs($superAdmin)
            ->delete(route('settings.company-overrides.destroy', $override))
            ->assertRedirect();

        $this->assertDatabaseMissing('tenant_feature_overrides', [
            'id' => $override->id,
        ]);
    }

    public function test_feature_override_allows_expired_company_to_access_feature_gate(): void
    {
        $plan = $this->makePlan('Free Plan');
        $company = $this->makeExpiredCompany($plan);

        TenantFeatureOverride::create([
            'company_id' => $company->id,
            'override_type' => 'feature',
            'override_key' => 'billing.product.manage',
            'notes' => 'Liberado para validar o fluxo',
        ]);

        $resolution = app(PlanFeatureResolver::class)->resolve('billing.product.manage', $company);

        $this->assertSame('active', $resolution['state']);
        $this->assertSame(['tenant_override'], $resolution['reasons']);
        $this->assertSame([], $resolution['missing_permissions']);
        $this->assertSame([], $resolution['missing_config_keys']);

        $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->get('/__tests/override-feature')
            ->assertOk()
            ->assertSeeText('feature ok');

        $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->get('/__tests/override-feature-module')
            ->assertOk()
            ->assertSeeText('feature module ok');
    }

    public function test_limit_override_allows_expired_company_to_create_through_limit_gate(): void
    {
        Config::set('assistant_activation_limits.dimensions', array_values(array_merge(
            (array) config('assistant_activation_limits.dimensions', []),
            [[
                'key' => 'test_limit',
                'label' => 'Teste limite',
                'unit' => 'records',
                'source' => 'contract',
                'enforcement' => 'manual',
                'description' => 'Limite de teste para validar overrides.',
            ]]
        )));
        Config::set('assistant_activation_limits.plan_families.free.limits', array_merge(
            (array) config('assistant_activation_limits.plan_families.free.limits', []),
            ['test_limit' => 1]
        ));

        $plan = $this->makePlan('Free Plan');
        $company = $this->makeExpiredCompany($plan);

        TenantFeatureOverride::create([
            'company_id' => $company->id,
            'override_type' => 'limit',
            'override_key' => 'test_limit',
            'limit_value' => null,
            'notes' => 'Limite temporariamente ilimitado',
        ]);

        $resolution = app(PlanLimitResolver::class)->resolve('test_limit', $company);

        $this->assertSame('within_limit', $resolution['state']);
        $this->assertTrue($resolution['unlimited']);
        $this->assertSame(-1, $resolution['contracted_limit']);
        $this->assertSame(['tenant_override'], $resolution['reasons']);

        $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->post('/__tests/override-limit')
            ->assertOk()
            ->assertSeeText('limit ok');

        $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->post('/__tests/override-limit-module')
            ->assertOk()
            ->assertSeeText('limit module ok');
    }

    private function registerTestRoutes(): void
    {
        if (! Route::has('tests.override.feature')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck', 'feature:billing.product.manage'])
                ->get('/__tests/override-feature', fn () => response('feature ok'))
                ->name('tests.override.feature');
        }

        if (! Route::has('tests.override.feature.module')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:ProductService', 'feature:billing.product.manage'])
                ->get('/__tests/override-feature-module', fn () => response('feature module ok'))
                ->name('tests.override.feature.module');
        }

        if (! Route::has('tests.override.limit.store')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck', 'plan.limit:test_limit'])
                ->post('/__tests/override-limit', fn () => response('limit ok'))
                ->name('tests.override.limit.store');
        }

        if (! Route::has('tests.override.limit.module.store')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Account', 'plan.limit:test_limit'])
                ->post('/__tests/override-limit-module', fn () => response('limit module ok'))
                ->name('tests.override.limit.module.store');
        }
    }

    private function makeSuperAdmin(): User
    {
        return User::forceCreate([
            'name' => 'Super Admin Override',
            'email' => 'superadmin-override@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'superadmin',
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function makeCompany(string $name): User
    {
        $plan = $this->makePlan('Free Plan');

        $company = User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->subDay(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);

        $company->ensureCompanyAccessRole();

        return $company;
    }

    private function makePlan(string $name): Plan
    {
        return Plan::create([
            'name' => $name,
            'status' => true,
            'free_plan' => true,
            'modules' => ['Account', 'ProductService'],
            'package_price_yearly' => 0,
            'package_price_monthly' => 0,
            'storage_limit' => 1024,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 5,
        ]);
    }

    private function makeExpiredCompany(Plan $plan): User
    {
        $company = User::forceCreate([
            'name' => 'Empresa Override',
            'email' => 'override-company@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->subDay(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);

        $company->ensureCompanyAccessRole();

        return $company;
    }
}
