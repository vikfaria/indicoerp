<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Services\JournalService;
use Workdo\DoubleEntry\Services\TrialBalanceService;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;
use Workdo\ProductService\Models\ProductServiceItem;

class AccountingTrialBalanceEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_purchase_payroll_and_fx_journals_roll_up_to_a_balanced_trial_balance(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($company);

        AccountUtility::defaultdata($company->id);

        $bankAccount = $this->makeBankAccount($company);
        $customer = $this->makeCounterpartyUser($company, 'client', 'Cliente ACC002');
        $vendor = $this->makeCounterpartyUser($company, 'vendor', 'Fornecedor FX ACC002');

        $salesProduct = $this->makeProduct($company, 'Produto Venda ACC002', 1000, 400);
        $purchaseProduct = $this->makeProduct($company, 'Produto Compra ACC002', 500, 500);

        $salesInvoice = $this->makeSalesInvoice($company, $customer, 1000, 160);
        $this->makeSalesInvoiceItem($salesInvoice, $salesProduct, 1000, 16);

        $purchaseInvoice = $this->makePurchaseInvoice($company, $vendor, 500, 80);
        $this->makePurchaseInvoiceItem($purchaseInvoice, $purchaseProduct, 500, 16);

        $payrollEntry = $this->makePayrollEntry($company, $bankAccount);
        $vendorPayment = $this->makeFxVendorPayment($company, $vendor, $bankAccount, 580);

        $journalService = app(JournalService::class);

        $salesJournal = $journalService->createSalesInvoiceJournal($salesInvoice);
        $cogsJournal = $journalService->createSalesCOGSJournal($salesInvoice);
        $purchaseJournal = $journalService->createPurchaseInventoryJournal($purchaseInvoice);
        $payrollJournal = $journalService->createPayrollJournal($payrollEntry);
        $fxJournal = $journalService->createVendorPaymentJournal($vendorPayment);

        $this->assertNotNull($salesJournal);
        $this->assertNotNull($cogsJournal);
        $this->assertNotNull($purchaseJournal);
        $this->assertNotNull($payrollJournal);
        $this->assertNotNull($fxJournal);

        $this->assertSame(
            ['sales_invoice', 'sales_invoice_cogs', 'purchase_invoice', 'payroll', 'vendor_payment'],
            JournalEntry::query()->orderBy('id')->pluck('reference_type')->all()
        );

        $trialBalance = app(TrialBalanceService::class)->generateTrialBalance('2026-05-01 00:00:00', '2026-05-31 23:59:59');

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertSame(0.0, round(abs($trialBalance['total_debit'] - $trialBalance['total_credit']), 2));
        $this->assertGreaterThan(0, count($trialBalance['accounts']));

        $accounts = collect($trialBalance['accounts'])->keyBy('account_code');

        $this->assertTrialBalanceAccount($accounts, '1030', 0.0, 28420.0);
        $this->assertTrialBalanceAccount($accounts, '1100', 1160.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '1200', 100.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '1500', 80.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '2210', 0.0, 160.0);
        $this->assertTrialBalanceAccount($accounts, '4100', 0.0, 1000.0);
        $this->assertTrialBalanceAccount($accounts, '5100', 400.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '5200', 32000.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '5210', 1280.0, 0.0);
        $this->assertTrialBalanceAccount($accounts, '2200', 0.0, 1200.0);
        $this->assertTrialBalanceAccount($accounts, '2400', 0.0, 4240.0);

        $this->assertFalse($accounts->has('2000'));
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

    private function makeCounterpartyUser(User $company, string $type, string $name): User
    {
        return User::factory()->create([
            'type' => $type,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeBankAccount(User $company): BankAccount
    {
        $glAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        return BankAccount::query()->create([
            'account_number' => 'ACC002-BANK-001',
            'account_name' => 'Conta Banco ACC002',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'gl_account_id' => $glAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company, string $name, float $salePrice, float $purchasePrice): ProductServiceItem
    {
        return ProductServiceItem::query()->create([
            'name' => $name,
            'sku' => strtoupper(str_replace(' ', '-', $name)) . '-SKU',
            'category_id' => null,
            'description' => $name,
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'unit' => null,
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeSalesInvoice(User $company, User $customer, float $subtotal, float $taxAmount): SalesInvoice
    {
        return SalesInvoice::query()->create([
            'invoice_number' => 'FT-ACC002-001',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-20',
            'customer_id' => $customer->id,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $taxAmount,
            'paid_amount' => 0,
            'balance_amount' => $subtotal + $taxAmount,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);
    }

    private function makeSalesInvoiceItem(SalesInvoice $invoice, ProductServiceItem $product, float $unitPrice, float $taxPercentage): SalesInvoiceItem
    {
        return SalesInvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'tax_percentage' => $taxPercentage,
            'vat_code' => 'STD',
            'tax_exemption_reason' => null,
        ]);
    }

    private function makePurchaseInvoice(User $company, User $vendor, float $subtotal, float $taxAmount): PurchaseInvoice
    {
        return PurchaseInvoice::query()->create([
            'invoice_number' => 'FR-ACC002-001',
            'invoice_date' => '2026-05-12',
            'due_date' => '2026-05-22',
            'vendor_id' => $vendor->id,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $taxAmount,
            'paid_amount' => 0,
            'balance_amount' => $subtotal + $taxAmount,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'validated',
        ]);
    }

    private function makePurchaseInvoiceItem(PurchaseInvoice $invoice, ProductServiceItem $product, float $unitPrice, float $taxPercentage): PurchaseInvoiceItem
    {
        return PurchaseInvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'tax_percentage' => $taxPercentage,
            'vat_code' => 'STD',
            'tax_exemption_reason' => null,
        ]);
    }

    private function makePayrollEntry(User $company, BankAccount $bankAccount): PayrollEntry
    {
        $employeeUser = $this->makeCounterpartyUser($company, 'staff', 'Funcionario ACC002');

        Employee::query()->create([
            'employee_id' => 'EMP-ACC002-001',
            'user_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Maio 2026 ACC002',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 32000,
            'total_deductions' => 4160,
            'total_net_pay' => 27840,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'bank_account_id' => $bankAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'gross_pay' => 32000,
            'net_pay' => 27840,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 4160,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeFxVendorPayment(User $company, User $vendor, BankAccount $bankAccount, float $amountMzn)
    {
        return \Workdo\Account\Models\VendorPayment::query()->create([
            'payment_date' => '2026-05-25',
            'vendor_id' => $vendor->id,
            'payment_purpose' => 'settlement',
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-ACC002-001',
            'payment_amount' => $amountMzn,
            'currency_code' => 'USD',
            'exchange_rate' => 64,
            'foreign_amount' => 9.06,
            'amount_mzn' => $amountMzn,
            'fx_difference_amount' => 0,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20,
            'withholding_tax_amount' => 116,
            'fiscal_compliance_reference' => 'WHT-ACC002-001',
            'financial_approval_reference' => 'FIN-ACC002-001',
            'fx_authorization_reference' => 'FX-AUTH-ACC002-001',
            'contract_reference' => 'CTR-ACC002-001',
            'invoice_reference' => 'FR-ACC002-001',
            'bank_settlement_reference' => 'SET-ACC002-001',
            'withholding_receipt_reference' => 'WHT-RCPT-ACC002-001',
            'notes' => 'Remessa internacional de teste para ACC-002.',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function assertTrialBalanceAccount(\Illuminate\Support\Collection $accounts, string $accountCode, float $expectedDebit, float $expectedCredit): void
    {
        $this->assertTrue($accounts->has($accountCode), "Account {$accountCode} not found in trial balance.");

        $account = $accounts->get($accountCode);

        $this->assertSame(round($expectedDebit, 2), round((float) ($account['debit'] ?? 0), 2), "Unexpected debit for account {$accountCode}.");
        $this->assertSame(round($expectedCredit, 2), round((float) ($account['credit'] ?? 0), 2), "Unexpected credit for account {$accountCode}.");
    }
}
