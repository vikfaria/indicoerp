<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\MzVatCode;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\ProductService\Models\ProductServiceItem;

class FiscalDocumentImmutabilityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_posted_sales_invoice_cannot_be_deleted(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company);
        $this->grantPermissions($company, ['delete-sales-invoices', 'manage-own-sales-invoices']);

        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 200,
            'tax_amount' => 32,
            'discount_amount' => 0,
            'total_amount' => 232,
            'paid_amount' => 0,
            'balance_amount' => 232,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        $expectedMessage = 'Documento fiscal submetido/validado não pode ser alterado. Use nota de crédito/débito para correcções.';

        $this->actingAs($company)
            ->delete(route('sales-invoices.destroy', $invoice))
            ->assertRedirect('http://localhost')
            ->assertSessionHas('error', $expectedMessage);

        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_draft_sales_invoice_can_still_be_deleted(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company);
        $this->grantPermissions($company, ['delete-sales-invoices', 'manage-own-sales-invoices']);

        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 150,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 150,
            'paid_amount' => 0,
            'balance_amount' => 150,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)
            ->delete(route('sales-invoices.destroy', $invoice))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sales_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_posted_sales_invoice_cannot_be_edited_or_updated_directly(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company);
        $service = $this->makeService($company);
        $this->grantPermissions($company, ['edit-sales-invoices', 'manage-own-sales-invoices']);
        MzVatCode::seedDefaults();

        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = SalesInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'ISE',
            'tax_exemption_reason' => 'Isento por lei.',
        ]);

        $expectedMessage = 'Documento com estado "posted" não pode ser alterado. Use nota de crédito/débito para correcções.';

        $this->actingAs($company)
            ->get(route('sales-invoices.edit', $invoice))
            ->assertRedirect(route('sales-invoices.index'))
            ->assertSessionHas('error', $expectedMessage);

        $this->actingAs($company)
            ->put(route('sales-invoices.update', $invoice), $this->salesUpdatePayload($customer->id, $warehouse->id, $service->id))
            ->assertRedirect(route('sales-invoices.index'))
            ->assertSessionHas('error', $expectedMessage);

        $invoice->refresh();
        $item->refresh();

        $this->assertSame('posted', $invoice->status);
        $this->assertSame(100.0, (float) $invoice->total_amount);
        $this->assertSame(1, $item->quantity);
        $this->assertSame(100.0, (float) $item->unit_price);
        $this->assertSame('ISE', (string) $item->vat_code);
        $this->assertCount(1, $invoice->items);
    }

    public function test_posted_purchase_invoice_cannot_be_edited_or_updated_directly(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company);
        $warehouse = $this->makeWarehouse($company);
        $product = $this->makeProduct($company);
        $this->grantPermissions($company, ['edit-purchase-invoices', 'manage-own-purchase-invoices']);
        MzVatCode::seedDefaults();

        $invoice = PurchaseInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = PurchaseInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'ISE',
            'tax_exemption_reason' => 'Isento por lei.',
        ]);

        $expectedMessage = 'Documento com estado "posted" não pode ser alterado. Use nota de crédito/débito para correcções.';

        $this->actingAs($company)
            ->get(route('purchase-invoices.edit', $invoice))
            ->assertRedirect(route('purchase-invoices.index'))
            ->assertSessionHas('error', $expectedMessage);

        $this->actingAs($company)
            ->put(route('purchase-invoices.update', $invoice), $this->purchaseUpdatePayload($vendor->id, $warehouse->id, $product->id))
            ->assertRedirect(route('purchase-invoices.index'))
            ->assertSessionHas('error', $expectedMessage);

        $invoice->refresh();
        $item->refresh();

        $this->assertSame('posted', $invoice->status);
        $this->assertSame(100.0, (float) $invoice->total_amount);
        $this->assertSame(1, $item->quantity);
        $this->assertSame(100.0, (float) $item->unit_price);
        $this->assertSame('ISE', (string) $item->vat_code);
        $this->assertCount(1, $invoice->items);
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
            'name' => 'Fiscal Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeService(User $company): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Serviço Fiscal',
            'sku' => 'SRV-FISC-IMMUT',
            'sale_price' => 100,
            'purchase_price' => 100,
            'type' => 'service',
            'unit' => 'Unidade',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Produto Fiscal',
            'sku' => 'PRD-FISC-IMMUT',
            'sale_price' => 100,
            'purchase_price' => 100,
            'type' => 'product',
            'unit' => 'Unidade',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function salesUpdatePayload(int $customerId, int $warehouseId, int $productId): array
    {
        return [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'type' => 'service',
            'payment_terms' => '30 days',
            'notes' => 'Tentativa de alteração directa',
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 2,
                    'unit_price' => 250,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'ISE',
                    'tax_exemption_reason' => 'Isento por lei.',
                ],
            ],
        ];
    }

    private function purchaseUpdatePayload(int $vendorId, int $warehouseId, int $productId): array
    {
        return [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $vendorId,
            'warehouse_id' => $warehouseId,
            'payment_terms' => '30 days',
            'notes' => 'Tentativa de alteração directa',
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 3,
                    'unit_price' => 180,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'ISE',
                    'tax_exemption_reason' => 'Isento por lei.',
                ],
            ],
        ];
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
