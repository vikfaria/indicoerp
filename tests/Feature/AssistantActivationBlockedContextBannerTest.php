<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Services\AssistantActivation\ContextualCtaResolverService;
use App\Services\AssistantActivation\FeatureStatePresenter;
use App\Services\AssistantActivation\PlanLimitResolver;
use App\Services\AssistantActivation\UpgradeSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;

class AssistantActivationBlockedContextBannerTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdInstalledMarker = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(Module::class)->moduleCacheForget();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $installedMarker = storage_path('installed');
        if (! file_exists($installedMarker)) {
            touch($installedMarker);
            $this->createdInstalledMarker = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdInstalledMarker) {
            @unlink(storage_path('installed'));
            $this->createdInstalledMarker = false;
        }

        parent::tearDown();
    }

    public function test_it_persists_feature_gate_addon_upgrade_suggestions_to_the_dashboard(): void
    {
        $plan = $this->createPlan('Professional Plan', ['Account', 'DoubleEntry']);
        $company = $this->makeCompany(97001, $plan);

        $this->enableModule('Account');
        $this->enableModule('DoubleEntry');

        $payload = app(FeatureStatePresenter::class)->presentArray('accounting.double_entry.post', $company, 'menu');
        $request = Request::create(route('dashboard'), 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('feature_gate', $payload);
        $request->setUserResolver(static fn () => $company);

        $share = app(HandleInertiaRequests::class)->share($request);

        $this->assertSame('accounting.double_entry.post', $share['flash']['feature_gate']['key']);
        $this->assertSame('addon_required', $share['flash']['feature_gate']['block']['code']);
        $this->assertSame('activate_addon', $share['flash']['feature_gate']['recommendation']['action']);
        $this->assertSame('Account', $share['flash']['feature_gate']['recommendation']['recommended_addons'][0]['reference']);
        $this->assertSame('activate_addon', $share['flash']['feature_gate']['cta']['action']);
    }

    public function test_it_persists_plan_limit_upgrade_suggestions_to_the_dashboard(): void
    {
        $freePlan = $this->createPlan('Free Plan', ['Account'], true);
        $this->createPlan('Starter Plan', ['Account']);

        $company = $this->makeCompany(97002, $freePlan);
        $this->enableModule('Account');
        $this->activateModuleForCompany($company, 'Account');
        $this->grantPermissions($company, ['manage-bank-accounts']);
        $this->createActiveBankAccount($company, '0001');
        $this->createActiveBankAccount($company, '0002');

        $resolution = app(PlanLimitResolver::class)->resolve('bank_accounts', $company);
        $suggestion = app(UpgradeSuggestionService::class)->suggestLimit('bank_accounts', $company);
        $cta = app(ContextualCtaResolverService::class)->forRecommendation(
            (array) data_get($suggestion, 'recommendation', []),
            array_merge($resolution, ['type' => 'limit'])
        );

        $payload = array_merge($resolution, [
            'blocked' => true,
            'limit_key' => $resolution['key'] ?? null,
            'message' => $this->planLimitMessage($resolution),
            'block' => data_get($suggestion, 'block'),
            'suggestion' => $suggestion,
            'recommendation' => data_get($suggestion, 'recommendation'),
            'cta' => $cta,
        ]);

        $request = Request::create(route('dashboard'), 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('plan_limit', $payload);
        $request->setUserResolver(static fn () => $company);

        $share = app(HandleInertiaRequests::class)->share($request);

        $this->assertSame('bank_accounts', $share['flash']['plan_limit']['limit_key']);
        $this->assertSame('limit_exceeded', $share['flash']['plan_limit']['block']['code']);
        $this->assertSame(2, $share['flash']['plan_limit']['current_usage']);
        $this->assertSame(1, $share['flash']['plan_limit']['contracted_limit']);
        $this->assertSame('upgrade_plan', $share['flash']['plan_limit']['suggestion']['recommendation']['action']);
        $this->assertSame('starter', $share['flash']['plan_limit']['suggestion']['recommendation']['recommended_plan']['family']);
        $this->assertSame('upgrade_plan', $share['flash']['plan_limit']['cta']['action']);
    }

    public function test_it_persists_subscription_gate_payloads_to_the_dashboard(): void
    {
        $plan = $this->createPlan('Professional Plan', ['Account']);
        $company = $this->makeCompany(97003, $plan);
        $planId = $plan->id;

        $payload = [
            'blocked' => true,
            'type' => 'subscription',
            'key' => 'subscription.expired',
            'label' => 'Subscrição expirada',
            'message' => 'A subscrição desta empresa expirou. Renove o plano para continuar.',
            'summary' => 'A subscrição desta empresa expirou. Renove o plano para continuar.',
            'state' => 'expired',
            'subscription_state' => 'expired',
            'plan_id' => $planId,
            'plan_name' => 'Professional Plan',
            'plan_family' => 'professional',
            'plan_family_label' => 'Professional',
            'plan_expire_date' => now()->subDay()->toDateString(),
            'trial_expire_date' => null,
            'reasons' => ['subscription_expired'],
            'block' => [
                'code' => 'subscription_expired',
                'label' => 'Subscrição expirada',
                'reasons' => ['subscription_expired'],
                'details' => [
                    'plan_id' => $planId,
                    'plan_name' => 'Professional Plan',
                    'plan_family' => 'professional',
                    'plan_family_label' => 'Professional',
                    'plan_expire_date' => now()->subDay()->toDateString(),
                    'trial_expire_date' => null,
                    'active_plan' => $planId,
                ],
            ],
            'recommendation' => [
                'action' => 'renew_subscription',
                'label' => 'Renovar subscrição',
                'message' => 'Renove a subscrição para restaurar o acesso.',
                'reason_label' => 'Subscrição expirada',
                'reason_details' => [
                    'plan_id' => $planId,
                    'plan_name' => 'Professional Plan',
                    'plan_family' => 'professional',
                    'plan_family_label' => 'Professional',
                    'plan_expire_date' => now()->subDay()->toDateString(),
                    'trial_expire_date' => null,
                    'active_plan' => $planId,
                ],
                'recommended_plan' => [
                    'id' => $planId,
                    'name' => 'Professional Plan',
                    'family' => 'professional',
                    'family_label' => 'Professional',
                ],
                'recommended_addons' => [],
                'recommended_permissions' => [],
                'recommended_config_keys' => [],
                'alternatives' => [],
            ],
            'cta' => [
                'action' => 'renew_subscription',
                'label' => 'Renovar subscrição',
                'href' => route('plans.index'),
                'message' => 'Renove a subscrição para restaurar o acesso.',
                'tone' => 'default',
            ],
        ];

        $request = Request::create(route('dashboard'), 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('subscription_gate', $payload);
        $request->setUserResolver(static fn () => $company);

        $share = app(HandleInertiaRequests::class)->share($request);

        $this->assertSame('subscription.expired', $share['flash']['subscription_gate']['key']);
        $this->assertSame('subscription_expired', $share['flash']['subscription_gate']['block']['code']);
        $this->assertSame('expired', $share['flash']['subscription_gate']['subscription_state']);
        $this->assertSame('renew_subscription', $share['flash']['subscription_gate']['recommendation']['action']);
        $this->assertSame('renew_subscription', $share['flash']['subscription_gate']['cta']['action']);
    }

    private function createPlan(string $name, array $modules, bool $freePlan = false): Plan
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
            'number_of_users' => $freePlan ? 10 : 100,
        ]);
    }

    private function makeCompany(int $id, Plan $plan): User
    {
        $company = User::forceCreate([
            'id' => $id,
            'name' => 'Empresa ' . $id,
            'email' => 'company' . $id . '@example.com',
            'password' => bcrypt('password'),
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
        ]);

        $company->ensureCompanyAccessRole();

        return $company->refresh();
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

            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }

    private function createActiveBankAccount(User $company, string $number): BankAccount
    {
        return BankAccount::create([
            'account_number' => $number,
            'account_name' => 'Conta ' . $number,
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Sede',
            'account_type' => '0',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function planLimitMessage(array $resolution): string
    {
        $label = trim((string) ($resolution['label'] ?? ''));
        $currentUsage = (int) ($resolution['current_usage'] ?? 0);
        $contractedLimit = (int) ($resolution['contracted_limit'] ?? 0);

        if ($label === '') {
            $label = 'este recurso';
        }

        return sprintf(
            'O limite de %s foi atingido (%d/%d). Actualize o plano para continuar.',
            $label,
            $currentUsage,
            $contractedLimit
        );
    }
}
