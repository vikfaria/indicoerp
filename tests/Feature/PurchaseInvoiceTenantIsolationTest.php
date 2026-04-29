<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;

class PurchaseInvoiceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_purchase_invoice_actions_are_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $vendorB = $this->makeVendor($companyB);
        $warehouseB = $this->makeWarehouse($companyB);

        $this->grantPermissions($companyA, [
            'manage-purchase-invoices',
            'manage-any-purchase-invoices',
            'print-purchase-invoices',
            'post-purchase-invoices',
            'delete-purchase-invoices',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'PI-2026-04-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $vendorB->id,
            'warehouse_id' => $warehouseB->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'draft',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->actingAs($companyA)
            ->get(route('purchase-invoices.print', $invoice))
            ->assertRedirect(route('purchase-invoices.index'));

        $this->actingAs($companyA)
            ->post(route('purchase-invoices.post', $invoice))
            ->assertRedirect(route('purchase-invoices.index'));

        $this->actingAs($companyA)
            ->delete(route('purchase-invoices.destroy', $invoice))
            ->assertRedirect(route('purchase-invoices.index'));

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
        ]);
    }

    public function test_purchase_invoice_store_rejects_foreign_vendor_warehouse_and_product(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $localVendor = $this->makeVendor($companyA);
        $localWarehouse = $this->makeWarehouse($companyA);
        $localProduct = $this->makeProduct($companyA);

        $foreignVendor = $this->makeVendor($companyB);
        $foreignWarehouse = $this->makeWarehouse($companyB);
        $foreignProduct = $this->makeProduct($companyB);

        $this->grantPermissions($companyA, ['create-purchase-invoices']);

        $response = $this->actingAs($companyA)->post(route('purchase-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $foreignVendor->id,
            'warehouse_id' => $foreignWarehouse->id,
            'payment_terms' => 'Immediate',
            'items' => [
                [
                    'product_id' => $foreignProduct->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'vendor_id',
            'warehouse_id',
            'items.0.product_id',
        ]);

        $this->actingAs($companyA)->post(route('purchase-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $localVendor->id,
            'warehouse_id' => $localWarehouse->id,
            'payment_terms' => 'Immediate',
            'items' => [
                [
                    'product_id' => $localProduct->id,
                    'quantity' => 1,
                    'unit_price' => 100,
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

    private function makeVendor(User $company): User
    {
        return User::factory()->create([
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Purchase Warehouse',
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
            'name' => 'Purchase Item',
            'sku' => 'PUR-001',
            'purchase_price' => 100,
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
