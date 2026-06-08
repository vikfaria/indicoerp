<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\AssistantActivation\PlanContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AssistantActivationPlanContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_plan_families_and_builds_domain_coverage(): void
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
            'name' => 'Starter Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Taskly', 'Account', 'Hrm', 'DoubleEntry'],
            'package_price_yearly' => 240,
            'package_price_monthly' => 25,
            'storage_limit' => 10240,
            'trial' => true,
            'trial_days' => 14,
            'number_of_users' => 50,
        ]);

        Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Taskly', 'Account', 'Hrm', 'DoubleEntry', 'ProductService'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        $service = app(PlanContractService::class);

        $this->assertSame('starter', $service->normalizePlanFamily('Starter Plan'));
        $this->assertSame('professional', $service->normalizePlanFamily('Professional Plan'));
        $this->assertSame('custom', $service->normalizePlanFamily('My Custom Commercial Plan'));

        $report = $service->buildReport();

        $this->assertSame(3, $report['summary']['plans_total']);
        $this->assertArrayHasKey('billing', $report['domains']);
        $this->assertArrayHasKey('accounting', $report['domains']);
        $this->assertArrayHasKey('permission_matrix', $report);
        $this->assertArrayHasKey('plan_limits', $report);
        $this->assertArrayHasKey('module_feature_bridge', $report);
        $this->assertArrayHasKey('onboarding_steps', $report);
        $this->assertArrayHasKey('onboarding_progress', $report);
        $this->assertArrayHasKey('onboarding_readiness', $report);
        $this->assertArrayHasKey('onboarding_completion', $report);
        $this->assertArrayHasKey('onboarding_step_skips', $report);
        $this->assertArrayHasKey('readiness_formula', $report);
        $this->assertArrayHasKey('feature_matrix', $report);
        $this->assertArrayHasKey('feature_state_payload', $report);
        $this->assertSame(5, $report['permission_matrix']['summary']['areas_total']);
        $this->assertSame(10, $report['permission_matrix']['summary']['role_templates_total']);
        $this->assertSame(10, $report['plan_limits']['summary']['dimensions_total']);
        $this->assertSame(5, $report['plan_limits']['summary']['families_total']);
        $this->assertSame(29, $report['module_feature_bridge']['summary']['links_total']);
        $this->assertSame(6, $report['module_feature_bridge']['summary']['modules_with_feature_links_total']);
        $this->assertSame(22, $report['module_feature_bridge']['summary']['features_with_module_links_total']);
        $this->assertSame(6, $report['onboarding_steps']['summary']['modules_total']);
        $this->assertSame(28, $report['onboarding_steps']['summary']['steps_total']);
        $this->assertSame(6, $report['onboarding_progress']['summary']['step_states_total']);
        $this->assertSame(6, $report['onboarding_progress']['summary']['checklist_states_total']);
        $this->assertSame(4, $report['onboarding_readiness']['summary']['state_codes_total']);
        $this->assertSame(2, $report['onboarding_readiness']['summary']['critical_block_types_total']);
        $this->assertSame(25, $report['onboarding_readiness']['summary']['critical_config_checks_total']);
        $this->assertSame(2, $report['onboarding_completion']['summary']['decision_states_total']);
        $this->assertSame(5, $report['onboarding_completion']['summary']['blocker_codes_total']);
        $this->assertSame(5, $report['onboarding_completion']['summary']['validation_checks_total']);
        $this->assertSame(2, $report['onboarding_step_skips']['summary']['decision_states_total']);
        $this->assertSame(7, $report['onboarding_step_skips']['summary']['blocker_codes_total']);
        $this->assertSame(7, $report['onboarding_step_skips']['summary']['validation_checks_total']);
        $this->assertSame(4, $report['onboarding_step_skips']['summary']['audit_fields_total']);
        $this->assertSame(115.0, $report['readiness_formula']['summary']['module_weights_total']);
        $this->assertSame(130.0, $report['readiness_formula']['summary']['critical_config_weights_total']);
        $this->assertSame(70.0, $report['readiness_formula']['summary']['module_component_weight']);
        $this->assertSame(30.0, $report['readiness_formula']['summary']['critical_config_component_weight']);
        $this->assertSame(22, $report['feature_matrix']['summary']['features_total']);
        $this->assertSame(10, $report['feature_matrix']['summary']['config_keys_total']);
        $this->assertSame(5, $report['feature_matrix']['summary']['domains_total']);
        $this->assertSame(23, $report['summary']['feature_state_fields_total']);
        $this->assertSame(3, $report['summary']['feature_state_surfaces_total']);
        $this->assertSame(['menu', 'dashboard', 'onboarding'], $report['feature_state_payload']['surface_keys']);
        $this->assertContains('recommendation', $report['feature_state_payload']['top_level_fields']);
        $this->assertContains('payroll_contributions', array_column($report['onboarding_readiness']['critical_config_keys'], 'key'));
        $this->assertContains('warehouses', array_column($report['onboarding_readiness']['critical_config_keys'], 'key'));
        $this->assertContains('mozambique_fiscal_compliance', array_column($report['onboarding_readiness']['critical_config_keys'], 'key'));

        $freePlanCoverage = collect($report['domains']['billing']['plan_coverage'])->firstWhere('plan_name', 'Free Plan');
        $this->assertSame(50, $freePlanCoverage['coverage_percent']);
        $this->assertContains('ProductService', $freePlanCoverage['missing_modules']);
    }

    public function test_the_audit_command_outputs_the_contract_report(): void
    {
        Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Taskly', 'Account', 'Hrm', 'DoubleEntry', 'ProductService'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);

        Artisan::call('assistant:plan-contract');

        $output = Artisan::output();

        $this->assertStringContainsString('Assistant Activation Plan Contract', $output);
        $this->assertStringContainsString('Observed plans', $output);
        $this->assertStringContainsString('Facturação', $output);
        $this->assertStringContainsString('ProductService', $output);
        $this->assertStringContainsString('Permission matrix', $output);
        $this->assertStringContainsString('finance-administrator', $output);
        $this->assertStringContainsString('Plan limits', $output);
        $this->assertStringContainsString('users=', $output);
        $this->assertStringContainsString('Module-feature bridge', $output);
        $this->assertStringContainsString('Links total', $output);
        $this->assertStringContainsString('Onboarding step registry', $output);
        $this->assertStringContainsString('Configurar perfil fiscal', $output);
        $this->assertStringContainsString('Onboarding progress', $output);
        $this->assertStringContainsString('Onboarding readiness', $output);
        $this->assertStringContainsString('Critical block types', $output);
        $this->assertStringContainsString('Critical checks', $output);
        $this->assertStringContainsString('Onboarding completion', $output);
        $this->assertStringContainsString('Decision states', $output);
        $this->assertStringContainsString('Blocker codes', $output);
        $this->assertStringContainsString('Onboarding step skips', $output);
        $this->assertStringContainsString('Audit fields', $output);
        $this->assertStringContainsString('available steps only; checklist items provide partial progress', $output);
        $this->assertStringContainsString('Onboarding steps:', $output);
        $this->assertStringContainsString('Readiness score formula', $output);
        $this->assertStringContainsString('module component weight', strtolower($output));
        $this->assertStringContainsString('Feature matrix', $output);
        $this->assertStringContainsString('billing.invoice.create', $output);
        $this->assertStringContainsString('Feature state payload', $output);
        $this->assertStringContainsString('Surface keys: menu, dashboard, onboarding', $output);
    }
}
