<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Loan;
use Workdo\Hrm\Models\LoanType;

class VendorAdvanceAndEmployeeLoanAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_vendor_advance_payment_can_be_cleared_and_applied_to_supplier_invoice(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments', 'cleared-vendor-payments']);

        Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Adiantamento',
            'contact_person_name' => 'Tesouraria',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::query()->create([
            'invoice_number' => 'FR-ADV-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1500,
            'paid_amount' => 0,
            'balance_amount' => 1500,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'payment_purpose' => 'advance',
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'ADV-VEND-001',
            'payment_amount' => 1500,
            'currency_code' => 'MZN',
            'notes' => 'Supplier advance to be applied later.',
            'allocations' => [],
            'debit_notes' => [],
        ]);

        $response->assertSessionHasNoErrors();

        $payment = VendorPayment::query()
            ->where('reference_number', 'ADV-VEND-001')
            ->where('created_by', $company->id)
            ->firstOrFail();

        $this->assertSame('advance', (string) $payment->payment_purpose);
        $this->assertSame('pending', (string) $payment->status);

        $clearResponse = $this->actingAs($company)->post(route('account.vendor-payments.update-status', $payment->id), [
            'status' => 'cleared',
        ]);

        $clearResponse->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame('cleared', (string) $payment->status);

        $advanceJournal = JournalEntry::query()
            ->where('reference_type', 'vendor_payment')
            ->where('reference_id', $payment->id)
            ->firstOrFail();

        $advanceJournal->load('items.account');

        $this->assertSame(1500.00, (float) $advanceJournal->total_debit);
        $this->assertSame(1500.00, (float) $advanceJournal->total_credit);
        $this->assertSame(1500.00, (float) $this->sumJournalItems($advanceJournal, '1310', 'debit_amount'));
        $this->assertSame(1500.00, (float) $this->sumJournalItems($advanceJournal, '1030', 'credit_amount'));

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'transaction_type' => 'debit',
            'reference_number' => $payment->payment_number,
            'amount' => 1500.00,
        ]);

        $applyResponse = $this->actingAs($company)->post(route('account.vendor-payments.apply-advance', $payment->id), [
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1500],
            ],
        ]);

        $applyResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendor_payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 1500.00,
        ]);

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoice->id,
            'paid_amount' => 1500.00,
            'balance_amount' => 0.00,
            'status' => 'paid',
        ]);

        $settlementJournal = JournalEntry::query()
            ->where('reference_type', 'vendor_advance_to_invoice')
            ->where('reference_id', $payment->id)
            ->firstOrFail();

        $settlementJournal->load('items.account');

        $this->assertSame(1500.00, (float) $settlementJournal->total_debit);
        $this->assertSame(1500.00, (float) $settlementJournal->total_credit);
        $this->assertSame(1500.00, (float) $this->sumJournalItems($settlementJournal, '2000', 'debit_amount'));
        $this->assertSame(1500.00, (float) $this->sumJournalItems($settlementJournal, '1310', 'credit_amount'));
    }

    public function test_vendor_advance_payment_rejects_zero_value_amount(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);

        Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Adiantamento Zero',
            'contact_person_name' => 'Tesouraria',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'payment_purpose' => 'advance',
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'ADV-VEND-ZERO',
            'payment_amount' => 0,
            'currency_code' => 'MZN',
            'allocations' => [],
            'debit_notes' => [],
        ]);

        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'ADV-VEND-ZERO',
        ]);
    }

    public function test_employee_loan_creation_posts_advance_and_bank_disbursement(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeCounterpartyUser($company, 'staff');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-loans']);

        $employee = Employee::query()->create([
            'employee_id' => 'EMP-LOAN-001',
            'user_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $loanType = LoanType::query()->create([
            'name' => 'Adiantamento salarial',
            'description' => 'Loan type used for payroll advances.',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.loans.store'), [
            'employee_id' => $employee->id,
            'title' => 'Adiantamento salarial Junho 2026',
            'loan_type_id' => $loanType->id,
            'type' => 'fixed',
            'amount' => 1250,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'reason' => 'Adiantamento a recuperar na folha',
        ]);

        $response->assertSessionHasNoErrors();

        $loan = Loan::query()
            ->where('employee_id', $employeeUser->id)
            ->where('loan_type_id', $loanType->id)
            ->firstOrFail();

        $journal = JournalEntry::query()
            ->where('reference_type', 'employee_loan')
            ->where('reference_id', $loan->id)
            ->firstOrFail();

        $journal->load('items.account');

        $this->assertSame(1250.00, (float) $journal->total_debit);
        $this->assertSame(1250.00, (float) $journal->total_credit);
        $this->assertSame(1250.00, (float) $this->sumJournalItems($journal, '1320', 'debit_amount'));
        $this->assertSame(1250.00, (float) $this->sumJournalItems($journal, '1030', 'credit_amount'));

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bankAccount->id,
            'transaction_type' => 'debit',
            'reference_number' => 'LN-' . $loan->id,
            'amount' => 1250.00,
        ]);
    }

    private function makeCompany(): User
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        AccountUtility::defaultdata($company->id);

        return $company;
    }

    private function makeCounterpartyUser(User $company, string $type): User
    {
        return User::factory()->create([
            'type' => $type,
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeBankAccount(User $company, string $accountType = 'current'): BankAccount
    {
        $bankGlAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        return BankAccount::query()->create([
            'account_number' => 'ADV-001-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'account_name' => 'Conta Operacional',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => $accountType,
            'opening_balance' => 0,
            'current_balance' => 0,
            'iban' => 'MZ59000100000000000000123',
            'swift_code' => 'BCDMMZMA',
            'is_active' => true,
            'gl_account_id' => $bankGlAccount->id,
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

    private function sumJournalItems(JournalEntry $journalEntry, string $code, string $column): float
    {
        return round(
            (float) $journalEntry->items
                ->filter(fn ($item): bool => (string) $item->account?->account_code === $code)
                ->sum($column),
            2
        );
    }
}
