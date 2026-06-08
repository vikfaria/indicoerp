<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Classes\Module;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardReadinessSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
        Cache::flush();
    }

    public function test_it_renders_the_readiness_summary_on_the_dashboard_for_company_users(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        foreach (['Account', 'ProductService', 'DoubleEntry', 'Hrm'] as $module) {
            $this->enableModule($module);
            $this->activateModuleForCompany($company, $module);
        }

        $response = $this->actingAs($company)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('dashboard')
                ->has('onboarding')
                ->has('onboarding.summary')
                ->has('onboarding.top_blocks')
                ->has('onboarding.module_snapshots')
                ->where('onboarding.meta.plan_label', 'Professional Plan')
                ->where('onboarding.meta.session_status', 'not_started')
                ->where('onboarding.meta.is_new_company', true)
                ->where('onboarding.next_action.href', route('settings.index') . '#company-settings')
                ->where('onboarding.summary.critical_blocks_total', fn (mixed $value): bool => is_numeric($value) && (int) $value > 0)
                ->where('onboarding.summary.overall_score', fn (mixed $value): bool => is_numeric($value))
                ->has('onboarding.top_blocks.0')
                ->has('onboarding.module_snapshots.0');
        });
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);
    }

    private function makeCompany(Plan $plan): User
    {
        return User::forceCreate([
            'name' => 'Empresa Dashboard',
            'email' => 'dashboard@example.com',
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
