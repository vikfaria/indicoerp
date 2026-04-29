<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesInvoiceReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;

class SalesReturnTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_sales_return_actions_are_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $customerB = $this->makeClient($companyB);
        $warehouseB = $this->makeWarehouse($companyB, 'Foreign Warehouse');
        $productB = $this->makeProduct($companyB, 'Foreign Product', 'SR-FOR-001');
        $invoiceB = $this->makeSalesInvoice($companyB, $customerB, $warehouseB);
        $this->makeSalesInvoiceItem($invoiceB, $productB, 5, 100);

        $this->grantPermissions($companyA, [
            'view-sales-return-invoices',
            'manage-sales-return-invoices',
            'manage-any-sales-return-invoices',
            'approve-sales-returns-invoices',
            'delete-sales-return-invoices',
        ]);

        $return = SalesInvoiceReturn::create([
            'return_number' => 'SR-2026-04-001',
            'return_date' => now()->toDateString(),
            'customer_id' => $customerB->id,
            'warehouse_id' => $warehouseB->id,
            'original_invoice_id' => $invoiceB->id,
            'reason' => 'damaged',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'draft',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->actingAs($companyA)
            ->get(route('sales-returns.show', $return))
            ->assertRedirect(route('sales-returns.index'));

        $this->actingAs($companyA)
            ->get(route('sales-returns.print', $return))
            ->assertRedirect(route('sales-returns.index'));

        $this->actingAs($companyA)
            ->post(route('sales-returns.approve', $return))
            ->assertRedirect(route('sales-returns.index'));

        $this->actingAs($companyA)
            ->delete(route('sales-returns.destroy', $return))
            ->assertRedirect(route('sales-returns.index'));

        $this->assertDatabaseHas('sales_invoice_returns', [
            'id' => $return->id,
            'status' => 'draft',
        ]);
    }

    public function test_sales_return_store_rejects_foreign_documents_mismatched_items_and_excess_quantity(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $localCustomer = $this->makeClient($companyA);
        $localWarehouse = $this->makeWarehouse($companyA, 'Local Warehouse');
        $localProduct = $this->makeProduct($companyA, 'Local Product', 'SR-LOC-001');
        $localOtherProduct = $this->makeProduct($companyA, 'Other Product', 'SR-LOC-002');
        $localInvoice = $this->makeSalesInvoice($companyA, $localCustomer, $localWarehouse);
        $localInvoiceItem = $this->makeSalesInvoiceItem($localInvoice, $localProduct, 5, 100);

        $existingReturn = SalesInvoiceReturn::create([
            'return_number' => 'SR-2026-04-010',
            'return_date' => now()->subDay()->toDateString(),
            'customer_id' => $localCustomer->id,
            'warehouse_id' => $localWarehouse->id,
            'original_invoice_id' => $localInvoice->id,
            'reason' => 'damaged',
            'subtotal' => 200,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 200,
            'status' => 'approved',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        SalesInvoiceReturnItem::create([
            'return_id' => $existingReturn->id,
            'product_id' => $localProduct->id,
            'original_invoice_item_id' => $localInvoiceItem->id,
            'original_quantity' => 5,
            'return_quantity' => 2,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'total_amount' => 200,
        ]);

        $foreignCustomer = $this->makeClient($companyB);
        $foreignWarehouse = $this->makeWarehouse($companyB, 'Foreign Warehouse');
        $foreignProduct = $this->makeProduct($companyB, 'Foreign Product', 'SR-FOR-002');
        $foreignInvoice = $this->makeSalesInvoice($companyB, $foreignCustomer, $foreignWarehouse);
        $foreignInvoiceItem = $this->makeSalesInvoiceItem($foreignInvoice, $foreignProduct, 4, 50);

        $this->grantPermissions($companyA, ['create-sales-return-invoices']);

        $this->actingAs($companyA)->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $foreignCustomer->id,
            'warehouse_id' => $foreignWarehouse->id,
            'original_invoice_id' => $foreignInvoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $foreignProduct->id,
                    'original_invoice_item_id' => $foreignInvoiceItem->id,
                    'return_quantity' => 1,
                    'unit_price' => 50,
                ],
            ],
        ])->assertSessionHasErrors([
            'customer_id',
            'warehouse_id',
            'original_invoice_id',
            'items.0.product_id',
            'items.0.original_invoice_item_id',
        ]);

        $this->actingAs($companyA)->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $localCustomer->id,
            'warehouse_id' => $localWarehouse->id,
            'original_invoice_id' => $localInvoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $localOtherProduct->id,
                    'original_invoice_item_id' => $localInvoiceItem->id,
                    'return_quantity' => 4,
                    'unit_price' => 100,
                ],
            ],
        ])->assertSessionHasErrors([
            'items.0.original_invoice_item_id',
        ]);

        $this->actingAs($companyA)->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $localCustomer->id,
            'warehouse_id' => $localWarehouse->id,
            'original_invoice_id' => $localInvoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $localProduct->id,
                    'original_invoice_item_id' => $localInvoiceItem->id,
                    'return_quantity' => 4,
                    'unit_price' => 100,
                ],
            ],
        ])->assertSessionHasErrors([
            'items.0.return_quantity',
        ]);

        $this->actingAs($companyA)->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $localCustomer->id,
            'warehouse_id' => $localWarehouse->id,
            'original_invoice_id' => $localInvoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $localProduct->id,
                    'original_invoice_item_id' => $localInvoiceItem->id,
                    'return_quantity' => 3,
                    'unit_price' => 100,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_sales_return_print_is_available_with_view_permission_for_same_tenant(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company, 'Local Warehouse');
        $product = $this->makeProduct($company, 'Local Product', 'SR-PRINT-001');
        $invoice = $this->makeSalesInvoice($company, $customer, $warehouse);
        $invoiceItem = $this->makeSalesInvoiceItem($invoice, $product, 2, 100);

        $return = SalesInvoiceReturn::create([
            'return_number' => 'NC-2026-04-050',
            'return_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $invoice->id,
            'reason' => 'damaged',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'approved',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoiceReturnItem::create([
            'return_id' => $return->id,
            'product_id' => $product->id,
            'original_invoice_item_id' => $invoiceItem->id,
            'original_quantity' => 2,
            'return_quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
        ]);

        $this->grantPermissions($company, ['view-sales-return-invoices']);

        $this->actingAs($company)
            ->get(route('sales-returns.print', $return), $this->inertiaHeaders())
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'SalesReturns/Print')
            ->assertJsonPath('props.return.return_number', 'NC-2026-04-050');
    }

    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
        ];
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

    private function makeProduct(User $company, string $name, string $sku): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => $name,
            'sku' => $sku,
            'sale_price' => 100,
            'purchase_price' => 80,
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeSalesInvoice(User $company, User $customer, Warehouse $warehouse): SalesInvoice
    {
        return SalesInvoice::create([
            'invoice_number' => 'SI-' . uniqid(),
            'invoice_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'balance_amount' => 500,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeSalesInvoiceItem(SalesInvoice $invoice, ProductServiceItem $product, int $quantity, float $unitPrice): SalesInvoiceItem
    {
        return SalesInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
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
