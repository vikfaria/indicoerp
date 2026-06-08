<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Services\AssistantActivation\ContextualCtaResolverService;
use App\Services\AssistantActivation\FeatureStatePresenter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\MozTaxAccountMapping;

class AssistantActivationFeatureStatePresenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-07 12:00:00'));
        Cache::flush();
        app(Module::class)->moduleCacheForget();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_builds_a_consistent_payload_for_menu_and_onboarding_surfaces(): void
    {
        $plan = $this->createPlan('Professional Plan', ['Account', 'ProductService', 'DoubleEntry'], 100);
        $activeCompany = $this->makeCompany(93001, $plan);
        $lockedCompany = $this->makeCompany(93002, $plan);

        FiscalDocumentType::seedDefaults();

        $this->enableModule('Account');
        $this->enableModule('ProductService');
        $this->activateModuleForCompany($activeCompany, 'Account');
        $this->activateModuleForCompany($activeCompany, 'ProductService');
        $this->activateModuleForCompany($lockedCompany, 'Account');
        $this->activateModuleForCompany($lockedCompany, 'ProductService');

        $this->grantPermissions($activeCompany, ['create-sales-invoices']);
        $this->grantPermissions($lockedCompany, ['create-sales-invoices']);

        $this->prepareFiscalSetup($activeCompany, true);
        $this->prepareFiscalSetup($lockedCompany, false);

        $presenter = app(FeatureStatePresenter::class);

        $active = $presenter->presentArray('billing.invoice.create', $activeCompany, 'menu');
        $blocked = $presenter->presentArray('billing.invoice.create', $lockedCompany, 'onboarding');

        $this->assertSame('billing.invoice.create', $active['key']);
        $this->assertSame('menu', $active['surface']);
        $this->assertSame('active', $active['state']);
        $this->assertTrue($active['visible']);
        $this->assertTrue($active['enabled']);
        $this->assertFalse($active['blocked']);
        $this->assertSame('no_action', $active['recommendation']['action']);
        $this->assertArrayHasKey('menu', $active['surfaces']);
        $this->assertSame('success', $active['surfaces']['menu']['badge']['tone'] ?? 'success');
        $this->assertTrue($active['surfaces']['dashboard']['visible']);
        $this->assertSame('complete', $active['surfaces']['onboarding']['state']);

        $this->assertSame('billing.invoice.create', $blocked['key']);
        $this->assertSame('onboarding', $blocked['surface']);
        $this->assertSame('locked', $blocked['state']);
        $this->assertTrue($blocked['visible']);
        $this->assertFalse($blocked['enabled']);
        $this->assertTrue($blocked['blocked']);
        $this->assertSame('config_missing', $blocked['block']['code']);
        $this->assertSame('complete_configuration', $blocked['recommendation']['action']);
        $this->assertSame('blocked', $blocked['surfaces']['onboarding']['state']);
        $this->assertSame('blocked', $blocked['surfaces']['onboarding']['step_state']);
        $this->assertSame('complete_configuration', $blocked['cta']['action']);
    }

    public function test_it_resolves_contextual_ctas_for_feature_recommendations(): void
    {
        $contextualCtaResolver = Mockery::mock(ContextualCtaResolverService::class);
        $contextualCtaResolver->shouldReceive('forRecommendation')
            ->once()
            ->andReturn([
                'action' => 'complete_configuration',
                'label' => 'Configurar perfil fiscal',
                'href' => route('sce.fiscal.index'),
                'message' => 'Abra o perfil fiscal.',
                'tone' => 'default',
                'source' => [
                    'type' => 'feature',
                    'key' => 'billing.invoice.create',
                ],
            ]);

        $this->app->instance(ContextualCtaResolverService::class, $contextualCtaResolver);

        $presenter = app(FeatureStatePresenter::class);

        $payload = $presenter->presentResolution([
            'key' => 'billing.invoice.create',
            'label' => 'Criar factura',
            'domain' => 'billing',
            'state' => 'locked',
            'reasons' => ['config_missing'],
            'missing_config_keys' => ['fiscal_profile'],
            'missing_permissions' => [],
            'addon_modules' => [],
            'unavailable_modules' => [],
            'subscription_state' => 'active',
        ], 'menu')->toArray();

        $this->assertSame('complete_configuration', $payload['cta']['action']);
        $this->assertSame('Configurar perfil fiscal', $payload['cta']['label']);
        $this->assertSame(route('sce.fiscal.index'), $payload['cta']['href']);
        $this->assertNotEmpty($payload['cta']['message']);
    }

    private function createPlan(string $name, array $modules, int $usersLimit): Plan
    {
        return Plan::create([
            'name' => $name,
            'status' => true,
            'free_plan' => false,
            'modules' => $modules,
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => $usersLimit,
        ]);
    }

    private function makeCompany(int $id, Plan $plan): User
    {
        return User::forceCreate([
            'id' => $id,
            'name' => 'Empresa ' . $id,
            'email' => 'company' . $id . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
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

            if (! $user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }

    private function enableModule(string $module): void
    {
        AddOn::updateOrCreate(
            ['module' => $module],
            [
                'name' => $module,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => true,
                'for_admin' => false,
                'package_name' => $module,
                'priority' => 10,
            ]
        );

        app(Module::class)->moduleCacheForget($module);
        Cache::forget('user:activated_modules:guest:admin');
    }

    private function activateModuleForCompany(User $company, string $module): void
    {
        UserActiveModule::updateOrCreate(
            [
                'user_id' => $company->id,
                'module' => $module,
            ],
            []
        );

        Cache::forget('user:activated_modules:user:' . $company->id);
    }

    private function prepareFiscalSetup(User $company, bool $includeAccountingPeriod): void
    {
        CompanyFiscalProfile::updateOrCreate(
            ['company_id' => $company->id],
            [
                'nuit' => '400123456',
                'legal_name' => 'Empresa ' . $company->id . ', Lda',
                'fiscal_regime' => 'normal',
                'accounting_framework' => 'pgc_nirf',
                'fiscal_year_start_month' => 1,
                'is_active' => true,
                'created_by' => $company->id,
            ]
        );

        if ($includeAccountingPeriod) {
            AccountingPeriod::generateForYear($company->id, '2026');
        }

        $this->ensureDocumentSeries($company);
        $this->ensureTaxProfile($company);
    }

    private function ensureDocumentSeries(User $company): void
    {
        $salesType = FiscalDocumentType::query()
            ->where('code', 'FT')
            ->firstOrFail();

        FiscalDocumentSeries::updateOrCreate(
            [
                'company_id' => $company->id,
                'fiscal_document_type_id' => $salesType->id,
                'series_code' => 'A',
            ],
            [
                'assigned_user_id' => $company->id,
                'terminal_code' => 'T1',
                'fiscal_regime_code' => 'normal',
                'fiscal_year' => '2026',
                'last_sequence' => 0,
                'is_active' => true,
                'valid_from' => now()->startOfMonth()->toDateString(),
                'valid_to' => now()->endOfYear()->toDateString(),
                'created_by' => $company->id,
            ]
        );
    }

    private function ensureTaxProfile(User $company): void
    {
        $vatOutput = $this->makeChartAccount($company, '2431', 'IVA liquidado', 'credit');
        $vatInput = $this->makeChartAccount($company, '2432', 'IVA dedutível', 'debit');

        MozTaxAccountMapping::updateOrCreate(
            [
                'created_by' => $company->id,
                'is_active' => true,
                'effective_from' => now()->toDateString(),
            ],
            [
                'vat_output_account_id' => $vatOutput->id,
                'vat_input_account_id' => $vatInput->id,
                'withholding_payable_account_id' => null,
                'withholding_receivable_account_id' => null,
                'irpc_expense_account_id' => null,
                'effective_to' => null,
                'notes' => 'Tax profile for assistant activation tests',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]
        );
    }

    private function makeChartAccount(User $company, string $code, string $name, string $balance): ChartOfAccount
    {
        $category = AccountCategory::create([
            'name' => 'Categoria ' . $code,
            'code' => 'CAT-' . $code,
            'type' => $balance === 'credit' ? 'liabilities' : 'assets',
            'description' => 'Categoria de teste',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $accountType = AccountType::create([
            'category_id' => $category->id,
            'name' => 'Tipo ' . $code,
            'code' => 'TYP-' . $code,
            'normal_balance' => $balance,
            'description' => 'Tipo de teste',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type_id' => $accountType->id,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => $balance,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}
