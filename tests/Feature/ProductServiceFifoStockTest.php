<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Services\InventoryCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\ProductServiceCategory;
use Workdo\ProductService\Models\ProductServiceUnit;
use Workdo\ProductService\Models\WarehouseStock;

class ProductServiceFifoStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_manual_stock_entry_creates_fifo_layer_for_the_selected_warehouse(): void
    {
        $company = $this->makeCompany();
        $warehouse = $this->makeWarehouse($company, 'Recheio');
        $product = $this->makeProduct($company, 'Coca Cola', 100.00, 75.00);

        $this->grantPermissions($company, ['create-stock']);

        $this->actingAs($company)
            ->post(route('product-service.stock.store'), [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 5,
            ])
            ->assertSessionDoesntHaveErrors();

        $stock = WarehouseStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame(5.0, round((float) $stock->quantity, 2));

        $layer = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->with('movement')
            ->first();

        $this->assertNotNull($layer);
        $this->assertSame(5.0, round((float) $layer->remaining_quantity, 2));
        $this->assertSame(75.0, round((float) $layer->unit_cost, 2));
        $this->assertSame('purchase', (string) $layer->movement->movement_type);
        $this->assertSame('manual_stock_adjustment', (string) $layer->movement->reference_type);
        $this->assertSame((string) $warehouse->id, (string) $layer->movement->warehouse_code);
    }

    public function test_item_creation_with_initial_stock_creates_fifo_layer(): void
    {
        $company = $this->makeCompany();
        $warehouse = $this->makeWarehouse($company, 'Armazém Inicial');
        $category = ProductServiceCategory::create([
            'name' => 'Bebidas',
            'color' => '#3B82F6',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
        $unit = ProductServiceUnit::create([
            'unit_name' => 'Unidade',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $this->grantPermissions($company, ['create-product-service-item']);

        $this->actingAs($company)
            ->post(route('product-service.items.store'), [
                'name' => 'Água Mineral 1.5L',
                'sku' => 'AGUA-001',
                'tax_ids' => ['1'],
                'category_id' => $category->id,
                'description' => 'Água mineral sem gás',
                'long_description' => 'Água mineral sem gás',
                'sale_price' => 50,
                'purchase_price' => 20,
                'unit' => (string) $unit->id,
                'quantity' => 7,
                'warehouse_id' => $warehouse->id,
                'type' => 'product',
            ])
            ->assertSessionHasNoErrors();

        $item = ProductServiceItem::where('sku', 'AGUA-001')->first();
        $this->assertNotNull($item);

        $stock = WarehouseStock::where('product_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $layer = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $item->id)
            ->with('movement')
            ->first();

        $this->assertSame(7.0, round((float) $stock->quantity, 2));
        $this->assertSame(7.0, round((float) $layer->remaining_quantity, 2));
        $this->assertSame(20.0, round((float) $layer->unit_cost, 2));
        $this->assertSame('opening_stock', (string) $layer->movement->reference_type);
    }

    public function test_transfer_creates_fifo_layers_and_destination_stock_can_be_consumed_in_fifo_order(): void
    {
        $company = $this->makeCompany();
        $sourceWarehouse = $this->makeWarehouse($company, 'Recheio');
        $destinationWarehouse = $this->makeWarehouse($company, 'Loja');
        $product = $this->makeProduct($company, 'Água Mineral 1.5L', 50.00, 20.00);

        $this->grantPermissions($company, ['create-stock', 'create-transfers']);

        $this->actingAs($company)
            ->post(route('product-service.stock.store'), [
                'product_id' => $product->id,
                'warehouse_id' => $sourceWarehouse->id,
                'quantity' => 5,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($company)
            ->post(route('transfers.store'), [
                'from_warehouse' => $sourceWarehouse->id,
                'to_warehouse' => $destinationWarehouse->id,
                'product_id' => $product->id,
                'quantity' => 3,
                'date' => now()->toDateString(),
            ])
            ->assertSessionDoesntHaveErrors();

        $sourceStock = WarehouseStock::where('product_id', $product->id)
            ->where('warehouse_id', $sourceWarehouse->id)
            ->first();
        $destinationStock = WarehouseStock::where('product_id', $product->id)
            ->where('warehouse_id', $destinationWarehouse->id)
            ->first();

        $this->assertSame(2.0, round((float) $sourceStock->quantity, 2));
        $this->assertSame(3.0, round((float) $destinationStock->quantity, 2));

        $sourceLayer = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereHas('movement', function ($query) use ($sourceWarehouse) {
                $query->where('warehouse_code', (string) $sourceWarehouse->id);
            })
            ->first();

        $destinationLayer = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereHas('movement', function ($query) use ($destinationWarehouse) {
                $query->where('warehouse_code', (string) $destinationWarehouse->id);
            })
            ->first();

        $this->assertSame(2.0, round((float) $sourceLayer->remaining_quantity, 2));
        $this->assertSame(3.0, round((float) $destinationLayer->remaining_quantity, 2));
        $this->assertSame('manual_stock_adjustment', (string) $sourceLayer->movement->reference_type);
        $this->assertSame('stock_transfer_in', (string) $destinationLayer->movement->reference_type);

        $transferOutMovement = StockMovement::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('reference_type', 'stock_transfer_out')
            ->first();

        $this->assertNotNull($transferOutMovement);

        app(InventoryCostingService::class)->recordSale(
            $company->id,
            $product->id,
            2,
            now()->toDateString(),
            'sales_invoice',
            100,
            (string) $destinationWarehouse->id,
            false
        );

        $sourceLayer->refresh();
        $destinationLayer->refresh();

        $this->assertSame(2.0, round((float) $sourceLayer->remaining_quantity, 2));
        $this->assertSame(1.0, round((float) $destinationLayer->remaining_quantity, 2));
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

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            $user->givePermissionTo($permission);
        }
    }
}
