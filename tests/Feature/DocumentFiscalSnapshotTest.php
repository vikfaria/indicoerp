<?php

namespace Tests\Feature;

use App\Events\AcceptSalesProposal;
use App\Events\ApprovePurchaseReturn;
use App\Events\ApproveSalesReturn;
use App\Events\ConvertSalesProposal;
use App\Events\CreatePurchaseInvoice;
use App\Events\CreatePurchaseReturn;
use App\Events\CreateSalesInvoice;
use App\Events\CreateSalesProposal;
use App\Events\CreateSalesReturn;
use App\Events\SentSalesProposal;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesProposal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DocumentFiscalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Listeners\CreateCreditNoteFromReturn;
use Workdo\Account\Listeners\CreateDebitNoteFromReturn;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;

class DocumentFiscalSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            AcceptSalesProposal::class,
            ApprovePurchaseReturn::class,
            ApproveSalesReturn::class,
            ConvertSalesProposal::class,
            CreatePurchaseInvoice::class,
            CreatePurchaseReturn::class,
            CreateSalesInvoice::class,
            CreateSalesProposal::class,
            CreateSalesReturn::class,
            SentSalesProposal::class,
        ]);
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_sales_invoice_store_captures_issuer_and_customer_snapshots(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Snapshot');
        $this->makeCustomerDetails($company, $customer, [
            'company_name' => 'Cliente Snapshot Lda',
            'tax_number' => '400200300',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Main Warehouse');
        $product = $this->makeProduct($company, 'Produto Fiscal', 'FT-001');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Snapshot',
            'company_address' => 'Av. Julius Nyerere, 100',
            'company_city' => 'Maputo',
            'company_state' => 'Maputo',
            'company_zipcode' => '1100',
            'company_country' => 'Mozambique',
            'company_telephone' => '+258840000000',
            'company_email' => 'fiscal@empresa.test',
            'registration_number' => 'REG-001',
            'tax_type' => 'NUIT',
            'company_tax_number' => '400123456',
        ]);

        $this->grantPermissions($company, ['create-sales-invoices']);

        $response = $this->actingAs($company->fresh())->post(route('sales-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'product',
            'payment_terms' => '15 days',
            'notes' => 'Snapshot test',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 150,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $invoice = SalesInvoice::query()->latest('id')->firstOrFail();

        $this->assertSame('Empresa Snapshot', $invoice->issuer_snapshot['company_name']);
        $this->assertSame('400123456', $invoice->issuer_snapshot['tax_number']);
        $this->assertSame('NUIT', $invoice->issuer_snapshot['tax_label']);
        $this->assertSame('Cliente Snapshot', $invoice->counterparty_snapshot['name']);
        $this->assertSame('Cliente Snapshot Lda', $invoice->counterparty_snapshot['company_name']);
        $this->assertSame('400200300', $invoice->counterparty_snapshot['tax_number']);
        $this->assertSame('Matola', $invoice->counterparty_snapshot['billing_address']['city']);
    }

    public function test_purchase_invoice_store_captures_issuer_and_vendor_snapshots(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Snapshot');
        $this->makeVendorDetails($company, $vendor, [
            'company_name' => 'Fornecedor Snapshot SA',
            'tax_number' => '400300400',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Import Warehouse');
        $product = $this->makeProduct($company, 'Produto Compra', 'FC-001');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Compras',
            'company_country' => 'Mozambique',
            'company_tax_number' => '400987654',
            'tax_type' => 'NUIT',
        ]);

        $this->grantPermissions($company, ['create-purchase-invoices']);

        $response = $this->actingAs($company->fresh())->post(route('purchase-invoices.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => '30 days',
            'notes' => 'Purchase snapshot',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 200,
                    'discount_percentage' => 5,
                    'tax_percentage' => 16,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::query()->latest('id')->firstOrFail();

        $this->assertSame('Empresa Compras', $invoice->issuer_snapshot['company_name']);
        $this->assertSame('400987654', $invoice->issuer_snapshot['tax_number']);
        $this->assertSame('Fornecedor Snapshot', $invoice->counterparty_snapshot['name']);
        $this->assertSame('Fornecedor Snapshot SA', $invoice->counterparty_snapshot['company_name']);
        $this->assertSame('400300400', $invoice->counterparty_snapshot['tax_number']);
        $this->assertSame('Maputo', $invoice->counterparty_snapshot['shipping_address']['city']);
    }

    public function test_sales_proposal_send_and_convert_persist_snapshots(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Proposta');
        $this->makeCustomerDetails($company, $customer, [
            'company_name' => 'Cliente Proposta Lda',
            'tax_number' => '400555666',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Proposal Warehouse');
        $product = $this->makeProduct($company, 'Produto Proposta', 'PRF-001');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Propostas',
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
            'company_tax_number' => '400111222',
        ]);

        $this->grantPermissions($company, [
            'create-sales-proposals',
            'sent-sales-proposals',
            'accept-sales-proposals',
            'convert-sales-proposals',
            'manage-any-sales-proposals',
        ]);

        $storeResponse = $this->actingAs($company->fresh())->post(route('sales-proposals.store'), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => '10 days',
            'notes' => 'Proposal snapshot',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();

        $proposal = SalesProposal::query()->latest('id')->firstOrFail();
        $this->assertSame('Empresa Propostas', $proposal->issuer_snapshot['company_name']);
        $this->assertSame('Cliente Proposta Lda', $proposal->counterparty_snapshot['company_name']);

        $this->actingAs($company->fresh())
            ->post(route('sales-proposals.sent', $proposal))
            ->assertSessionHasNoErrors();

        $proposal->refresh();

        $this->actingAs($company->fresh())
            ->post(route('sales-proposals.accept', $proposal))
            ->assertSessionHasNoErrors();

        $proposal->refresh();

        $this->actingAs($company->fresh())
            ->post(route('sales-proposals.convert-to-invoice', $proposal))
            ->assertSessionHasNoErrors();

        $proposal->refresh();
        $invoice = SalesInvoice::query()->findOrFail($proposal->invoice_id);

        $this->assertSame('400111222', $proposal->issuer_snapshot['tax_number']);
        $this->assertSame('400555666', $proposal->counterparty_snapshot['tax_number']);
        $this->assertSame('Empresa Propostas', $invoice->issuer_snapshot['company_name']);
        $this->assertSame('Cliente Proposta', $invoice->counterparty_snapshot['name']);
        $this->assertSame('400555666', $invoice->counterparty_snapshot['tax_number']);
    }

    public function test_sales_return_reuses_original_invoice_snapshots(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Return');
        $customerDetails = $this->makeCustomerDetails($company, $customer, [
            'company_name' => 'Cliente Return Original',
            'tax_number' => '400700800',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Return Warehouse');
        $product = $this->makeProduct($company, 'Produto Return', 'NC-001');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Returns',
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
            'company_tax_number' => '400444555',
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-900',
            'invoice_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = SalesInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 25,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        app(DocumentFiscalSnapshotService::class)->syncSalesInvoice($invoice);
        $invoice->refresh();

        $customerDetails->update([
            'company_name' => 'Cliente Return Alterado',
            'tax_number' => '499999999',
        ]);

        $this->grantPermissions($company, [
            'create-sales-return-invoices',
            'approve-sales-returns-invoices',
            'manage-any-sales-return-invoices',
        ]);

        $storeResponse = $this->actingAs($company->fresh())->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $invoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $product->id,
                    'original_invoice_item_id' => $item->id,
                    'return_quantity' => 1,
                    'unit_price' => 25,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();

        $salesReturn = SalesInvoiceReturn::query()->latest('id')->firstOrFail();
        $this->assertSame($invoice->issuer_snapshot, $salesReturn->issuer_snapshot);
        $this->assertSame($invoice->counterparty_snapshot, $salesReturn->counterparty_snapshot);
        $this->assertSame('Cliente Return Original', $salesReturn->counterparty_snapshot['company_name']);

        $this->actingAs($company->fresh())
            ->post(route('sales-returns.approve', $salesReturn))
            ->assertSessionHasNoErrors();

        $salesReturn->refresh();
        $this->assertSame($invoice->issuer_snapshot, $salesReturn->issuer_snapshot);
        $this->assertSame($invoice->counterparty_snapshot, $salesReturn->counterparty_snapshot);
    }

    public function test_purchase_return_reuses_original_invoice_snapshots(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Return');
        $vendorDetails = $this->makeVendorDetails($company, $vendor, [
            'company_name' => 'Fornecedor Return Original',
            'tax_number' => '400800900',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Purchase Return Warehouse');
        $product = $this->makeProduct($company, 'Produto Compra Return', 'ND-001');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Purchase Returns',
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
            'company_tax_number' => '400666777',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-900',
            'invoice_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 120,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 120,
            'paid_amount' => 0,
            'balance_amount' => 120,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = PurchaseInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 30,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        app(DocumentFiscalSnapshotService::class)->syncPurchaseInvoice($invoice);
        $invoice->refresh();

        $vendorDetails->update([
            'company_name' => 'Fornecedor Return Alterado',
            'tax_number' => '488888888',
        ]);

        $this->grantPermissions($company, [
            'create-purchase-return-invoices',
            'approve-purchase-returns-invoices',
            'manage-any-purchase-return-invoices',
        ]);

        $storeResponse = $this->actingAs($company->fresh())->post(route('purchase-returns.store'), [
            'return_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $invoice->id,
            'reason' => 'damaged',
            'items' => [
                [
                    'product_id' => $product->id,
                    'original_invoice_item_id' => $item->id,
                    'return_quantity' => 1,
                    'unit_price' => 30,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();

        $purchaseReturn = PurchaseReturn::query()->latest('id')->firstOrFail();
        $this->assertSame($invoice->issuer_snapshot, $purchaseReturn->issuer_snapshot);
        $this->assertSame($invoice->counterparty_snapshot, $purchaseReturn->counterparty_snapshot);
        $this->assertSame('Fornecedor Return Original', $purchaseReturn->counterparty_snapshot['company_name']);

        $this->actingAs($company->fresh())
            ->post(route('purchase-returns.approve', $purchaseReturn))
            ->assertSessionHasNoErrors();

        $purchaseReturn->refresh();
        $this->assertSame($invoice->issuer_snapshot, $purchaseReturn->issuer_snapshot);
        $this->assertSame($invoice->counterparty_snapshot, $purchaseReturn->counterparty_snapshot);
    }

    public function test_credit_note_reuses_return_number_and_snapshots(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Nota Credito');
        $customerDetails = $this->makeCustomerDetails($company, $customer, [
            'company_name' => 'Cliente Nota Credito Original',
            'tax_number' => '401000100',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Credit Note Warehouse');
        $product = $this->makeProduct($company, 'Produto Nota Credito', 'NC-100');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Nota Credito',
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
            'company_tax_number' => '401111222',
            'sales_return_prefix' => 'NC',
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-901',
            'invoice_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = SalesInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        app(DocumentFiscalSnapshotService::class)->syncSalesInvoice($invoice);
        $invoice->refresh();

        $this->grantPermissions($company, ['create-sales-return-invoices']);

        $storeResponse = $this->actingAs($company->fresh())->post(route('sales-returns.store'), [
            'return_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $invoice->id,
            'reason' => 'damaged',
            'notes' => 'Snapshot preserved',
            'items' => [
                [
                    'product_id' => $product->id,
                    'original_invoice_item_id' => $item->id,
                    'return_quantity' => 1,
                    'unit_price' => 50,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();

        $salesReturn = SalesInvoiceReturn::query()->latest('id')->firstOrFail();

        $customerDetails->update([
            'company_name' => 'Cliente Nota Credito Alterado',
            'tax_number' => '499000000',
        ]);

        $this->actingAs($company->fresh());
        app(CreateCreditNoteFromReturn::class)->handle(new ApproveSalesReturn($salesReturn->fresh(['items.taxes', 'originalInvoice'])));

        $creditNote = CreditNote::query()->latest('id')->firstOrFail();

        $this->assertSame($salesReturn->return_number, $creditNote->credit_note_number);
        $this->assertSame($salesReturn->return_date->toDateString(), $creditNote->credit_note_date->toDateString());
        $this->assertSame($salesReturn->issuer_snapshot, $creditNote->issuer_snapshot);
        $this->assertSame($salesReturn->counterparty_snapshot, $creditNote->counterparty_snapshot);
        $this->assertSame('Cliente Nota Credito Original', $creditNote->counterparty_snapshot['company_name']);
        $this->assertSame('Snapshot preserved', $creditNote->notes);
    }

    public function test_debit_note_reuses_return_number_and_snapshots(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Nota Debito');
        $vendorDetails = $this->makeVendorDetails($company, $vendor, [
            'company_name' => 'Fornecedor Nota Debito Original',
            'tax_number' => '402000200',
        ]);
        $warehouse = $this->makeWarehouse($company, 'Debit Note Warehouse');
        $product = $this->makeProduct($company, 'Produto Nota Debito', 'ND-200');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Nota Debito',
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
            'company_tax_number' => '402111222',
            'purchase_return_prefix' => 'ND',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-901',
            'invoice_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 120,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 120,
            'paid_amount' => 0,
            'balance_amount' => 120,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $item = PurchaseInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 40,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        app(DocumentFiscalSnapshotService::class)->syncPurchaseInvoice($invoice);
        $invoice->refresh();

        $this->grantPermissions($company, ['create-purchase-return-invoices']);

        $storeResponse = $this->actingAs($company->fresh())->post(route('purchase-returns.store'), [
            'return_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $invoice->id,
            'reason' => 'damaged',
            'notes' => 'Snapshot preserved vendor',
            'items' => [
                [
                    'product_id' => $product->id,
                    'original_invoice_item_id' => $item->id,
                    'return_quantity' => 1,
                    'unit_price' => 40,
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();

        $purchaseReturn = PurchaseReturn::query()->latest('id')->firstOrFail();

        $vendorDetails->update([
            'company_name' => 'Fornecedor Nota Debito Alterado',
            'tax_number' => '488000000',
        ]);

        $this->actingAs($company->fresh());
        app(CreateDebitNoteFromReturn::class)->handle(new ApprovePurchaseReturn($purchaseReturn->fresh(['items.taxes', 'originalInvoice'])));

        $debitNote = DebitNote::query()->latest('id')->firstOrFail();

        $this->assertSame($purchaseReturn->return_number, $debitNote->debit_note_number);
        $this->assertSame($purchaseReturn->return_date->toDateString(), $debitNote->debit_note_date->toDateString());
        $this->assertSame($purchaseReturn->issuer_snapshot, $debitNote->issuer_snapshot);
        $this->assertSame($purchaseReturn->counterparty_snapshot, $debitNote->counterparty_snapshot);
        $this->assertSame('Fornecedor Nota Debito Original', $debitNote->counterparty_snapshot['company_name']);
        $this->assertSame('Snapshot preserved vendor', $debitNote->notes);
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

    private function makeClient(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeVendor(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'vendor',
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

    private function makeCustomerDetails(User $company, User $customer, array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'user_id' => $customer->id,
            'customer_code' => 'CUST-' . str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'company_name' => 'Cliente Demo',
            'contact_person_name' => 'Joana Cliente',
            'contact_person_email' => 'cliente@example.test',
            'contact_person_mobile' => '+258841111111',
            'tax_number' => '400000001',
            'payment_terms' => '15 days',
            'billing_address' => [
                'name' => 'Cliente Demo',
                'address_line_1' => 'Bairro Central',
                'city' => 'Matola',
                'state' => 'Maputo',
                'zip_code' => '1114',
                'country' => 'Mozambique',
            ],
            'shipping_address' => [
                'name' => 'Cliente Demo Armazem',
                'address_line_1' => 'Av. 24 de Julho',
                'city' => 'Maputo',
                'state' => 'Maputo',
                'zip_code' => '1100',
                'country' => 'Mozambique',
            ],
            'same_as_billing' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ], $overrides));
    }

    private function makeVendorDetails(User $company, User $vendor, array $overrides = []): Vendor
    {
        return Vendor::create(array_merge([
            'user_id' => $vendor->id,
            'vendor_code' => 'VEN-' . str_pad((string) (Vendor::count() + 1), 4, '0', STR_PAD_LEFT),
            'company_name' => 'Fornecedor Demo',
            'contact_person_name' => 'Carlos Vendor',
            'contact_person_email' => 'vendor@example.test',
            'contact_person_mobile' => '+258842222222',
            'tax_number' => '400000002',
            'payment_terms' => '30 days',
            'billing_address' => [
                'name' => 'Fornecedor Demo',
                'address_line_1' => 'Rua das Industrias',
                'city' => 'Beira',
                'state' => 'Sofala',
                'zip_code' => '2100',
                'country' => 'Mozambique',
            ],
            'shipping_address' => [
                'name' => 'Fornecedor Demo Warehouse',
                'address_line_1' => 'Porto Comercial',
                'city' => 'Maputo',
                'state' => 'Maputo',
                'zip_code' => '1100',
                'country' => 'Mozambique',
            ],
            'same_as_billing' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ], $overrides));
    }

    private function saveCompanySettings(User $company, array $settings): void
    {
        foreach ($settings as $key => $value) {
            setSetting($key, $value, $company->id);
        }
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'general',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
