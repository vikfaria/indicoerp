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
use App\Services\AssistantActivation\UpgradeSuggestionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\MozTaxAccountMapping;

class AssistantActivationUpgradeSuggestionServiceTest extends TestCase
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

    public function test_it_recommends_configuration_completion_for_a_locked_feature(): void
    {
        $plan = $this->createPlan('Professional Plan', ['Account', 'ProductService', 'DoubleEntry'], 100);
        $company = $this->makeCompany(92001, $plan);

        FiscalDocumentType::seedDefaults();
        $this->enableModule('Account');
        $this->enableModule('ProductService');
        $this->activateModuleForCompany($company, 'Account');
        $this->activateModuleForCompany($company, 'ProductService');
        $this->grantPermissions($company, ['create-sales-invoices']);
        $this->prepareFiscalSetup($company, includeAccountingPeriod: false);

        $resolver = app(UpgradeSuggestionService::class);
        $suggestion = $resolver->suggestFeature('billing.invoice.create', $company);

        $this->assertSame('feature', $suggestion['type']);
        $this->assertSame('config_missing', $suggestion['block']['code']);
        $this->assertSame('complete_configuration', $suggestion['recommendation']['action']);
        $this->assertNull($suggestion['recommendation']['recommended_plan']);
        $this->assertSame(['accounting_period'], $suggestion['recommendation']['recommended_config_keys']);
        $this->assertContains('accounting_period', $suggestion['missing_config_keys']);
    }

    public function test_it_recommends_an_addon_for_a_feature_blocked_by_module_activation(): void
    {
        $plan = $this->createPlan('Professional Plan', ['Account', 'ProductService', 'DoubleEntry'], 100);
        $company = $this->makeCompany(92002, $plan);

        FiscalDocumentType::seedDefaults();
        $this->enableModule('Account');
        $this->enableModule('DoubleEntry');
        $this->activateModuleForCompany($company, 'Account');
        $this->grantPermissions($company, ['manage-double-entry']);
        $this->prepareFiscalSetup($company, includeAccountingPeriod: true);

        $resolver = app(UpgradeSuggestionService::class);
        $suggestion = $resolver->suggestFeature('accounting.double_entry.post', $company);

        $this->assertSame('feature', $suggestion['type']);
        $this->assertSame('addon_required', $suggestion['block']['code']);
        $this->assertSame('activate_addon', $suggestion['recommendation']['action']);
        $this->assertCount(1, $suggestion['recommendation']['recommended_addons']);
        $this->assertSame('DoubleEntry', $suggestion['recommendation']['recommended_addons'][0]['reference']);
        $this->assertContains(
            'accounting.double_entry.post',
            $suggestion['recommendation']['recommended_addons'][0]['feature_keys']
        );
    }

    public function test_it_recommends_the_next_plan_when_a_limit_is_exceeded(): void
    {
        $freePlan = $this->createPlan('Free Plan', ['Account'], 2, true);
        $this->createPlan('Starter Plan', ['Account', 'ProductService'], 50);
        $this->createPlan('Professional Plan', ['Account', 'ProductService', 'DoubleEntry'], 100);

        $company = $this->makeCompany(92003, $freePlan);
        $this->makeUser('Active User 1', 'client', null, $company->id, $company->id);
        $this->makeUser('Active User 2', 'client', null, $company->id, $company->id);
        $this->makeUser('Active User 3', 'client', null, $company->id, $company->id);

        $resolver = app(UpgradeSuggestionService::class);
        $suggestion = $resolver->suggestLimit('users', $company);

        $this->assertSame('limit', $suggestion['type']);
        $this->assertSame('limit_exceeded', $suggestion['block']['code']);
        $this->assertSame('upgrade_plan', $suggestion['recommendation']['action']);
        $this->assertNotNull($suggestion['recommendation']['recommended_plan']);
        $this->assertSame('starter', $suggestion['recommendation']['recommended_plan']['family']);
        $this->assertSame('Starter Plan', $suggestion['recommendation']['recommended_plan']['name']);
        $this->assertGreaterThanOrEqual(3, $suggestion['recommendation']['recommended_plan']['users_limit']);
    }

    private function createPlan(string $name, array $modules, int $usersLimit, bool $freePlan = false): Plan
    {
        return Plan::create([
            'name' => $name,
            'status' => true,
            'free_plan' => $freePlan,
            'modules' => $modules,
            'package_price_yearly' => $freePlan ? 0 : 960,
            'package_price_monthly' => $freePlan ? 0 : 99,
            'storage_limit' => $freePlan ? 10240 : 51200,
            'trial' => ! $freePlan,
            'trial_days' => $freePlan ? 0 : 30,
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

    private function makeUser(
        string $name,
        string $type,
        ?int $activePlan,
        ?int $createdBy,
        ?int $creatorId,
        bool $disabled = false
    ): User {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => $type,
            'active_plan' => $activePlan ?? 0,
            'plan_expire_date' => ($activePlan ?? 0) > 0 ? now()->addMonth() : null,
            'trial_expire_date' => null,
            'created_by' => $createdBy,
            'creator_id' => $creatorId,
            'is_disable' => $disabled ? 1 : 0,
        ]);
    }
}
