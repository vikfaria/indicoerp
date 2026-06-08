<?php

namespace Tests\Feature;

use App\Http\Middleware\FeatureGate;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;

class AssistantActivationPlanLimitGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestRoutes();
    }

    public function test_it_blocks_real_bank_account_creation_when_the_limit_is_reached(): void
    {
        $company = $this->makeCompany();
        $this->createActiveBankAccount($company);

        $response = $this->withoutMiddleware([
                PlanModuleCheck::class,
                FeatureGate::class,
            ])
            ->from(route('account.bank-accounts.index'))
            ->actingAs($company)
            ->post(route('account.bank-accounts.store'), []);

        $response->assertRedirect(route('account.bank-accounts.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('plan_limit.blocked', true);
        $response->assertSessionHas('plan_limit.limit_key', 'bank_accounts');
        $response->assertSessionHas('plan_limit.current_usage', 1);
        $response->assertSessionHas('plan_limit.contracted_limit', 1);
    }

    public function test_it_blocks_json_store_routes_when_the_limit_is_reached(): void
    {
        $company = $this->makeCompany();
        $this->createActiveBankAccount($company);

        $response = $this->actingAs($company)
            ->postJson('/__tests/plan-limit/store');

        $response->assertStatus(403);
        $response->assertJsonPath('plan_limit.blocked', true);
        $response->assertJsonPath('plan_limit.limit_key', 'bank_accounts');
        $response->assertJsonPath('plan_limit.current_usage', 1);
        $response->assertJsonPath('plan_limit.contracted_limit', 1);
        $response->assertJsonPath('plan_limit.block.code', 'limit_near');
    }

    public function test_it_allows_store_routes_when_the_limit_is_not_reached(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)
            ->postJson('/__tests/plan-limit/store');

        $response->assertOk();
        $response->assertJsonPath('ok', true);
    }

    public function test_it_skips_non_creation_routes_even_when_the_limit_is_reached(): void
    {
        $company = $this->makeCompany();
        $this->createActiveBankAccount($company);

        $response = $this->actingAs($company)
            ->postJson('/__tests/plan-limit/approve');

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('route', 'approve');
    }

    private function registerTestRoutes(): void
    {
        if (Route::getRoutes()->getByName('tests.plan-limit.store')) {
            return;
        }

        Route::middleware(['web', 'auth', 'verified', 'plan.limit:bank_accounts'])
            ->post('/__tests/plan-limit/store', static fn () => response()->json([
                'ok' => true,
                'route' => 'store',
            ]))
            ->name('tests.plan-limit.store');

        Route::middleware(['web', 'auth', 'verified', 'plan.limit:bank_accounts'])
            ->post('/__tests/plan-limit/approve', static fn () => response()->json([
                'ok' => true,
                'route' => 'approve',
            ]))
            ->name('tests.plan-limit.approve');
    }

    private function makeCompany(): User
    {
        $plan = Plan::create([
            'name' => 'Free Plan',
            'status' => true,
            'free_plan' => true,
            'modules' => ['Account'],
            'package_price_yearly' => 0,
            'package_price_monthly' => 0,
            'storage_limit' => 0,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 10,
        ]);

        return User::forceCreate([
            'name' => 'Empresa Teste',
            'email' => 'empresa-teste@example.com',
            'password' => bcrypt('password'),
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => now()->addMonth(),
        ]);
    }

    private function createActiveBankAccount(User $company): BankAccount
    {
        return BankAccount::create([
            'account_number' => '000123456',
            'account_name' => 'Conta Principal',
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
}
