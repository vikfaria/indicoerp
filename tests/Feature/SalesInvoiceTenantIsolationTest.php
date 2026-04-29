<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;

class SalesInvoiceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_sales_invoice_actions_are_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $customerB = $this->makeClient($companyB);
        $warehouseB = $this->makeWarehouse($companyB);

        $this->grantPermissions($companyA, [
            'manage-sales-invoices',
            'manage-any-sales-invoices',
            'print-sales-invoices',
            'post-sales-invoices',
            'delete-sales-invoices',
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'SI-2026-04-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customerB->id,
            'warehouse_id' => $warehouseB->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->actingAs($companyA)
            ->get(route('sales-invoices.print', $invoice))
            ->assertRedirect(route('sales-invoices.index'));

        $this->actingAs($companyA)
            ->post(route('sales-invoices.post', $invoice))
            ->assertRedirect(route('sales-invoices.index'));

        $this->actingAs($companyA)
            ->delete(route('sales-invoices.destroy', $invoice))
            ->assertRedirect(route('sales-invoices.index'));

        $invoice->refresh();

        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
        ]);
    }

    public function test_sales_invoice_store_rejects_foreign_customer_warehouse_and_product(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $localCustomer = $this->makeClient($companyA);
        $localWarehouse = $this->makeWarehouse($companyA);
        $localProduct = $this->makeProduct($companyA, 'product');

        $foreignCustomer = $this->makeClient($companyB);
        $foreignWarehouse = $this->makeWarehouse($companyB);
        $foreignProduct = $this->makeProduct($companyB, 'product');

        $this->grantPermissions($companyA, ['create-sales-invoices']);

        $response = $this->actingAs($companyA)->post(route('sales-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $foreignCustomer->id,
            'type' => 'product',
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
            'customer_id',
            'warehouse_id',
            'items.0.product_id',
        ]);

        $this->actingAs($companyA)->post(route('sales-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $localCustomer->id,
            'type' => 'product',
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
            'name' => 'Main Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company, string $type): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => ucfirst($type) . ' Item',
            'sku' => strtoupper($type) . '-001',
            'sale_price' => 100,
            'type' => $type,
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
