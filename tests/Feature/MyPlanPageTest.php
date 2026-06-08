<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MyPlanPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(Module::class)->moduleCacheForget();
    }

    public function test_it_renders_the_my_plan_page_for_an_expired_company_plan(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $this->enableModule('Account');
        $this->enableModule('ProductService');
        $this->enableModule('Hrm');

        $this->activateModuleForCompany($company, 'Account');
        $this->activateModuleForCompany($company, 'ProductService');
        $this->activateModuleForCompany($company, 'Hrm');

        $response = $this->withSession(['company_role_checked' => true])
            ->actingAs($company)
            ->get(route('plans.my-plan'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('plans/my-plan')
                ->has('myPlan.meta')
                ->has('myPlan.overview')
                ->has('myPlan.summary')
                ->has('myPlan.modules.included')
                ->has('myPlan.modules.addons')
                ->has('myPlan.limits.summary')
                ->has('myPlan.suggestions')
                ->where('myPlan.overview.company_name', 'Empresa Plano')
                ->where('myPlan.overview.plan_name', 'Professional Plan')
                ->where('myPlan.overview.plan_status', 'expired')
                ->where('myPlan.summary.plan_modules_total', 2)
                ->where('myPlan.summary.addon_modules_total', 1)
                ->has('myPlan.modules.included', 2)
                ->has('myPlan.modules.addons', 1);
        });
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService'],
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
        $company = User::forceCreate([
            'name' => 'Empresa Plano',
            'email' => 'plano@example.com',
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
