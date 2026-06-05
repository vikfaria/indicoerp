<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\StockCostLayer;
use App\Services\InventoryCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\WarehouseStock;
use App\Models\User;
use App\Models\Warehouse;

class FifoBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_backfill_only_creates_missing_fifo_quantity_and_is_idempotent(): void
    {
        $company = $this->makeCompany();
        $warehouse = $this->makeWarehouse($company, 'Recheio');
        $product = $this->makeProduct($company, 'Coca Cola', 100.00, 10.00);

        WarehouseStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        app(InventoryCostingService::class)->recordPurchase(
            $company->id,
            $product->id,
            2,
            10.00,
            now()->toDateString(),
            'manual_stock_adjustment',
            $product->id,
            (string) $warehouse->id,
            false
        );

        $this->assertSame(2.0, round(app(InventoryCostingService::class)->getAvailableFifoQuantity($company->id, $product->id, (string) $warehouse->id), 2));

        $exitCode = Artisan::call('inventory:backfill-fifo-layers', [
            '--company' => $company->id,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(5.0, round(app(InventoryCostingService::class)->getAvailableFifoQuantity($company->id, $product->id, (string) $warehouse->id), 2));

        $layers = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereHas('movement', function ($query) use ($warehouse) {
                $query->where('warehouse_code', (string) $warehouse->id);
            })
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $layers);
        $this->assertSame(2.0, round((float) $layers[0]->remaining_quantity, 2));
        $this->assertSame(3.0, round((float) $layers[1]->remaining_quantity, 2));
        $this->assertSame('manual_stock_adjustment', (string) $layers[0]->movement->reference_type);
        $this->assertSame('fifo_backfill', (string) $layers[1]->movement->reference_type);
        $this->assertSame(10.0, round((float) $layers[1]->unit_cost, 2));

        Artisan::call('inventory:backfill-fifo-layers', [
            '--company' => $company->id,
        ]);

        $this->assertCount(2, StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereHas('movement', function ($query) use ($warehouse) {
                $query->where('warehouse_code', (string) $warehouse->id);
            })
            ->get());
    }

    public function test_backfill_dry_run_does_not_create_new_layers(): void
    {
        $company = $this->makeCompany();
        $warehouse = $this->makeWarehouse($company, 'Armazém 2');
        $product = $this->makeProduct($company, 'Água Mineral', 40.00, 8.50);

        WarehouseStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);

        $this->assertSame(0.0, round(app(InventoryCostingService::class)->getAvailableFifoQuantity($company->id, $product->id, (string) $warehouse->id), 2));

        Artisan::call('inventory:backfill-fifo-layers', [
            '--company' => $company->id,
            '--dry-run' => true,
        ]);

        $this->assertSame(0.0, round(app(InventoryCostingService::class)->getAvailableFifoQuantity($company->id, $product->id, (string) $warehouse->id), 2));
        $this->assertCount(0, StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereHas('movement', function ($query) use ($warehouse) {
                $query->where('warehouse_code', (string) $warehouse->id);
            })
            ->get());
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeWarehouse(User $company, string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'address' => 'Maputo',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '+258840000000',
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company, string $name, float $salePrice, float $purchasePrice): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => $name,
            'sku' => strtoupper(str_replace(' ', '-', $name)) . '-SKU',
            'category_id' => null,
            'description' => $name,
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'unit' => null,
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}
