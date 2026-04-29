<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\WarehouseStock;

class TransferTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_transfer_actions_are_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $fromWarehouseB = $this->makeWarehouse($companyB, 'From Warehouse');
        $toWarehouseB = $this->makeWarehouse($companyB, 'To Warehouse');
        $productB = $this->makeProduct($companyB);

        $this->grantPermissions($companyA, [
            'view-transfers',
            'manage-transfers',
            'manage-any-transfers',
            'delete-transfers',
        ]);

        $transfer = Transfer::create([
            'from_warehouse' => $fromWarehouseB->id,
            'to_warehouse' => $toWarehouseB->id,
            'product_id' => $productB->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->actingAs($companyA)
            ->get(route('transfers.show', $transfer))
            ->assertRedirect(route('transfers.index'));

        $this->actingAs($companyA)
            ->delete(route('transfers.destroy', $transfer))
            ->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
        ]);
    }

    public function test_transfer_store_rejects_foreign_warehouses_and_product(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $localFromWarehouse = $this->makeWarehouse($companyA, 'Local From');
        $localToWarehouse = $this->makeWarehouse($companyA, 'Local To');
        $localProduct = $this->makeProduct($companyA);
        WarehouseStock::create([
            'warehouse_id' => $localFromWarehouse->id,
            'product_id' => $localProduct->id,
            'quantity' => 10,
        ]);

        $foreignFromWarehouse = $this->makeWarehouse($companyB, 'Foreign From');
        $foreignToWarehouse = $this->makeWarehouse($companyB, 'Foreign To');
        $foreignProduct = $this->makeProduct($companyB);
        WarehouseStock::create([
            'warehouse_id' => $foreignFromWarehouse->id,
            'product_id' => $foreignProduct->id,
            'quantity' => 10,
        ]);

        $this->grantPermissions($companyA, ['create-transfers']);

        $response = $this->actingAs($companyA)->post(route('transfers.store'), [
            'from_warehouse' => $foreignFromWarehouse->id,
            'to_warehouse' => $foreignToWarehouse->id,
            'product_id' => $foreignProduct->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors([
            'from_warehouse',
            'to_warehouse',
            'product_id',
        ]);

        $this->actingAs($companyA)->post(route('transfers.store'), [
            'from_warehouse' => $localFromWarehouse->id,
            'to_warehouse' => $localToWarehouse->id,
            'product_id' => $localProduct->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeWarehouse(User $company, string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Transfer Product',
            'sku' => 'TR-001',
            'sale_price' => 100,
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
