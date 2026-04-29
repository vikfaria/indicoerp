<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\Pos\Models\Pos;
use Workdo\ProductService\Models\ProductServiceItem;

class PosTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_pos_print_is_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $warehouseB = $this->makeWarehouse($companyB);

        $this->grantPermissions($companyA, ['view-pos-orders']);

        $sale = Pos::create([
            'sale_number' => '#POS00001',
            'warehouse_id' => $warehouseB->id,
            'pos_date' => now()->toDateString(),
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->actingAs($companyA)
            ->get(route('pos-orders.print', $sale))
            ->assertRedirect(route('pos.index'));
    }

    public function test_pos_store_rejects_foreign_customer_warehouse_and_product(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $foreignCustomer = $this->makeClient($companyB);
        $foreignWarehouse = $this->makeWarehouse($companyB);
        $foreignProduct = $this->makeProduct($companyB);

        $localCustomer = $this->makeClient($companyA);
        $localWarehouse = $this->makeWarehouse($companyA);
        $localProduct = $this->makeProduct($companyA);

        $this->grantPermissions($companyA, ['create-pos']);

        $response = $this->actingAs($companyA)->post(route('pos.store'), [
            'customer_id' => $foreignCustomer->id,
            'warehouse_id' => $foreignWarehouse->id,
            'pos_date' => now()->toDateString(),
            'discount' => 0,
            'items' => [
                [
                    'id' => $foreignProduct->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'customer_id',
            'warehouse_id',
            'items.0.id',
        ]);

        $this->actingAs($companyA)->post(route('pos.store'), [
            'customer_id' => $localCustomer->id,
            'warehouse_id' => $localWarehouse->id,
            'pos_date' => now()->toDateString(),
            'discount' => 0,
            'items' => [
                [
                    'id' => $localProduct->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
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

    private function makeClient(User $company): User
    {
        return User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'POS Warehouse',
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
            'name' => 'POS Product',
            'sku' => 'POS-001',
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
