<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;
use Workdo\Pos\Models\Pos;

class FiscalDocumentComplianceMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    #[DataProvider('fiscalDocumentMatrixProvider')]
    public function test_supported_fiscal_documents_follow_submission_validation_cancellation_and_immutability(
        string $kind,
        string $table,
        string $initialStatus,
        bool $supportsCancelledStatus
    ): void {
        if (!Schema::hasTable($table)) {
            $this->markTestSkipped(sprintf('Fiscal document table [%s] is not available in this test environment.', $table));
        }

        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Matriz Fiscal');
        $vendor = $this->makeVendor($company, 'Fornecedor Matriz Fiscal');
        $warehouse = $this->makeWarehouse($company, 'Armazem Matriz Fiscal');

        $this->grantPermissions($company, $this->fiscalPermissions());

        $document = $this->createDocumentFixture($kind, $company, $customer, $vendor, $warehouse, $initialStatus);
        [$statusRoute, $cancelRoute] = $this->fiscalRoutes($kind);

        $submittedReference = sprintf('FAT-%s-SUB-001', strtoupper(str_replace('_', '-', $kind)));
        $validatedReference = sprintf('FAT-%s-VAL-001', strtoupper(str_replace('_', '-', $kind)));
        $cancelReference = sprintf('FAT-%s-CAN-001', strtoupper(str_replace('_', '-', $kind)));
        $rectificationReference = sprintf('FAT-%s-RECT-001', strtoupper(str_replace('_', '-', $kind)));

        $this->actingAs($company)
            ->post(route($statusRoute, $document), [
                'status' => 'submitted',
                'reference' => $submittedReference,
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertSame('submitted', $document->fiscal_submission_status);
        $this->assertSame($submittedReference, $document->fiscal_submission_reference);
        $this->assertNotNull($document->fiscal_submitted_at);

        $this->actingAs($company)
            ->post(route($statusRoute, $document), [
                'status' => 'validated',
                'reference' => $validatedReference,
                'message' => 'Validated for fiscal matrix coverage',
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertSame('validated', $document->fiscal_submission_status);
        $this->assertSame($validatedReference, $document->fiscal_submission_reference);
        $this->assertNotNull($document->fiscal_validated_at);

        $this->actingAs($company)
            ->post(route($cancelRoute, $document), [
                'reason' => 'Cancelacao fiscal sem referencia de retificacao',
            ])
            ->assertSessionHasErrors('rectification_reference');

        $this->actingAs($company)
            ->post(route($cancelRoute, $document), [
                'reason' => 'Cancelacao fiscal com referencia de retificacao',
                'cancellation_reference' => $cancelReference,
                'rectification_reference' => $rectificationReference,
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertTrue((bool) $document->is_cancelled);
        $this->assertSame('rejected', $document->fiscal_submission_status);
        $this->assertSame($cancelReference, $document->cancellation_reference);
        $this->assertSame($rectificationReference, $document->rectification_reference);
        $this->assertNotNull($document->cancelled_at);
        $this->assertNotNull($document->cancelled_by);
        $this->assertNull($document->fiscal_validated_at);
        $this->assertSame($supportsCancelledStatus ? 'cancelled' : $initialStatus, $document->status);

        $this->actingAs($company)
            ->post(route($statusRoute, $document), [
                'status' => 'submitted',
                'reference' => sprintf('FAT-%s-AFTER-CANCEL', strtoupper(str_replace('_', '-', $kind))),
            ])
            ->assertSessionHasErrors('status');

        $document->refresh();
        $this->assertSame('rejected', $document->fiscal_submission_status);
        $this->assertSame($supportsCancelledStatus ? 'cancelled' : $initialStatus, $document->status);
    }

    public static function fiscalDocumentMatrixProvider(): array
    {
        return [
            'sales invoice' => ['sales_invoice', 'sales_invoices', 'posted', false],
            'purchase invoice' => ['purchase_invoice', 'purchase_invoices', 'posted', false],
            'sales return' => ['sales_return', 'sales_invoice_returns', 'approved', true],
            'purchase return' => ['purchase_return', 'purchase_returns', 'approved', true],
            'credit note' => ['credit_note', 'credit_notes', 'approved', true],
            'debit note' => ['debit_note', 'debit_notes', 'approved', false],
            'pos' => ['pos', 'pos', 'completed', true],
        ];
    }

    private function createDocumentFixture(
        string $kind,
        User $company,
        User $customer,
        User $vendor,
        Warehouse $warehouse,
        string $initialStatus
    ): Model {
        return match ($kind) {
            'sales_invoice' => $this->makeSalesInvoice($company, $customer, $warehouse, $initialStatus),
            'purchase_invoice' => $this->makePurchaseInvoice($company, $vendor, $warehouse, $initialStatus),
            'sales_return' => $this->makeSalesReturn($company, $customer, $warehouse),
            'purchase_return' => $this->makePurchaseReturn($company, $vendor, $warehouse),
            'credit_note' => $this->makeCreditNote($company, $customer, $warehouse),
            'debit_note' => $this->makeDebitNote($company, $vendor, $warehouse),
            'pos' => $this->makePosSale($company, $customer, $warehouse, $initialStatus),
            default => throw new \InvalidArgumentException(sprintf('Unsupported fiscal document kind [%s].', $kind)),
        };
    }

    /**
     * @return array{0:string,1:string}
     */
    private function fiscalRoutes(string $kind): array
    {
        return match ($kind) {
            'sales_invoice' => ['sales-invoices.fiscal-status', 'sales-invoices.cancel-fiscal'],
            'purchase_invoice' => ['purchase-invoices.fiscal-status', 'purchase-invoices.cancel-fiscal'],
            'sales_return' => ['sales-returns.fiscal-status', 'sales-returns.cancel-fiscal'],
            'purchase_return' => ['purchase-returns.fiscal-status', 'purchase-returns.cancel-fiscal'],
            'credit_note' => ['account.credit-notes.fiscal-status', 'account.credit-notes.cancel-fiscal'],
            'debit_note' => ['account.debit-notes.fiscal-status', 'account.debit-notes.cancel-fiscal'],
            'pos' => ['pos.fiscal-status', 'pos.cancel-fiscal'],
            default => throw new \InvalidArgumentException(sprintf('Unsupported fiscal document kind [%s].', $kind)),
        };
    }

    private function makeSalesInvoice(User $company, User $customer, Warehouse $warehouse, string $status): SalesInvoice
    {
        return SalesInvoice::create([
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
            'status' => $status,
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makePurchaseInvoice(User $company, User $vendor, Warehouse $warehouse, string $status): PurchaseInvoice
    {
        return PurchaseInvoice::create([
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
            'status' => $status,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeSalesReturn(User $company, User $customer, Warehouse $warehouse): SalesInvoiceReturn
    {
        $originalInvoice = $this->makeSalesInvoice($company, $customer, $warehouse, 'posted');

        return SalesInvoiceReturn::create([
            'return_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $originalInvoice->id,
            'reason' => 'damaged',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'approved',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makePurchaseReturn(User $company, User $vendor, Warehouse $warehouse): PurchaseReturn
    {
        $originalInvoice = $this->makePurchaseInvoice($company, $vendor, $warehouse, 'posted');

        return PurchaseReturn::create([
            'return_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $originalInvoice->id,
            'reason' => 'damaged',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'approved',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeCreditNote(User $company, User $customer, Warehouse $warehouse): CreditNote
    {
        $invoice = $this->makeSalesInvoice($company, $customer, $warehouse, 'posted');

        return CreditNote::create([
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'reason' => 'credit adj',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'establishment_id' => $warehouse->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDebitNote(User $company, User $vendor, Warehouse $warehouse): DebitNote
    {
        $invoice = $this->makePurchaseInvoice($company, $vendor, $warehouse, 'posted');

        return DebitNote::create([
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'invoice_id' => $invoice->id,
            'reason' => 'debit adj',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'establishment_id' => $warehouse->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makePosSale(User $company, User $customer, Warehouse $warehouse, string $status): Pos
    {
        $payload = [
            'sale_number' => 'POS-' . uniqid('', true),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'pos_date' => now()->toDateString(),
            'status' => $status,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ];

        if (Schema::hasColumn('pos', 'is_cancelled')) {
            $payload['is_cancelled'] = false;
        }

        return Pos::create($payload);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'name' => 'Company Matrix',
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

    /**
     * @return array<int, string>
     */
    private function fiscalPermissions(): array
    {
        return [
            'post-sales-invoices',
            'delete-sales-invoices',
            'manage-own-sales-invoices',
            'post-purchase-invoices',
            'delete-purchase-invoices',
            'manage-own-purchase-invoices',
            'manage-sales-return-invoices',
            'manage-own-sales-return-invoices',
            'approve-sales-returns-invoices',
            'complete-sales-returns-invoices',
            'delete-sales-return-invoices',
            'manage-purchase-return-invoices',
            'manage-own-purchase-return-invoices',
            'approve-purchase-returns-invoices',
            'complete-purchase-returns-invoices',
            'delete-purchase-return-invoices',
            'approve-credit-notes',
            'delete-credit-notes',
            'manage-own-credit-notes',
            'approve-debit-notes',
            'delete-debit-notes',
            'manage-own-debit-notes',
            'manage-pos-orders',
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
        $user->refresh();
    }
}
