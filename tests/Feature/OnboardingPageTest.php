<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\AddOn;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OnboardingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_it_renders_the_onboarding_dashboard_for_company_users(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $response = $this->actingAs($company)->get(route('onboarding.index'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('onboarding/index')
                ->has('plan')
                ->has('session')
                ->has('overview')
                ->has('modules')
                ->has('next_steps')
                ->has('critical_blocks')
                ->has('completion_blockers')
                ->where('plan.label', 'Professional Plan')
                ->where('plan.modules_total', fn (mixed $value): bool => is_numeric($value))
                ->where('overview.session_status', 'not_started')
                ->where('overview.session_status_label', fn (mixed $value): bool => in_array($value, ['Sem sessão', 'Not started'], true))
                ->where('overview.is_new_company', true)
                ->has('next_steps.0.action')
                ->has('next_steps.0.block')
                ->where('next_steps.0.action.kind', fn (mixed $value): bool => is_string($value) && $value !== '')
                ->where('next_steps.0.block.code', fn (mixed $value): bool => is_string($value) && $value !== '')
                ->where('overview.readiness_score', 0)
                ->has('modules.0.steps.0.action')
                ->has('modules.0.steps.0.block')
                ->where('modules.0.steps.0.action.kind', fn (mixed $value): bool => is_string($value) && $value !== '')
                ->where('modules.0.steps.0.block.code', fn (mixed $value): bool => is_string($value) && $value !== '');
        });
    }

    public function test_it_marks_steps_as_permission_blocked_when_the_user_lacks_access(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $this->enableModule('Account');

        $response = $this->actingAs($company)->get(route('onboarding.index'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('onboarding/index')
                ->where('modules.0.key', 'billing')
                ->where('next_steps.0.block.code', 'permission_missing')
                ->where('next_steps.0.action.kind', 'grant_permission')
                ->where('next_steps.0.action.href', route('roles.index'))
                ->where('next_steps.0.block.details.missing_permissions.0', 'manage-account');
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
            'name' => 'Empresa Onboarding',
            'email' => 'onboarding@example.com',
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
}
