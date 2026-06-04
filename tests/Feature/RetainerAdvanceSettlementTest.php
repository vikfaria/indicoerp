<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Retainer\Models\Retainer;
use Workdo\Retainer\Models\RetainerPayment;

class RetainerAdvanceSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_retainer_advance_payment_and_invoice_conversion_post_expected_journals_and_regularize_balance(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($company);

        $customer = $this->makeClient($company, 'Cliente TES007');
        $warehouse = $this->makeWarehouse($company, 'Armazem TES007');
        $accounts = $this->seedAccountingAccounts($company);
        $bankAccount = $this->makeBankAccount($company, $accounts['bank']);
        $product = $this->makeProduct($company);
        $retainer = $this->makeRetainer($company, $customer, $warehouse, 1000);
        $invoice = $this->makeSalesInvoice($company, $customer, $warehouse, $product, 1000);

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainer-payments.store'), [
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccount->id,
                'payment_date' => now()->toDateString(),
                'reference_number' => 'ADV-001',
                'payment_amount' => 1000,
                'status' => 'cleared',
                'notes' => 'Adiantamento de cliente',
                'allocations' => [
                    [
                        'retainer_id' => $retainer->id,
                        'amount' => 1000,
                    ],
                ],
            ])
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $payment = RetainerPayment::query()->firstOrFail();
        $retainer->refresh();

        $this->assertSame('cleared', $payment->status);
        $this->assertSame('paid', $retainer->status);
        $this->assertSame(0.0, (float) $retainer->balance_amount);
        $this->assertDatabaseHas('retainer_payment_allocations', [
            'payment_id' => $payment->id,
            'retainer_id' => $retainer->id,
            'allocated_amount' => 1000,
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'reference_number' => $payment->payment_number,
            'amount' => 1000,
            'transaction_type' => 'credit',
            'transaction_status' => 'cleared',
        ]);
        $this->assertSame(1, JournalEntry::query()->where('reference_type', 'retainer_payment')->count());
        $this->assertSame(1, BankTransaction::query()->where('bank_account_id', $bankAccount->id)->count());

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainers.convert-to-invoice', $retainer), [
                'invoice_id' => $invoice->id,
            ])
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $retainer->refresh();

        $this->assertSame('converted', $retainer->status);
        $this->assertSame(4, JournalEntry::count());
        $this->assertSame(
            ['retainer_payment', 'sales_invoice', 'retainer_to_invoice', 'sales_invoice_cogs'],
            JournalEntry::query()->orderBy('id')->pluck('reference_type')->all()
        );
    }

    public function test_retainer_status_routes_support_lifecycle_transitions_and_duplicate(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($company);

        $customer = $this->makeClient($company, 'Cliente TES007 Lifecycle');
        $warehouse = $this->makeWarehouse($company, 'Armazem TES007 Lifecycle');
        $retainer = $this->makeRetainer($company, $customer, $warehouse, 500);

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainers.sent', $retainer))
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $retainer->refresh();
        $this->assertSame('sent', $retainer->status);

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainers.accept', $retainer))
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $retainer->refresh();
        $this->assertSame('accepted', $retainer->status);

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainers.reject', $retainer))
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $retainer->refresh();
        $this->assertSame('rejected', $retainer->status);

        $this->actingAs($company)
            ->from('/retainers')
            ->post(route('retainers.duplicate', $retainer))
            ->assertRedirect('/retainers')
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Retainer::count());
        $duplicate = Retainer::query()->where('id', '!=', $retainer->id)->firstOrFail();

        $this->assertNotSame($retainer->retainer_number, $duplicate->retainer_number);
        $this->assertSame('draft', $duplicate->status);
        $this->assertSame($retainer->customer_id, $duplicate->customer_id);
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

    private function makeClient(User $company, string $name): User
    {
        return User::factory()->create([
            'type' => 'client',
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeWarehouse(User $company, string $name): Warehouse
    {
        return Warehouse::query()->create([
            'name' => $name,
            'address' => 'Maputo',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '840000000',
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function seedAccountingAccounts(User $company): array
    {
        $categoryId = (int) DB::table('account_categories')->insertGetId([
            'name' => 'Categoria TES007',
            'code' => 'TES007-' . uniqid(),
            'type' => 'assets',
            'description' => 'Categoria de teste para adiantamentos',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountTypeId = (int) DB::table('account_types')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Tipo TES007',
            'code' => 'TT-' . uniqid(),
            'normal_balance' => 'debit',
            'description' => 'Tipo de conta de teste',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountIds = [];

        foreach ([
            'bank' => ['1000', 'Banco de Teste', 'debit'],
            'ar' => ['1100', 'Accounts Receivable', 'debit'],
            'deposit' => ['2350', 'Customer Deposits', 'credit'],
            'revenue' => ['4100', 'Sales Revenue', 'credit'],
            'cogs' => ['5100', 'Cost of Goods Sold', 'debit'],
            'inventory' => ['1200', 'Inventory', 'debit'],
        ] as $key => [$code, $name, $normalBalance]) {
            $accountIds[$key] = (int) DB::table('chart_of_accounts')->insertGetId([
                'account_code' => $code,
                'account_name' => $name,
                'account_type_id' => $accountTypeId,
                'parent_account_id' => null,
                'level' => 1,
                'normal_balance' => $normalBalance,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'is_system_account' => false,
                'is_movement_account' => true,
                'description' => $name,
                'creator_id' => $company->id,
                'created_by' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $accountIds;
    }

    private function makeBankAccount(User $company, int $glAccountId): BankAccount
    {
        return BankAccount::query()->create([
            'account_number' => 'BANK-TES007-' . uniqid(),
            'account_name' => 'Conta Bancaria TES007',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'bank',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'gl_account_id' => $glAccountId,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company): ProductServiceItem
    {
        return ProductServiceItem::query()->create([
            'name' => 'Produto TES007',
            'sku' => 'TES007-PROD-' . uniqid(),
            'description' => 'Produto de teste para COGS',
            'sale_price' => 1000,
            'purchase_price' => 300,
            'unit' => '1',
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeRetainer(User $company, User $customer, Warehouse $warehouse, float $amount): Retainer
    {
        return Retainer::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'retainer_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'balance_amount' => $amount,
            'status' => 'draft',
            'notes' => 'Retainer TES007',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeSalesInvoice(User $company, User $customer, Warehouse $warehouse, ProductServiceItem $product, float $amount): SalesInvoice
    {
        $invoice = SalesInvoice::query()->create([
            'invoice_number' => 'SI-TES007-' . uniqid(),
            'document_type' => 'SI',
            'document_series' => 'A1',
            'document_sequence' => random_int(1, 9999),
            'establishment_id' => $warehouse->id,
            'invoice_date' => now()->toDateString(),
            'operation_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'posted',
            'type' => 'product',
            'payment_terms' => 'immediate',
            'notes' => 'Invoice TES007',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $amount,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        return $invoice;
    }
}
