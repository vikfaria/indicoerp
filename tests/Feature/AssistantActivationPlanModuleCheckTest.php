<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssistantActivationPlanModuleCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestRoutes();
        Cache::flush();
        app(Module::class)->moduleCacheForget();
    }

    public function test_it_allows_a_known_module_when_the_company_has_it_active(): void
    {
        $company = $this->makeCompany(901001);
        $this->enableModule('Account');
        $this->activateModuleForCompany($company, 'Account');

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/account-active');

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('route', 'account-active');
    }

    public function test_it_allows_a_known_module_when_it_is_in_the_company_plan_even_without_user_active_module_rows(): void
    {
        $company = $this->makeCompany(901006);
        $this->enableModule('Account');

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/account-active');

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('route', 'account-active');
    }

    public function test_it_keeps_account_available_as_a_core_company_module_even_when_it_is_missing_from_the_plan(): void
    {
        $company = $this->makeCompany(901002, ['Hrm']);
        $this->enableModule('Account');

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/account-active');

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('route', 'account-active');
    }

    public function test_it_allows_bank_accounts_index_even_when_account_is_missing_from_the_plan_and_the_session_was_already_checked(): void
    {
        $company = $this->makeCompany(901007, ['Hrm']);
        $this->enableModule('Account');

        foreach ([
            'manage-bank-accounts',
            'manage-any-bank-accounts',
            'manage-own-bank-accounts',
        ] as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );
        }

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->get(route('account.bank-accounts.index'), $this->inertiaHeaders());

        $response->assertOk();
        $response->assertHeader('X-Inertia', 'true');
        $response->assertJsonPath('component', 'Account/BankAccounts/Index');
    }

    public function test_it_invalidates_stale_active_module_cache_before_bank_account_feature_gate_runs(): void
    {
        $company = $this->makeCompany(901008, ['Hrm']);
        $this->enableModule('Account');

        foreach ([
            'manage-bank-accounts',
            'manage-any-bank-accounts',
            'manage-own-bank-accounts',
        ] as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );
        }

        $this->assertNotContains('Account', ActivatedModule($company->id));

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->get(route('account.bank-accounts.index'), $this->inertiaHeaders());

        $response->assertOk();
        $response->assertHeader('X-Inertia', 'true');
        $response->assertJsonPath('component', 'Account/BankAccounts/Index');
    }

    public function test_it_blocks_a_known_non_core_module_and_uses_the_resolver_payload_when_available(): void
    {
        $company = $this->makeCompany(901003, ['Account']);
        $this->enableModule('Hrm');

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/hrm-locked');

        $response->assertStatus(403);
        $response->assertJsonPath('module_gate.allowed', false);
        $response->assertJsonPath('module_gate.state', 'addon');
        $response->assertJsonPath('module_gate.resolved_via', 'resolver');
        $response->assertJsonPath('module_gate.suggestion.type', 'feature');
        $response->assertJsonPath('module_gate.suggestion.block.code', 'addon_required');
        $response->assertJsonPath('module_gate.suggestion.recommendation.action', 'activate_addon');
        $response->assertJsonPath('module_gate.suggestion.recommendation.message', 'O papel do utilizador pode estar correcto, mas a empresa ainda não activou o add-on necessário. Reveja os add-ons activos da empresa.');
    }

    public function test_it_blocks_expired_company_subscriptions_with_a_subscription_gate_payload(): void
    {
        $company = $this->makeCompany(901004);
        $company->forceFill([
            'plan_expire_date' => now()->subDay(),
        ])->save();

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->from(route('dashboard'))
            ->post('/__tests/plan-module/account-locked');

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('subscription_gate.key', 'subscription.expired');
        $response->assertSessionHas('subscription_gate.block.code', 'subscription_expired');
        $response->assertSessionHas('subscription_gate.subscription_state', 'expired');
        $response->assertSessionHas('subscription_gate.recommendation.action', 'renew_subscription');
        $response->assertSessionHas('subscription_gate.cta.action', 'renew_subscription');
    }

    public function test_it_blocks_expired_company_subscriptions_with_a_json_subscription_gate_payload(): void
    {
        $company = $this->makeCompany(901005);
        $company->forceFill([
            'plan_expire_date' => now()->subDay(),
        ])->save();

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/account-locked');

        $response->assertStatus(403);
        $response->assertJsonPath('subscription_gate.key', 'subscription.expired');
        $response->assertJsonPath('subscription_gate.block.code', 'subscription_expired');
        $response->assertJsonPath('subscription_gate.subscription_state', 'expired');
        $response->assertJsonPath('subscription_gate.recommendation.action', 'renew_subscription');
        $response->assertJsonPath('subscription_gate.cta.action', 'renew_subscription');
    }

    public function test_it_keeps_the_legacy_fallback_for_unknown_modules(): void
    {
        $company = $this->makeCompany(901003);

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->postJson('/__tests/plan-module/stripe');

        $response->assertStatus(403);
        $response->assertJsonPath('module_gate.allowed', false);
        $response->assertJsonPath('module_gate.state', 'hidden');
        $response->assertJsonPath('module_gate.resolved_via', 'legacy');
        $this->assertNull($response->json('module_gate.suggestion'));
    }

    private function registerTestRoutes(): void
    {
        if (! Route::getRoutes()->getByName('tests.plan-module.account-active')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Account'])
                ->post('/__tests/plan-module/account-active', static fn () => response()->json([
                    'ok' => true,
                    'route' => 'account-active',
                ]))
                ->name('tests.plan-module.account-active');
        }

        if (! Route::getRoutes()->getByName('tests.plan-module.account-locked')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Account'])
                ->post('/__tests/plan-module/account-locked', static fn () => response()->json([
                    'ok' => true,
                    'route' => 'account-locked',
                ]))
                ->name('tests.plan-module.account-locked');
        }

        if (! Route::getRoutes()->getByName('tests.plan-module.hrm-locked')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Hrm'])
                ->post('/__tests/plan-module/hrm-locked', static fn () => response()->json([
                    'ok' => true,
                    'route' => 'hrm-locked',
                ]))
                ->name('tests.plan-module.hrm-locked');
        }

        if (! Route::getRoutes()->getByName('tests.plan-module.stripe')) {
            Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Stripe'])
                ->post('/__tests/plan-module/stripe', static fn () => response()->json([
                    'ok' => true,
                    'route' => 'stripe',
                ]))
                ->name('tests.plan-module.stripe');
        }
    }

    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
        ];
    }

    private function makeCompany(int $id, array $planModules = ['Account']): User
    {
        $plan = Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => $planModules,
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 100,
        ]);

        $company = User::forceCreate([
            'id' => $id,
            'name' => 'Empresa Teste',
            'email' => 'empresa-plan-module@example.com',
            'password' => bcrypt('password'),
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
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
}
