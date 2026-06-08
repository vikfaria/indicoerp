<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\AssistantActivation\PlanContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantActivationPlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_the_real_plan_limit_matrix(): void
    {
        Plan::create([
            'name' => 'Free Plan',
            'status' => true,
            'free_plan' => true,
            'modules' => ['Taskly', 'Account', 'Hrm', 'DoubleEntry'],
            'package_price_yearly' => 0,
            'package_price_monthly' => 0,
            'storage_limit' => 0,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 10,
        ]);

        Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Taskly', 'Account', 'Hrm', 'DoubleEntry', 'ProductService'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 50000000,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        $service = app(PlanContractService::class);
        $report = $service->buildReport();

        $this->assertSame(10, $report['plan_limits']['summary']['dimensions_total']);
        $this->assertSame(5, $report['plan_limits']['summary']['families_total']);

        $freePlan = collect($report['plan_limits']['families'])->firstWhere('family', 'free');
        $professionalPlan = collect($report['plan_limits']['families'])->firstWhere('family', 'professional');

        $this->assertNotNull($freePlan);
        $this->assertSame(10, $freePlan['limits']['users']['value']);
        $this->assertSame(5000000, $freePlan['limits']['storage_kb']['value']);
        $this->assertSame(1, $freePlan['limits']['document_series']['value']);

        $this->assertNotNull($professionalPlan);
        $this->assertSame(100, $professionalPlan['limits']['users']['value']);
        $this->assertSame(50000000, $professionalPlan['limits']['storage_kb']['value']);
        $this->assertSame(5000, $professionalPlan['limits']['documents_per_month']['value']);
    }
}
