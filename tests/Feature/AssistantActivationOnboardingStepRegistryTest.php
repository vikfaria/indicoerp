<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\OnboardingStepRegistry;
use Tests\TestCase;

class AssistantActivationOnboardingStepRegistryTest extends TestCase
{
    public function test_it_exposes_the_priority_module_onboarding_steps(): void
    {
        $service = app(OnboardingStepRegistry::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame(6, $report['summary']['modules_total']);
        $this->assertSame(28, $report['summary']['steps_total']);
        $this->assertSame(27, $report['summary']['required_steps_total']);

        $billing = $service->findModule('billing');
        $accounting = $service->findModule('accounting');
        $hr = $service->findModule('hr');
        $treasury = $service->findModule('treasury');
        $inventory = $service->findModule('inventory');
        $pos = $service->findModule('pos');

        $this->assertNotNull($billing);
        $this->assertSame(6, $billing['step_count']);
        $this->assertContains('Account', $billing['technical_modules']);
        $this->assertContains('ProductService', $billing['technical_modules']);

        $this->assertNotNull($accounting);
        $this->assertSame(6, $accounting['step_count']);
        $this->assertContains('DoubleEntry', $accounting['technical_modules']);

        $this->assertNotNull($hr);
        $this->assertSame(6, $hr['step_count']);
        $this->assertContains('Hrm', $hr['technical_modules']);

        $this->assertNotNull($treasury);
        $this->assertSame(6, $treasury['step_count']);
        $this->assertContains('manage-bank-transactions', $treasury['required_permissions']);

        $this->assertNotNull($inventory);
        $this->assertSame(3, $inventory['step_count']);
        $this->assertContains('ProductService', $inventory['technical_modules']);

        $this->assertNotNull($pos);
        $this->assertSame(1, $pos['step_count']);
        $this->assertContains('Pos', $pos['technical_modules']);

        $testInvoiceStep = $service->findStep('billing.issue_test_invoice');
        $payrollTestStep = $service->findStep('hr.run_payroll_test');
        $inventoryWarehouseStep = $service->findStep('inventory.create_warehouse');
        $posTestStep = $service->findStep('pos.run_test_sale');

        $this->assertNotNull($testInvoiceStep);
        $this->assertSame('issue_invoice', $testInvoiceStep['checklist_key']);
        $this->assertContains('create-sales-invoices', $testInvoiceStep['permissions']);
        $this->assertFalse($testInvoiceStep['required']);

        $this->assertNotNull($payrollTestStep);
        $this->assertSame('payroll_test', $payrollTestStep['checklist_key']);
        $this->assertContains('manage-payrolls', $payrollTestStep['permissions']);
        $this->assertContains('payroll_contributions', $payrollTestStep['config_keys']);

        $this->assertNotNull($inventoryWarehouseStep);
        $this->assertSame('create_warehouse', $inventoryWarehouseStep['checklist_key']);
        $this->assertContains('manage-warehouses', $inventoryWarehouseStep['permissions']);
        $this->assertContains('warehouses', $inventoryWarehouseStep['config_keys']);

        $this->assertNotNull($posTestStep);
        $this->assertSame('run_pos_test', $posTestStep['checklist_key']);
        $this->assertContains('create-pos', $posTestStep['permissions']);
        $this->assertContains('pos_registers', $posTestStep['config_keys']);
    }

    public function test_it_resolves_onboarding_steps_by_plan_modules(): void
    {
        $service = app(OnboardingStepRegistry::class);

        $freePlanReport = $service->buildPlanReport(['Taskly', 'Account', 'Hrm', 'DoubleEntry'], 'Free Plan');
        $professionalPlanReport = $service->buildPlanReport(['Taskly', 'Account', 'Hrm', 'DoubleEntry', 'ProductService'], 'Professional Plan');
        $inventoryPlanReport = $service->buildPlanReport(['Taskly', 'Account', 'Hrm', 'DoubleEntry', 'ProductService', 'Pos'], 'Inventory Plan');

        $this->assertSame('Free Plan', $freePlanReport['meta']['plan_label']);
        $this->assertSame(['Taskly', 'Account', 'Hrm', 'DoubleEntry'], $freePlanReport['meta']['plan_modules']);
        $this->assertSame(28, $freePlanReport['summary']['steps_total']);
        $this->assertSame(22, $freePlanReport['summary']['available_steps_total']);
        $this->assertSame(6, $freePlanReport['summary']['unavailable_steps_total']);
        $this->assertSame(22, $freePlanReport['summary']['available_required_steps_total']);

        $billing = collect($freePlanReport['modules'])->firstWhere('key', 'billing');
        $this->assertNotNull($billing);
        $this->assertSame(4, $billing['available_step_count']);
        $this->assertSame(2, $billing['unavailable_step_count']);

        $configureProfile = collect($billing['steps'])->firstWhere('key', 'billing.configure_fiscal_profile');
        $createProduct = collect($billing['steps'])->firstWhere('key', 'billing.create_product_masterdata');
        $issueInvoice = collect($billing['steps'])->firstWhere('key', 'billing.issue_test_invoice');

        $this->assertTrue($configureProfile['available']);
        $this->assertSame([], $configureProfile['missing_module_refs']);
        $this->assertFalse($createProduct['available']);
        $this->assertSame(['ProductService'], $createProduct['missing_module_refs']);
        $this->assertFalse($issueInvoice['available']);
        $this->assertSame(['ProductService'], $issueInvoice['missing_module_refs']);

        $this->assertSame(24, $professionalPlanReport['summary']['available_steps_total']);
        $this->assertSame(4, $professionalPlanReport['summary']['unavailable_steps_total']);
        $this->assertSame(23, $professionalPlanReport['summary']['available_required_steps_total']);
        $professionalBilling = collect($professionalPlanReport['modules'])->firstWhere('key', 'billing');
        $this->assertSame(6, $professionalBilling['available_step_count']);
        $this->assertSame(0, $professionalBilling['unavailable_step_count']);
        $this->assertTrue(collect($professionalBilling['steps'])->every(fn (array $step): bool => $step['available']));

        $this->assertSame('Inventory Plan', $inventoryPlanReport['meta']['plan_label']);
        $this->assertSame(28, $inventoryPlanReport['summary']['steps_total']);
        $this->assertSame(28, $inventoryPlanReport['summary']['available_steps_total']);
        $this->assertSame(0, $inventoryPlanReport['summary']['unavailable_steps_total']);
        $this->assertSame(27, $inventoryPlanReport['summary']['available_required_steps_total']);

        $inventoryModule = collect($inventoryPlanReport['modules'])->firstWhere('key', 'inventory');
        $posModule = collect($inventoryPlanReport['modules'])->firstWhere('key', 'pos');

        $this->assertSame(3, $inventoryModule['available_step_count']);
        $this->assertSame(0, $inventoryModule['unavailable_step_count']);
        $this->assertSame(1, $posModule['available_step_count']);
        $this->assertSame(0, $posModule['unavailable_step_count']);
    }
}
