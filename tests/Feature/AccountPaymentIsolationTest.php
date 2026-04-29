<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;

class AccountPaymentIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_customer_payment_store_rejects_foreign_invoice_and_credit_note(): void
    {
        $company = $this->makeCompany();
        $foreignCompany = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Local');
        $foreignCustomer = $this->makeClient($foreignCompany, 'Cliente Externo');
        $bankAccount = $this->makeBankAccount($company);
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->grantPermissions($company, ['create-customer-payments']);

        $foreignInvoice = SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-901',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $foreignCustomer->id,
            'warehouse_id' => $foreignWarehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $foreignCreditNote = CreditNote::create([
            'credit_note_number' => 'NC-2026-04-901',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $foreignCustomer->id,
            'reason' => 'foreign credit note',
            'status' => 'approved',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'applied_amount' => 0,
            'balance_amount' => 20,
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_amount' => 80,
            'allocations' => [
                [
                    'invoice_id' => $foreignInvoice->id,
                    'amount' => 100,
                ],
            ],
            'credit_notes' => [
                [
                    'credit_note_id' => $foreignCreditNote->id,
                    'amount' => 20,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'allocations.0.invoice_id',
            'credit_notes.0.credit_note_id',
        ]);
    }

    public function test_customer_outstanding_endpoint_is_tenant_scoped_and_returns_snapshot_metadata(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Snapshot');
        $foreignCompany = $this->makeCompany();
        $foreignCustomer = $this->makeClient($foreignCompany, 'Cliente Foreign');
        $warehouse = $this->makeWarehouse($company, 'Main Warehouse');
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->grantPermissions($company, ['create-customer-payments']);

        SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 40,
            'balance_amount' => 60,
            'status' => 'partial',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'counterparty_snapshot' => [
                'company_name' => 'Cliente Snapshot Lda',
                'tax_label' => 'NUIT',
                'tax_number' => '400123456',
            ],
        ]);

        CreditNote::create([
            'credit_note_number' => 'NC-2026-04-001',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'reason' => 'local credit note',
            'status' => 'approved',
            'subtotal' => 30,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 30,
            'applied_amount' => 10,
            'balance_amount' => 20,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'counterparty_snapshot' => [
                'company_name' => 'Cliente Snapshot Lda',
                'tax_label' => 'NUIT',
                'tax_number' => '400123456',
            ],
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-999',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $foreignCustomer->id,
            'warehouse_id' => $foreignWarehouse->id,
            'subtotal' => 50,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 50,
            'paid_amount' => 0,
            'balance_amount' => 50,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.customer-payments.outstanding-invoices', $customer->id));

        $response->assertOk()
            ->assertJsonCount(1, 'invoices')
            ->assertJsonCount(1, 'creditNotes')
            ->assertJsonPath('invoices.0.counterparty_name', 'Cliente Snapshot Lda')
            ->assertJsonPath('invoices.0.counterparty_tax_label', 'NUIT')
            ->assertJsonPath('invoices.0.counterparty_tax_number', '400123456')
            ->assertJsonPath('creditNotes.0.counterparty_name', 'Cliente Snapshot Lda')
            ->assertJsonPath('creditNotes.0.counterparty_tax_label', 'NUIT')
            ->assertJsonPath('creditNotes.0.counterparty_tax_number', '400123456');
    }

    public function test_customer_payment_mobile_money_requires_provider_and_number(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Mobile');
        $bankAccount = $this->makeBankAccount($company);
        $warehouse = $this->makeWarehouse($company, 'Main Warehouse');

        $this->grantPermissions($company, ['create-customer-payments']);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-777',
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
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'payment_amount' => 100,
            'allocations' => [
                [
                    'invoice_id' => $invoice->id,
                    'amount' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'mobile_money_provider',
            'mobile_money_number',
        ]);
    }

    public function test_vendor_payment_store_rejects_foreign_invoice_and_debit_note(): void
    {
        $company = $this->makeCompany();
        $foreignCompany = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Local');
        $foreignVendor = $this->makeVendor($foreignCompany, 'Fornecedor Externo');
        $bankAccount = $this->makeBankAccount($company);
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->grantPermissions($company, ['create-vendor-payments']);

        $foreignInvoice = PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-901',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $foreignVendor->id,
            'warehouse_id' => $foreignWarehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $foreignDebitNote = DebitNote::create([
            'debit_note_number' => 'ND-2026-04-901',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $foreignVendor->id,
            'reason' => 'foreign debit note',
            'status' => 'approved',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'applied_amount' => 0,
            'balance_amount' => 20,
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_amount' => 80,
            'allocations' => [
                [
                    'invoice_id' => $foreignInvoice->id,
                    'amount' => 100,
                ],
            ],
            'debit_notes' => [
                [
                    'debit_note_id' => $foreignDebitNote->id,
                    'amount' => 20,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'allocations.0.invoice_id',
            'debit_notes.0.debit_note_id',
        ]);
    }

    public function test_vendor_outstanding_endpoint_is_tenant_scoped_and_returns_snapshot_metadata(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Snapshot');
        $foreignCompany = $this->makeCompany();
        $foreignVendor = $this->makeVendor($foreignCompany, 'Fornecedor Foreign');
        $warehouse = $this->makeWarehouse($company, 'Main Warehouse');
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->grantPermissions($company, ['create-vendor-payments']);

        PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 40,
            'balance_amount' => 60,
            'status' => 'partial',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'counterparty_snapshot' => [
                'company_name' => 'Fornecedor Snapshot SA',
                'tax_label' => 'NUIT',
                'tax_number' => '400654321',
            ],
        ]);

        DebitNote::create([
            'debit_note_number' => 'ND-2026-04-001',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'reason' => 'local debit note',
            'status' => 'approved',
            'subtotal' => 30,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 30,
            'applied_amount' => 10,
            'balance_amount' => 20,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'counterparty_snapshot' => [
                'company_name' => 'Fornecedor Snapshot SA',
                'tax_label' => 'NUIT',
                'tax_number' => '400654321',
            ],
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-999',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $foreignVendor->id,
            'warehouse_id' => $foreignWarehouse->id,
            'subtotal' => 50,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 50,
            'paid_amount' => 0,
            'balance_amount' => 50,
            'status' => 'posted',
            'creator_id' => $foreignCompany->id,
            'created_by' => $foreignCompany->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.vendor-payments.vendors.outstanding', $vendor->id));

        $response->assertOk()
            ->assertJsonCount(1, 'invoices')
            ->assertJsonCount(1, 'debitNotes')
            ->assertJsonPath('invoices.0.counterparty_name', 'Fornecedor Snapshot SA')
            ->assertJsonPath('invoices.0.counterparty_tax_label', 'NUIT')
            ->assertJsonPath('invoices.0.counterparty_tax_number', '400654321')
            ->assertJsonPath('debitNotes.0.counterparty_name', 'Fornecedor Snapshot SA')
            ->assertJsonPath('debitNotes.0.counterparty_tax_label', 'NUIT')
            ->assertJsonPath('debitNotes.0.counterparty_tax_number', '400654321');
    }

    public function test_vendor_payment_mobile_money_requires_provider_and_number(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Mobile');
        $bankAccount = $this->makeBankAccount($company);
        $warehouse = $this->makeWarehouse($company, 'Main Warehouse');

        $this->grantPermissions($company, ['create-vendor-payments']);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FC-2026-04-777',
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

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'payment_amount' => 100,
            'allocations' => [
                [
                    'invoice_id' => $invoice->id,
                    'amount' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'mobile_money_provider',
            'mobile_money_number',
        ]);
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

    private function makeBankAccount(User $company): BankAccount
    {
        return BankAccount::create([
            'account_number' => '0001',
            'account_name' => 'Conta Principal',
            'bank_name' => 'Banco Teste',
            'account_type' => 'checking',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'account',
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
