<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\FeatureCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantActivationFeatureCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_the_versioned_initial_feature_matrix(): void
    {
        $service = app(FeatureCatalogService::class);

        $features = $service->features();
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $service->catalogVersion());
        $this->assertCount(22, $features);
        $this->assertSame(22, $report['summary']['features_total']);
        $this->assertSame(5, $report['summary']['domains_total']);
        $this->assertSame(6, $report['summary']['modules_total']);
        $this->assertGreaterThan(0, $report['summary']['permissions_total']);
        $this->assertSame(10, $report['summary']['config_keys_total']);

        $billingInvoiceCreate = $service->find('billing.invoice.create');
        $inventoryStockManage = $service->find('inventory.stock.manage');
        $inventoryWarehouseManage = $service->find('inventory.warehouse.manage');
        $inventoryPosManage = $service->find('inventory.pos.manage');

        $this->assertNotNull($billingInvoiceCreate);
        $this->assertSame('billing', $billingInvoiceCreate['domain']);
        $this->assertContains('Account', $billingInvoiceCreate['modules']);
        $this->assertContains('sales-invoices', $billingInvoiceCreate['route_prefixes']);
        $this->assertContains('Facturação', $billingInvoiceCreate['menu_groups']);

        $this->assertNotNull($inventoryStockManage);
        $this->assertSame('inventory', $inventoryStockManage['domain']);
        $this->assertContains('ProductService', $inventoryStockManage['modules']);
        $this->assertContains('product-service/stock', $inventoryStockManage['route_prefixes']);
        $this->assertContains('Inventário', $inventoryStockManage['menu_groups']);

        $this->assertNotNull($inventoryWarehouseManage);
        $this->assertContains('warehouses', $inventoryWarehouseManage['modules']);
        $this->assertContains('warehouses', $inventoryWarehouseManage['route_prefixes']);

        $this->assertNotNull($inventoryPosManage);
        $this->assertContains('Pos', $inventoryPosManage['modules']);
        $this->assertContains('pos/create', $inventoryPosManage['route_prefixes']);
        $this->assertContains('POS', $inventoryPosManage['menu_groups']);

        $billingDomain = collect($report['domains'])->firstWhere('key', 'billing');
        $inventoryDomain = collect($report['domains'])->firstWhere('key', 'inventory');
        $hrPayrollRun = collect($report['features'])->firstWhere('key', 'hr.payroll.run');
        $firstBillingFeature = collect($report['features'])->firstWhere('key', 'billing.invoice.create');

        $this->assertNotNull($billingDomain);
        $this->assertSame(5, $billingDomain['features_total']);
        $this->assertNotNull($firstBillingFeature);
        $this->assertContains('sales-invoices', $firstBillingFeature['route_prefixes']);

        $this->assertNotNull($inventoryDomain);
        $this->assertSame(4, $inventoryDomain['features_total']);
        $this->assertContains('warehouses', $inventoryDomain['modules']);
        $this->assertContains('Pos', $inventoryDomain['modules']);
        $this->assertContains('manage-stock', array_merge(
            $inventoryStockManage['permissions_all'],
            $inventoryStockManage['permissions_any']
        ));

        $this->assertNotNull($hrPayrollRun);
        $this->assertContains('payroll_contributions', $hrPayrollRun['config_keys']);
        $this->assertContains('hrm/payrolls', $hrPayrollRun['route_prefixes']);
    }
}
