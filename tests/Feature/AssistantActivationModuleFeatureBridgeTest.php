<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\ModuleFeatureBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantActivationModuleFeatureBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_links_modules_to_their_feature_keys(): void
    {
        $service = app(ModuleFeatureBridgeService::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame(31, $report['summary']['modules_total']);
        $this->assertSame(5, $report['summary']['modules_with_feature_links_total']);
        $this->assertSame(22, $report['summary']['features_total']);
        $this->assertSame(22, $report['summary']['features_with_module_links_total']);
        $this->assertSame(27, $report['summary']['links_total']);
        $this->assertSame(5, $report['summary']['linked_module_keys_total']);
        $this->assertSame(22, $report['summary']['linked_feature_keys_total']);

        $account = collect($report['modules'])->firstWhere('key', 'account');
        $productService = collect($report['modules'])->firstWhere('key', 'product_service');
        $doubleEntry = collect($report['modules'])->firstWhere('key', 'double_entry');
        $taskly = collect($report['modules'])->firstWhere('key', 'taskly');
        $warehouses = collect($report['modules'])->firstWhere('key', 'warehouses');
        $pos = collect($report['modules'])->firstWhere('key', 'pos');

        $this->assertNotNull($account);
        $this->assertContains('billing.invoice.create', $account['feature_keys']);
        $this->assertContains('accounting.double_entry.post', $account['feature_keys']);
        $this->assertContains('treasury.bank_accounts.manage', $account['feature_keys']);

        $this->assertNotNull($productService);
        $this->assertContains('billing.invoice.create', $productService['feature_keys']);
        $this->assertContains('billing.product.manage', $productService['feature_keys']);
        $this->assertContains('inventory.warehouse.manage', $productService['feature_keys']);
        $this->assertContains('inventory.stock.manage', $productService['feature_keys']);
        $this->assertContains('inventory.pos.manage', $productService['feature_keys']);

        $this->assertNotNull($doubleEntry);
        $this->assertContains('accounting.double_entry.post', $doubleEntry['feature_keys']);
        $this->assertContains('accounting.trial_balance.view', $doubleEntry['feature_keys']);

        $this->assertNotNull($taskly);
        $this->assertSame([], $taskly['feature_keys']);

        $this->assertNotNull($warehouses);
        $this->assertSame([], $warehouses['feature_keys']);

        $this->assertNotNull($pos);
        $this->assertContains('inventory.pos.manage', $pos['feature_keys']);

        $billingInvoiceCreate = collect($report['features'])->firstWhere('key', 'billing.invoice.create');
        $inventoryWarehouseManage = collect($report['features'])->firstWhere('key', 'inventory.warehouse.manage');
        $inventoryPosManage = collect($report['features'])->firstWhere('key', 'inventory.pos.manage');

        $this->assertNotNull($billingInvoiceCreate);
        $this->assertSame(['account', 'product_service'], $billingInvoiceCreate['module_keys']);

        $this->assertNotNull($inventoryWarehouseManage);
        $this->assertSame(['product_service'], $inventoryWarehouseManage['module_keys']);

        $this->assertNotNull($inventoryPosManage);
        $this->assertSame(['pos', 'product_service'], $inventoryPosManage['module_keys']);
    }
}
