<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\ModuleCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AssistantActivationModuleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_the_real_module_catalog(): void
    {
        $service = app(ModuleCatalogService::class);

        $modules = $service->modules();

        $this->assertGreaterThanOrEqual(20, count($modules));

        $account = $service->find('account');
        $taskly = $service->find('taskly');
        $lead = $service->find('lead');
        $salesInvoices = $service->find('sales_invoices');
        $purchaseInvoices = $service->find('purchase_invoices');
        $pos = $service->find('pos');

        $this->assertNotNull($account);
        $this->assertSame('Account', $account['package_key']);
        $this->assertContains('sce/*', $account['route_prefixes']);

        $this->assertNotNull($taskly);
        $this->assertSame('Taskly', $taskly['package_key']);
        $this->assertContains('project/*', $taskly['route_prefixes']);
        $this->assertSame('project', $taskly['permission_module']);

        $this->assertNotNull($lead);
        $this->assertSame('Lead', $lead['package_key']);
        $this->assertSame('lead', $lead['permission_module']);

        $this->assertNotNull($salesInvoices);
        $this->assertSame('sales-invoices', $salesInvoices['permission_module']);
        $this->assertContains('sales-invoices', $salesInvoices['route_prefixes']);

        $this->assertNotNull($purchaseInvoices);
        $this->assertSame('purchase-invoices', $purchaseInvoices['permission_module']);
        $this->assertContains('purchase-invoices', $purchaseInvoices['route_prefixes']);

        $this->assertNotNull($pos);
        $this->assertSame('Pos', $pos['package_key']);
        $this->assertContains('dashboard/pos', $pos['route_prefixes']);

        $report = $service->buildReport();
        $this->assertGreaterThan(0, $report['summary']['modules_total']);
        $this->assertArrayHasKey('package', $report['summary']['by_type']);
        $this->assertArrayHasKey('core', $report['summary']['by_type']);
    }

    public function test_the_plan_contract_command_includes_the_module_catalog(): void
    {
        Artisan::call('assistant:plan-contract');

        $output = Artisan::output();

        $this->assertStringContainsString('Module catalog', $output);
        $this->assertStringContainsString('Facturas de Venda', $output);
        $this->assertStringContainsString('Projectos', $output);
        $this->assertStringContainsString('sales-invoices', $output);
        $this->assertStringContainsString('dashboard/pos', $output);
    }
}
