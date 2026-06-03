<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AuditTrail;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use App\Models\WithholdingTaxTreatyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\ExchangeControlDossier;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Services\ReportService;
use Workdo\ProductService\Models\ProductServiceItem;

class AccountForeignCurrencyPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_customer_payment_stores_foreign_currency_fields_and_fx_difference(): void
    {
        $company = $this->makeCompany();
        $client = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-FX-CUST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $client->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $client->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-CUST-001',
            'payment_amount' => 1000,
            'currency_code' => 'usd',
            'exchange_rate' => 63.5,
            'foreign_amount' => 15,
            'is_export_receipt' => true,
            'receipt_origin_country' => 'South Africa',
            'export_reference' => 'EXP-2026-001',
            'intermediary_bank' => 'Banco Internacional',
            'repatriation_status' => 'pending',
            'fx_compliance_reference' => 'FX-IN-2026-001',
            'notes' => 'Liquidação em USD com diferença cambial reconhecida.',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'credit_notes' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $client->id,
            'currency_code' => 'USD',
            'exchange_rate' => 63.500000,
            'foreign_amount' => 15.00,
            'amount_mzn' => 1000.00,
            'fx_difference_amount' => 47.50,
            'is_export_receipt' => 1,
            'receipt_origin_country' => 'South Africa',
            'export_reference' => 'EXP-2026-001',
            'intermediary_bank' => 'Banco Internacional',
            'repatriation_status' => 'pending',
            'fx_compliance_reference' => 'FX-IN-2026-001',
        ]);

        $payment = CustomerPayment::query()
            ->where('reference_number', 'FX-CUST-001')
            ->where('created_by', $company->id)
            ->firstOrFail();

        $dossier = ExchangeControlDossier::query()
            ->where('company_id', $company->id)
            ->where('direction', 'inbound')
            ->where('payment_type', 'customer_payment')
            ->where('payment_id', $payment->id)
            ->first();

        $this->assertNotNull($dossier);
        $this->assertFalse((bool) $dossier->is_complete);
        $this->assertSame('EXP-2026-001', data_get($dossier->documents, 'invoice_reference'));
        $this->assertSame('FX-IN-2026-001', data_get($dossier->documents, 'bank_settlement_reference'));
        $this->assertContains('contract_reference', $dossier->missing_documents ?? []);
    }

    public function test_vendor_payment_blocks_international_bank_transfer_without_settlement_identifiers(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, ['create-vendor-payments']);

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Sem Swift',
            'contact_person_name' => 'Fiscal',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankAccount = BankAccount::create([
            'account_number' => 'INT-NO-SWIFT-001',
            'account_name' => 'Conta Sem Identificador Internacional',
            'bank_name' => 'Banco Local',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-INT-NO-SWIFT-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
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

        WithholdingTaxRule::create([
            'code' => 'IRPC-SRV-NR-BANK',
            'name' => 'Retencao servicos internacionais',
            'income_type' => 'services',
            'rate' => 20.00,
            'applies_to' => 'non_resident',
            'is_final_tax' => false,
            'is_active' => true,
            'legal_basis' => 'Base teste',
            'pgc_debit_account' => '622',
            'pgc_credit_account' => '245',
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'INT-NO-SWIFT-PAY-001',
            'payment_amount' => 1500,
            'currency_code' => 'USD',
            'exchange_rate' => 64,
            'foreign_amount' => 23.4375,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20,
            'withholding_tax_amount' => 300,
            'fiscal_compliance_reference' => 'WHT-NO-SWIFT-001',
            'financial_approval_reference' => 'FIN-NO-SWIFT-001',
            'fx_authorization_reference' => 'FX-NO-SWIFT-001',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1500],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'INT-NO-SWIFT-PAY-001',
        ]);
    }

    public function test_customer_payment_blocks_domestic_foreign_currency_without_export_context(): void
    {
        $company = $this->makeCompany();
        $client = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);

        Customer::create([
            'user_id' => $client->id,
            'company_name' => 'Cliente Nacional',
            'contact_person_name' => 'Tesouraria',
            'contact_person_email' => 'tesouraria@nacional.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-FX-CUST-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $client->id,
            'subtotal' => 500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'balance_amount' => 500,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $client->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-CUST-002',
            'payment_amount' => 500,
            'currency_code' => 'USD',
            'exchange_rate' => 63.5,
            'foreign_amount' => 8,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 500],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['currency_code']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'FX-CUST-002',
        ]);
    }

    public function test_customer_export_receipt_blocks_cash_channel_for_cross_border_compliance(): void
    {
        $company = $this->makeCompany();
        $client = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);

        Customer::create([
            'user_id' => $client->id,
            'company_name' => 'Cliente Exportacao',
            'contact_person_name' => 'Tesouraria',
            'contact_person_email' => 'tesouraria@exportacao.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-FX-CUST-003',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $client->id,
            'subtotal' => 630,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 630,
            'paid_amount' => 0,
            'balance_amount' => 630,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $client->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'cash',
            'reference_number' => 'FX-CUST-003',
            'payment_amount' => 630,
            'currency_code' => 'USD',
            'exchange_rate' => 63,
            'foreign_amount' => 10,
            'is_export_receipt' => true,
            'receipt_origin_country' => 'South Africa',
            'export_reference' => 'EXP-2026-003',
            'intermediary_bank' => 'Banco Internacional',
            'fx_compliance_reference' => 'FX-IN-2026-003',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 630],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['payment_method']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'FX-CUST-003',
        ]);
    }

    public function test_customer_cash_payment_requires_cashbox_account(): void
    {
        $company = $this->makeCompany();
        $client = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);

        Customer::create([
            'user_id' => $client->id,
            'company_name' => 'Cliente Caixa',
            'contact_person_name' => 'Tesouraria',
            'contact_person_email' => 'tesouraria@caixa.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-CAIXA-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $client->id,
            'subtotal' => 630,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 630,
            'paid_amount' => 0,
            'balance_amount' => 630,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $client->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'cash',
            'reference_number' => 'CASH-CUST-001',
            'payment_amount' => 630,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 630],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'CASH-CUST-001',
        ]);
    }

    public function test_vendor_payment_stores_foreign_currency_fields_and_fx_difference(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-SRV-NR',
            incomeType: 'services',
            appliesTo: 'non_resident',
            rate: 20
        );

        Vendor::create([
            'user_id' => $vendor->id,
            'company_name' => 'Fornecedor Internacional Teste',
            'contact_person_name' => 'Responsável Fiscal',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendor->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-001',
            'payment_amount' => 1000,
            'currency_code' => 'eur',
            'exchange_rate' => 70,
            'foreign_amount' => 14,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20,
            'withholding_tax_amount' => 200,
            'fiscal_compliance_reference' => 'WHT-2026-0001',
            'financial_approval_reference' => 'FIN-APP-2026-0009',
            'fx_authorization_reference' => 'BM-AUT-2026-007',
            'notes' => 'Pagamento em EUR com diferença cambial reconhecida.',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_payments', [
            'vendor_id' => $vendor->id,
            'currency_code' => 'EUR',
            'exchange_rate' => 70.000000,
            'foreign_amount' => 14.00,
            'amount_mzn' => 1000.00,
            'fx_difference_amount' => 20.00,
            'is_international_payment' => 1,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20.0000,
            'withholding_tax_amount' => 200.00,
            'fiscal_compliance_reference' => 'WHT-2026-0001',
            'financial_approval_reference' => 'FIN-APP-2026-0009',
            'fx_authorization_reference' => 'BM-AUT-2026-007',
        ]);

        $this->assertDatabaseHas('withholding_tax_transactions', [
            'company_id' => $company->id,
            'vendor_id' => $vendor->id,
            'source_reference_type' => 'vendor_payment',
            'document_reference' => 'VP-' . now()->format('Y-m') . '-001',
            'income_type_snapshot' => 'services',
            'withholding_treatment' => 'withheld',
            'withholding_rate' => 20.00,
            'withholding_amount' => 200.00,
            'gross_amount' => 1000.00,
            'net_amount' => 800.00,
            'status' => 'pending',
        ]);
    }

    public function test_vendor_payment_blocks_non_resident_remittance_without_withholding_compliance_evidence(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Internacional',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => null,
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => false,
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-002',
            'payment_amount' => 1000,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors([
            'withholding_tax_treatment',
            'fiscal_compliance_reference',
            'financial_approval_reference',
            'fx_authorization_reference',
        ]);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-002',
        ]);
    }

    public function test_vendor_payment_blocks_international_cash_remittance_not_processed_through_authorized_channel(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-SRV-NR-CH',
            incomeType: 'services',
            appliesTo: 'non_resident',
            rate: 20
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Internacional Canal',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => null,
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => false,
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-002A',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'cash',
            'reference_number' => 'FX-VEND-002A',
            'payment_amount' => 1000,
            'currency_code' => 'MZN',
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20,
            'withholding_tax_amount' => 200,
            'fiscal_compliance_reference' => 'WHT-2026-002A',
            'financial_approval_reference' => 'FIN-APP-2026-002A',
            'fx_authorization_reference' => 'BM-AUT-2026-002A',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['payment_method']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-002A',
        ]);
    }

    public function test_vendor_cash_payment_accepts_cashbox_account(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company, 'cashbox');
        $this->grantPermissions($company, ['create-vendor-payments']);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-CAIXA-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'balance_amount' => 500,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'cash',
            'reference_number' => 'CASH-VEND-001',
            'payment_amount' => 500,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 500],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_payments', [
            'reference_number' => 'CASH-VEND-001',
            'payment_method' => 'cash',
        ]);
    }

    public function test_vendor_payment_with_adt_reduced_rate_requires_matching_rule_and_creates_withholding_history(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-ROY-NR',
            incomeType: 'royalties',
            appliesTo: 'non_resident',
            rate: 20
        );
        $this->seedTreatyRate(
            company: $company,
            countryCode: 'PT',
            countryName: 'Portugal',
            incomeType: 'royalties',
            treatyRate: 10,
            standardRate: 20,
            code: 'ADT-MZ-PT-ROY'
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Licenças',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => true,
            'adt_country' => 'Portugal',
            'compliance_documents' => ['Residency Certificate ADT-2026-0003'],
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-003',
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
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-003',
            'payment_amount' => 1500,
            'currency_code' => 'EUR',
            'exchange_rate' => 75,
            'foreign_amount' => 20,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'licensing',
            'withholding_tax_treatment' => 'adt_reduced',
            'withholding_tax_rate' => 10,
            'withholding_tax_amount' => 150,
            'adt_certificate_reference' => 'ADT-2026-0003',
            'fiscal_compliance_reference' => 'WHT-2026-0003',
            'financial_approval_reference' => 'FIN-APP-2026-0015',
            'fx_authorization_reference' => 'BM-AUT-2026-015',
            'contract_reference' => 'CTR-2026-0003',
            'invoice_reference' => 'FR-FX-VEND-003',
            'bank_settlement_reference' => 'BANK-SETTLE-2026-0003',
            'withholding_receipt_reference' => 'WHT-REC-2026-0003',
            'correspondence_reference' => 'MAIL-BANK-2026-0003',
            'notes' => 'Pagamento com taxa reduzida por ADT.',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1500],
            ],
            'debit_notes' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withholding_tax_transactions', [
            'company_id' => $company->id,
            'vendor_id' => $vendorUser->id,
            'source_reference_type' => 'vendor_payment',
            'income_type_snapshot' => 'royalties',
            'withholding_treatment' => 'adt_reduced',
            'adt_applied' => 1,
            'adt_certificate_reference' => 'ADT-2026-0003',
            'withholding_rate' => 10.00,
            'withholding_amount' => 150.00,
            'gross_amount' => 1500.00,
            'net_amount' => 1350.00,
            'vendor_nuit' => '400123456',
            'status' => 'pending',
        ]);

        $dossier = ExchangeControlDossier::query()
            ->where('company_id', $company->id)
            ->where('direction', 'outbound')
            ->where('payment_type', 'vendor_payment')
            ->first();

        $this->assertNotNull($dossier);
        $this->assertTrue((bool) $dossier->is_complete);
        $this->assertSame('CTR-2026-0003', data_get($dossier->documents, 'contract_reference'));
        $this->assertSame('FR-FX-VEND-003', data_get($dossier->documents, 'invoice_reference'));
        $this->assertSame('BANK-SETTLE-2026-0003', data_get($dossier->documents, 'bank_settlement_reference'));
        $this->assertSame('WHT-REC-2026-0003', data_get($dossier->documents, 'withholding_receipt_reference'));
        $this->assertSame('BM-AUT-2026-015', data_get($dossier->documents, 'fx_authorization_reference'));
    }

    public function test_vendor_payment_with_adt_reduced_rate_requires_configured_treaty_rate(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-AT-NR',
            incomeType: 'technical_assistance',
            appliesTo: 'non_resident',
            rate: 20
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Digital Sem ADT',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => '400123458',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => true,
            'adt_country' => 'Portugal',
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-004',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-004',
            'payment_amount' => 1000,
            'currency_code' => 'EUR',
            'exchange_rate' => 75,
            'foreign_amount' => 13.33,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'digital_services',
            'withholding_tax_treatment' => 'adt_reduced',
            'withholding_tax_rate' => 10,
            'withholding_tax_amount' => 100,
            'adt_certificate_reference' => 'ADT-2026-004',
            'fiscal_compliance_reference' => 'WHT-2026-0004',
            'financial_approval_reference' => 'FIN-APP-2026-0020',
            'fx_authorization_reference' => 'BM-AUT-2026-020',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['withholding_tax_treatment']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-004',
        ]);
    }

    public function test_vendor_payment_with_adt_reduced_rate_requires_matching_vendor_residency_certificate_document(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-ROY-NR-DOC',
            incomeType: 'royalties',
            appliesTo: 'non_resident',
            rate: 20
        );
        $this->seedTreatyRate(
            company: $company,
            countryCode: 'PT',
            countryName: 'Portugal',
            incomeType: 'royalties',
            treatyRate: 10,
            standardRate: 20,
            code: 'ADT-MZ-PT-ROY-DOC'
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor ADT Sem Certificado',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => '400123460',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => true,
            'adt_country' => 'Portugal',
            'compliance_documents' => ['Passport copy PT-2026', 'Commercial contract CTR-PT-001'],
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-003B',
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

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-003B',
            'payment_amount' => 1500,
            'currency_code' => 'EUR',
            'exchange_rate' => 75,
            'foreign_amount' => 20,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'licensing',
            'withholding_tax_treatment' => 'adt_reduced',
            'withholding_tax_rate' => 10,
            'withholding_tax_amount' => 150,
            'adt_certificate_reference' => 'ADT-2026-9999',
            'fiscal_compliance_reference' => 'WHT-2026-9999',
            'financial_approval_reference' => 'FIN-APP-2026-0099',
            'fx_authorization_reference' => 'BM-AUT-2026-099',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1500],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['adt_certificate_reference']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-003B',
        ]);
    }

    public function test_vendor_payment_with_adt_reduced_rate_rejects_rate_mismatch_against_treaty(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->seedWithholdingRule(
            code: 'IRPC-AT-NR-2',
            incomeType: 'technical_assistance',
            appliesTo: 'non_resident',
            rate: 20
        );
        $this->seedTreatyRate(
            company: $company,
            countryCode: 'PT',
            countryName: 'Portugal',
            incomeType: 'technical_assistance',
            treatyRate: 10,
            standardRate: 20,
            code: 'ADT-MZ-PT-TECH'
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Digital ADT',
            'contact_person_name' => 'Gestor Fiscal',
            'tax_number' => '400123459',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'adt_eligible' => true,
            'adt_country' => 'Portugal',
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-VEND-005',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-VEND-005',
            'payment_amount' => 1000,
            'currency_code' => 'EUR',
            'exchange_rate' => 75,
            'foreign_amount' => 13.33,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'digital_services',
            'withholding_tax_treatment' => 'adt_reduced',
            'withholding_tax_rate' => 12,
            'withholding_tax_amount' => 120,
            'adt_certificate_reference' => 'ADT-2026-005',
            'fiscal_compliance_reference' => 'WHT-2026-0005',
            'financial_approval_reference' => 'FIN-APP-2026-0021',
            'fx_authorization_reference' => 'BM-AUT-2026-021',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['withholding_tax_rate']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-005',
        ]);
    }

    public function test_exchange_control_report_summarizes_fx_operations_and_flags_compliance_gaps(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($company);

        $residentVendorUser = $this->makeCounterpartyUser($company, 'vendor');
        Vendor::create([
            'user_id' => $residentVendorUser->id,
            'company_name' => 'Fornecedor Nacional FX',
            'contact_person_name' => 'Operações',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::create([
            'payment_date' => now()->toDateString(),
            'vendor_id' => $residentVendorUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-RPT-OUT-001',
            'payment_amount' => 1200,
            'currency_code' => 'USD',
            'exchange_rate' => 63.5,
            'foreign_amount' => 18.9,
            'amount_mzn' => 1200,
            'fx_difference_amount' => 0,
            'is_international_payment' => true,
            'beneficiary_country' => 'Mozambique',
            'service_type' => 'consulting',
            'withholding_tax_treatment' => 'withheld',
            'withholding_tax_rate' => 20,
            'withholding_tax_amount' => 240,
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $customerUser = $this->makeCounterpartyUser($company, 'client');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'Cliente Exportador',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@exportador.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::create([
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-RPT-IN-001',
            'payment_amount' => 900,
            'currency_code' => 'USD',
            'exchange_rate' => 63.5,
            'foreign_amount' => 14.2,
            'amount_mzn' => 900,
            'fx_difference_amount' => 0,
            'is_export_receipt' => true,
            'receipt_origin_country' => 'South Africa',
            'export_reference' => 'EXP-RPT-001',
            'intermediary_bank' => 'Banco Inter',
            'repatriation_status' => 'pending',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $report = app(ReportService::class)->getMozambiqueExchangeControlReport([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(2, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(1, (int) data_get($report, 'summary.domestic_fx_violations'));
        $this->assertSame(2, (int) data_get($report, 'summary.missing_fx_documentation'));
        $this->assertSame(2, (int) data_get($report, 'summary.missing_dossier_count'));
        $this->assertSame(0, (int) data_get($report, 'summary.completed_dossier_count'));
        $this->assertSame(1, (int) data_get($report, 'summary.pending_repatriation_count'));
    }

    public function test_exchange_control_repatriation_endpoint_updates_export_receipt(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        $customerUser = $this->makeCounterpartyUser($company, 'client');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'Cliente Exportador Endpoint',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@endpoint.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payment = CustomerPayment::create([
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-REP-EP-001',
            'payment_amount' => 900,
            'currency_code' => 'USD',
            'exchange_rate' => 63.5,
            'foreign_amount' => 14.2,
            'amount_mzn' => 900,
            'is_export_receipt' => true,
            'export_reference' => 'EXP-REP-EP-001',
            'intermediary_bank' => 'Banco Intermediário',
            'repatriation_status' => 'pending',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->postJson(route('account.reports.mozambique-exchange-control-report.repatriation'), [
            'payment_id' => $payment->id,
            'repatriation_status' => 'completed',
            'repatriated_amount_mzn' => 900,
            'fx_compliance_reference' => 'FX-REP-COMP-001',
            'receipt_origin_country' => 'South Africa',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.payment_id', $payment->id);
        $response->assertJsonPath('data.repatriation_status', 'completed');
        $response->assertJsonPath('data.repatriated_amount_mzn', 900);
        $response->assertJsonPath('data.fx_compliance_reference', 'FX-REP-COMP-001');

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'repatriation_status' => 'completed',
            'repatriated_amount_mzn' => 900.00,
            'fx_compliance_reference' => 'FX-REP-COMP-001',
            'receipt_origin_country' => 'South Africa',
        ]);

        $dossier = ExchangeControlDossier::query()
            ->where('company_id', $company->id)
            ->where('direction', 'inbound')
            ->where('payment_type', 'customer_payment')
            ->where('payment_id', $payment->id)
            ->first();

        $this->assertNotNull($dossier);
        $this->assertSame('EXP-REP-EP-001', data_get($dossier->documents, 'invoice_reference'));
        $this->assertSame('FX-REP-COMP-001', data_get($dossier->documents, 'bank_settlement_reference'));
        $this->assertSame('South Africa', (string) ($dossier->counterparty_country ?? ''));

        $auditEntry = AuditTrail::query()
            ->where('auditable_type', CustomerPayment::class)
            ->where('auditable_id', $payment->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame('account.reports.mozambique-exchange-control-report.repatriation', $auditEntry->route);
        $this->assertSame('completed', (string) data_get($auditEntry->changes, 'repatriation_status'));
        $this->assertSame('FX-REP-COMP-001', (string) data_get($auditEntry->changes, 'fx_compliance_reference'));
    }

    public function test_exchange_control_dossier_endpoint_supports_partial_and_complete_workflow(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Dossier',
            'contact_person_name' => 'Fiscal',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payment = VendorPayment::create([
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'FX-DOS-OUT-001',
            'payment_amount' => 1500,
            'currency_code' => 'USD',
            'exchange_rate' => 64,
            'foreign_amount' => 23.44,
            'amount_mzn' => 1500,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'withholding_tax_treatment' => 'withheld',
            'fiscal_compliance_reference' => 'WHT-DOS-001',
            'financial_approval_reference' => 'FIN-DOS-001',
            'fx_authorization_reference' => 'FXAUTH-DOS-001',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $draftResponse = $this->postJson(route('account.reports.mozambique-exchange-control-report.dossier'), [
            'direction' => 'outbound',
            'payment_id' => $payment->id,
            'contract_reference' => 'CTR-001',
            'invoice_reference' => 'INV-001',
        ]);

        $draftResponse->assertOk();
        $this->assertFalse((bool) data_get($draftResponse->json(), 'data.is_complete'));
        $this->assertContains('bank_settlement_reference', (array) data_get($draftResponse->json(), 'data.missing_documents', []));
        $this->assertContains('withholding_receipt_reference', (array) data_get($draftResponse->json(), 'data.missing_documents', []));

        $finalResponse = $this->postJson(route('account.reports.mozambique-exchange-control-report.dossier'), [
            'direction' => 'outbound',
            'payment_id' => $payment->id,
            'bank_settlement_reference' => 'BANK-SETTLE-001',
            'withholding_receipt_reference' => 'WHT-REC-001',
            'fx_authorization_reference' => 'FXAUTH-DOS-001',
            'correspondence_reference' => 'MAIL-001',
        ]);

        $finalResponse->assertOk();
        $this->assertTrue((bool) data_get($finalResponse->json(), 'data.is_complete'));
        $this->assertSame([], (array) data_get($finalResponse->json(), 'data.missing_documents', []));

        $this->assertDatabaseHas('exchange_control_dossiers', [
            'company_id' => $company->id,
            'direction' => 'outbound',
            'payment_type' => 'vendor_payment',
            'payment_id' => $payment->id,
            'is_complete' => 1,
        ]);

        $dossier = ExchangeControlDossier::query()
            ->where('company_id', $company->id)
            ->where('direction', 'outbound')
            ->where('payment_type', 'vendor_payment')
            ->where('payment_id', $payment->id)
            ->first();

        $this->assertNotNull($dossier);

        $auditEntries = AuditTrail::query()
            ->where('auditable_type', ExchangeControlDossier::class)
            ->where('auditable_id', $dossier->id)
            ->orderBy('id')
            ->get();

        $this->assertTrue($auditEntries->pluck('event')->contains('created'));
        $this->assertTrue($auditEntries->pluck('event')->contains('updated'));

        $latestAudit = $auditEntries->last();
        $this->assertNotNull($latestAudit);
        $this->assertSame('account.reports.mozambique-exchange-control-report.dossier', $latestAudit->route);
        $this->assertTrue((bool) data_get($latestAudit->changes, 'is_complete'));

        $report = app(ReportService::class)->getMozambiqueExchangeControlReport([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(1, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(0, (int) data_get($report, 'summary.missing_dossier_count'));
        $this->assertSame(1, (int) data_get($report, 'summary.completed_dossier_count'));
    }

    public function test_reverse_charge_report_includes_non_resident_operations_and_endpoint_response(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Reverse Charge',
            'contact_person_name' => 'Fiscal',
            'tax_number' => null,
            'foreign_tax_number' => 'PT-12345678',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'reverse_charge_applicable' => true,
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::create([
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'RC-RPT-001',
            'payment_amount' => 1600,
            'amount_mzn' => 1600,
            'currency_code' => 'USD',
            'is_international_payment' => true,
            'service_type' => 'digital_services',
            'beneficiary_country' => 'Portugal',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $report = app(ReportService::class)->getMozambiqueReverseChargeReport([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(1, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(1600.0, (float) data_get($report, 'summary.total_base_amount_mzn'));
        $this->assertSame(256.0, (float) data_get($report, 'summary.total_vat_liquidated_mzn'));
        $this->assertSame(0, (int) data_get($report, 'summary.missing_supplier_tax_identifier_count'));
        $this->assertSame(0, (int) data_get($report, 'summary.missing_service_type_count'));

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-reverse-charge-report', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.total_operations', 1);
        $response->assertJsonPath('operations.0.payment_reference', 'VP-' . now()->format('Y-m') . '-001');
    }

    public function test_international_withholding_report_summarizes_non_resident_transactions_and_documentation_gaps(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $rule = WithholdingTaxRule::create([
            'code' => 'IRPC-SRV-NR-RPT',
            'name' => 'Retenção internacional reporte',
            'income_type' => 'services',
            'rate' => 20.00,
            'applies_to' => 'non_resident',
            'is_final_tax' => false,
            'is_active' => true,
            'legal_basis' => 'Base teste',
            'pgc_debit_account' => '622',
            'pgc_credit_account' => '245',
        ]);

        WithholdingTaxTransaction::create([
            'company_id' => $company->id,
            'withholding_rule_id' => $rule->id,
            'vendor_name' => 'Fornecedor Internacional A',
            'vendor_nuit' => null,
            'beneficiary_country' => 'PT',
            'beneficiary_residency_status' => 'non_resident',
            'income_type_snapshot' => 'services',
            'transaction_date' => now()->toDateString(),
            'document_reference' => 'WHT-INT-001',
            'source_reference_type' => 'vendor_payment',
            'gross_amount' => 1000,
            'withholding_rate' => 20,
            'withholding_treatment' => 'withheld',
            'adt_applied' => false,
            'fiscal_compliance_reference' => null,
            'financial_approval_reference' => null,
            'fx_authorization_reference' => null,
            'withholding_amount' => 200,
            'net_amount' => 800,
            'fiscal_year' => now()->format('Y'),
            'fiscal_month' => (int) now()->format('n'),
            'status' => 'pending',
            'created_by' => $company->id,
        ]);

        WithholdingTaxTransaction::create([
            'company_id' => $company->id,
            'withholding_rule_id' => $rule->id,
            'vendor_name' => 'Fornecedor Internacional B',
            'vendor_nuit' => '500123456',
            'beneficiary_country' => 'ZA',
            'beneficiary_residency_status' => 'non_resident',
            'income_type_snapshot' => 'royalties',
            'transaction_date' => now()->toDateString(),
            'document_reference' => 'WHT-INT-002',
            'source_reference_type' => 'vendor_payment',
            'gross_amount' => 2000,
            'withholding_rate' => 10,
            'withholding_treatment' => 'adt_reduced',
            'adt_applied' => true,
            'adt_certificate_reference' => 'ADT-INT-002',
            'fiscal_compliance_reference' => 'WHT-FISC-002',
            'financial_approval_reference' => 'FIN-APP-002',
            'fx_authorization_reference' => 'FX-AUTH-002',
            'withholding_amount' => 200,
            'net_amount' => 1800,
            'fiscal_year' => now()->format('Y'),
            'fiscal_month' => (int) now()->format('n'),
            'status' => 'declared',
            'created_by' => $company->id,
        ]);

        $report = app(ReportService::class)->getMozambiqueInternationalWithholdingReport([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(2, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(3000.0, (float) data_get($report, 'summary.total_gross_amount'));
        $this->assertSame(400.0, (float) data_get($report, 'summary.total_withholding_amount'));
        $this->assertSame(2600.0, (float) data_get($report, 'summary.total_net_amount'));
        $this->assertSame(1, (int) data_get($report, 'summary.adt_applied_count'));
        $this->assertSame(2, (int) data_get($report, 'summary.pending_state_payment_count'));
        $this->assertSame(1, (int) data_get($report, 'summary.missing_supporting_documents_count'));

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-international-withholding-report', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.total_operations', 2);
        $response->assertJsonPath('summary.missing_supporting_documents_count', 1);
    }

    public function test_vendor_payment_requires_high_value_approval_reference_when_gifim_threshold_is_triggered(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Nacional HV',
            'contact_person_name' => 'Compliance',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'withholding_tax_applicable' => false,
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-GIFIM-VEND-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 800000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 800000,
            'paid_amount' => 0,
            'balance_amount' => 800000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'GIFIM-VEND-001',
            'payment_amount' => 800000,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 800000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['high_value_approval_reference']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'GIFIM-VEND-001',
        ]);
    }

    public function test_vendor_payment_persists_gifim_fields_when_threshold_is_met(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Nacional GIFiM',
            'contact_person_name' => 'Tesouraria',
            'tax_number' => '400123457',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'withholding_tax_applicable' => false,
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-GIFIM-VEND-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 820000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 820000,
            'paid_amount' => 0,
            'balance_amount' => 820000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'GIFIM-VEND-002',
            'payment_amount' => 820000,
            'currency_code' => 'MZN',
            'gifim_alert_status' => 'communicated',
            'gifim_reference' => 'GIFIM-COM-2026-002',
            'gifim_submitted_document' => 'comprovativo-gifim-002.pdf',
            'gifim_justification' => 'Comunicação efectuada por obrigação legal.',
            'high_value_approval_reference' => 'FIN-APP-HV-2026-002',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 820000],
            ],
            'debit_notes' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_payments', [
            'reference_number' => 'GIFIM-VEND-002',
            'gifim_alert_required' => 1,
            'gifim_alert_category' => 'electronic_threshold',
            'gifim_alert_status' => 'communicated',
            'gifim_reference' => 'GIFIM-COM-2026-002',
            'gifim_submitted_document' => 'comprovativo-gifim-002.pdf',
            'high_value_approval_reference' => 'FIN-APP-HV-2026-002',
        ]);
    }

    public function test_gifim_compliance_report_and_communication_endpoint_work_for_high_value_operations(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary', 'manage-account-reports']);
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Teste GIFiM',
            'contact_person_name' => 'Compliance',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $customerUser = $this->makeCounterpartyUser($company, 'client');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'Cliente Teste GIFiM',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@gifim.test',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $vendorPayment = VendorPayment::create([
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'GIFIM-RPT-OUT-001',
            'payment_amount' => 900000,
            'currency_code' => 'MZN',
            'amount_mzn' => 900000,
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::create([
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'cash',
            'reference_number' => 'GIFIM-RPT-IN-001',
            'payment_amount' => 300000,
            'currency_code' => 'MZN',
            'amount_mzn' => 300000,
            'gifim_alert_status' => 'communicated',
            'gifim_reference' => 'GIFIM-IN-2026-001',
            'gifim_submitted_document' => 'comprovativo-in-001.pdf',
            'high_value_approval_reference' => 'FIN-APP-HV-IN-001',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $report = app(ReportService::class)->getMozambiqueGifimComplianceReport([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame(2, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(2, (int) data_get($report, 'summary.total_alert_required'));
        $this->assertSame(1, (int) data_get($report, 'summary.pending_alerts'));
        $this->assertSame(1, (int) data_get($report, 'summary.communicated_alerts'));
        $this->assertSame(1, (int) data_get($report, 'summary.cash_threshold_alerts'));
        $this->assertSame(1, (int) data_get($report, 'summary.electronic_threshold_alerts'));
        $this->assertSame(1, (int) data_get($report, 'summary.missing_high_value_approval_reference'));

        $response = $this->postJson(route('account.reports.mozambique-gifim-compliance-report.communicate'), [
            'direction' => 'outbound',
            'payment_id' => $vendorPayment->id,
            'gifim_reference' => 'GIFIM-OUT-2026-009',
            'gifim_submitted_document' => 'comprovativo-out-009.pdf',
            'high_value_approval_reference' => 'FIN-APP-HV-OUT-009',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.gifim_alert_status', 'communicated');

        $this->assertDatabaseHas('vendor_payments', [
            'id' => $vendorPayment->id,
            'gifim_alert_required' => 1,
            'gifim_alert_category' => 'electronic_threshold',
            'gifim_alert_status' => 'communicated',
            'gifim_reference' => 'GIFIM-OUT-2026-009',
            'gifim_submitted_document' => 'comprovativo-out-009.pdf',
            'high_value_approval_reference' => 'FIN-APP-HV-OUT-009',
        ]);

        $this->assertDatabaseHas('fiscal_export_histories', [
            'company_id' => $company->id,
            'export_type' => 'gifim_communication_notice',
            'status' => 'generated',
        ]);
    }

    public function test_vendor_mobile_money_payment_blocks_when_electronic_money_monthly_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, ['create-vendor-payments']);

        $bankAccount = BankAccount::create([
            'account_number' => 'EM-VEND-001',
            'account_name' => 'Conta Móvel Fornecedor',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'Vodacom M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 10000,
            'electronic_money_monthly_limit_mzn' => 1000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::create([
            'payment_number' => 'VP-EM-VEND-BASE-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'mpesa',
            'mobile_money_number' => '+258840000000',
            'reference_number' => 'EM-VEND-BASE-001',
            'payment_amount' => 800,
            'amount_mzn' => 800,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-EM-VEND-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 400,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 400,
            'paid_amount' => 0,
            'balance_amount' => 400,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'mpesa',
            'mobile_money_number' => '+258840111111',
            'reference_number' => 'EM-VEND-LIMIT-001',
            'payment_amount' => 400,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 400],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'EM-VEND-LIMIT-001',
        ]);
    }

    public function test_customer_mobile_money_payment_blocks_when_electronic_money_monthly_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $customerUser = $this->makeCounterpartyUser($company, 'client');
        $this->grantPermissions($company, ['create-customer-payments']);

        $bankAccount = BankAccount::create([
            'account_number' => 'EM-CUST-001',
            'account_name' => 'Conta Móvel Cliente',
            'bank_name' => 'e-Mola',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'e-Mola',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 10000,
            'electronic_money_monthly_limit_mzn' => 1000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::create([
            'payment_number' => 'CP-EM-CUST-BASE-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'emola',
            'mobile_money_number' => '+258850000000',
            'reference_number' => 'EM-CUST-BASE-001',
            'payment_amount' => 700,
            'amount_mzn' => 700,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-EM-CUST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customerUser->id,
            'subtotal' => 400,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 400,
            'paid_amount' => 0,
            'balance_amount' => 400,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'emola',
            'mobile_money_number' => '+258850111111',
            'reference_number' => 'EM-CUST-LIMIT-001',
            'payment_amount' => 400,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 400],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'EM-CUST-LIMIT-001',
        ]);
    }

    public function test_vendor_mobile_money_payment_requires_electronic_money_account(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, ['create-vendor-payments']);

        $bankAccount = $this->makeBankAccount($company);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-EM-VEND-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 250,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 250,
            'paid_amount' => 0,
            'balance_amount' => 250,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'mpesa',
            'mobile_money_number' => '+258840222222',
            'reference_number' => 'EM-VEND-NON-EM-001',
            'payment_amount' => 250,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 250],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'EM-VEND-NON-EM-001',
        ]);
    }

    public function test_customer_mobile_money_payment_requires_electronic_money_account(): void
    {
        $company = $this->makeCompany();
        $customerUser = $this->makeCounterpartyUser($company, 'client');
        $this->grantPermissions($company, ['create-customer-payments']);

        $bankAccount = $this->makeBankAccount($company);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-EM-CUST-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customerUser->id,
            'subtotal' => 250,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 250,
            'paid_amount' => 0,
            'balance_amount' => 250,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'emola',
            'mobile_money_number' => '+258850222222',
            'reference_number' => 'EM-CUST-NON-EM-001',
            'payment_amount' => 250,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 250],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'EM-CUST-NON-EM-001',
        ]);
    }

    public function test_vendor_mobile_money_payment_blocks_when_electronic_money_daily_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $customerUser = $this->makeCounterpartyUser($company, 'client');
        $this->grantPermissions($company, ['create-vendor-payments']);

        $bankAccount = BankAccount::create([
            'account_number' => 'EM-VEND-DAILY-001',
            'account_name' => 'Conta Móvel Fornecedor Limite Diário',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'Vodacom M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 1000,
            'electronic_money_monthly_limit_mzn' => 10000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::create([
            'payment_number' => 'CP-EM-DAILY-BASE-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'mpesa',
            'mobile_money_number' => '+258840333333',
            'reference_number' => 'EM-DAILY-BASE-001',
            'payment_amount' => 800,
            'amount_mzn' => 800,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-EM-VEND-003',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 300,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 300,
            'paid_amount' => 0,
            'balance_amount' => 300,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))->actingAs($company)->post(route('account.vendor-payments.store'), [
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'mpesa',
            'mobile_money_number' => '+258840444444',
            'reference_number' => 'EM-VEND-DAILY-LIMIT-001',
            'payment_amount' => 300,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 300],
            ],
            'debit_notes' => [],
        ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'EM-VEND-DAILY-LIMIT-001',
        ]);
    }

    public function test_customer_mobile_money_payment_blocks_when_electronic_money_daily_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $customerUser = $this->makeCounterpartyUser($company, 'client');
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, ['create-customer-payments']);

        $bankAccount = BankAccount::create([
            'account_number' => 'EM-CUST-DAILY-001',
            'account_name' => 'Conta Móvel Cliente Limite Diário',
            'bank_name' => 'e-Mola',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'e-Mola',
            'electronic_money_level' => 'III',
            'electronic_money_daily_limit_mzn' => 1000,
            'electronic_money_monthly_limit_mzn' => 10000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::create([
            'payment_number' => 'VP-EM-DAILY-BASE-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'emola',
            'mobile_money_number' => '+258850333333',
            'reference_number' => 'EM-DAILY-BASE-002',
            'payment_amount' => 850,
            'amount_mzn' => 850,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-EM-CUST-003',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customerUser->id,
            'subtotal' => 200,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 200,
            'paid_amount' => 0,
            'balance_amount' => 200,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))->actingAs($company)->post(route('account.customer-payments.store'), [
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'mobile_money',
            'mobile_money_provider' => 'emola',
            'mobile_money_number' => '+258850444444',
            'reference_number' => 'EM-CUST-DAILY-LIMIT-001',
            'payment_amount' => 200,
            'currency_code' => 'MZN',
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 200],
            ],
            'credit_notes' => [],
        ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'EM-CUST-DAILY-LIMIT-001',
        ]);
    }

    public function test_fiscal_compliance_alerts_include_electronic_money_alerts(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $customerUser = $this->makeCounterpartyUser($company, 'client');

        $accountMissingClassification = BankAccount::query()->create([
            'account_number' => 'EM-001',
            'account_name' => 'Conta Móvel Sem Classificação',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => null,
            'electronic_money_level' => null,
            'electronic_money_monthly_limit_mzn' => 1000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $accountLimitExceeded = BankAccount::query()->create([
            'account_number' => 'EM-002',
            'account_name' => 'Conta Móvel Excedida',
            'bank_name' => 'M-Pesa',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'Vodacom M-Pesa',
            'electronic_money_level' => 'III',
            'electronic_money_monthly_limit_mzn' => 1000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $accountNearThreshold = BankAccount::query()->create([
            'account_number' => 'EM-003',
            'account_name' => 'Conta Móvel Próxima do Limite',
            'bank_name' => 'e-Mola',
            'branch_name' => 'Maputo',
            'account_type' => 'mobile_money',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_electronic_money_account' => true,
            'electronic_money_entity' => 'e-Mola',
            'electronic_money_level' => 'III',
            'electronic_money_monthly_limit_mzn' => 1000,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::query()->create([
            'payment_number' => 'VP-EM-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $accountLimitExceeded->id,
            'payment_method' => 'mobile_money',
            'reference_number' => 'EM-LIM-EXC',
            'payment_amount' => 1200,
            'amount_mzn' => 1200,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::query()->create([
            'payment_number' => 'CP-EM-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $accountNearThreshold->id,
            'payment_method' => 'mobile_money',
            'reference_number' => 'EM-LIM-NEAR',
            'payment_amount' => 900,
            'amount_mzn' => 900,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        // Keep one account with no usage to assert only classification alert is raised.
        $this->assertTrue($accountMissingClassification->exists);

        $report = app(ReportService::class)->getMozambiqueFiscalComplianceAlerts([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]);

        $alertsByCode = collect($report['alerts'] ?? [])->keyBy('code');

        $this->assertSame(
            1,
            (int) data_get($alertsByCode->get('electronic_money_accounts_missing_classification'), 'count', 0)
        );
        $this->assertSame(
            1,
            (int) data_get($alertsByCode->get('electronic_money_accounts_limit_exceeded'), 'count', 0)
        );
        $this->assertSame(
            1,
            (int) data_get($alertsByCode->get('electronic_money_accounts_limit_near_threshold'), 'count', 0)
        );
    }

    public function test_treasury_report_aggregates_liquidity_cash_flow_and_endpoint_response(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $bankAccount = BankAccount::query()->create([
            'account_number' => 'TR-BANK-001',
            'account_name' => 'Conta Bancária Principal',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 1500,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        BankAccount::query()->create([
            'account_number' => 'TR-CASH-001',
            'account_name' => 'Caixa Operacional',
            'bank_name' => 'Caixa',
            'branch_name' => 'Maputo',
            'account_type' => 'cashbox',
            'opening_balance' => 0,
            'current_balance' => 200,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $customer = $this->makeCounterpartyUser($company, 'client');
        $vendor = $this->makeCounterpartyUser($company, 'vendor');

        SalesInvoice::query()->create([
            'invoice_number' => 'TR-AR-001',
            'invoice_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(8)->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PurchaseInvoice::query()->create([
            'invoice_number' => 'TR-AP-001',
            'invoice_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
            'vendor_id' => $vendor->id,
            'subtotal' => 600,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 600,
            'paid_amount' => 0,
            'balance_amount' => 600,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::query()->create([
            'payment_number' => 'CP-TR-001',
            'payment_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TR-REC-001',
            'payment_amount' => 700,
            'amount_mzn' => 700,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::query()->create([
            'payment_number' => 'VP-TR-001',
            'payment_date' => now()->startOfMonth()->addDays(6)->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TR-PAY-001',
            'payment_amount' => 200,
            'amount_mzn' => 200,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $report = app(ReportService::class)->getMozambiqueTreasuryReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'as_of_date' => $toDate,
        ]);

        $this->assertSame(1500.0, (float) data_get($report, 'summary.bank_balance_mzn'));
        $this->assertSame(200.0, (float) data_get($report, 'summary.cash_balance_mzn'));
        $this->assertSame(1, (int) data_get($report, 'summary.cashbox_account_count'));
        $this->assertSame(200.0, (float) data_get($report, 'summary.cashbox_balance_mzn'));
        $this->assertSame(0, (int) data_get($report, 'summary.petty_cash_account_count'));
        $this->assertSame(1700.0, (float) data_get($report, 'summary.total_liquidity_mzn'));
        $this->assertSame('cashbox', (string) data_get($report, 'cash_accounts.0.account_type'));
        $this->assertSame(1000.0, (float) data_get($report, 'summary.accounts_receivable_open_mzn'));
        $this->assertSame(600.0, (float) data_get($report, 'summary.accounts_payable_open_mzn'));
        $this->assertSame(700.0, (float) data_get($report, 'summary.period_receipts_mzn'));
        $this->assertSame(200.0, (float) data_get($report, 'summary.period_payments_mzn'));
        $this->assertSame(1, (int) data_get($report, 'summary.overdue_receivables_count'));
        $this->assertSame(1, (int) data_get($report, 'summary.overdue_payables_count'));

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-treasury-report', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'as_of_date' => $toDate,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.total_liquidity_mzn', 1700);
        $response->assertJsonPath('summary.period_net_cash_flow_mzn', 500);
    }

    public function test_financial_compliance_dashboard_returns_active_indicators_and_endpoint_response(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Dashboard',
            'contact_person_name' => 'Compliance',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::query()->create([
            'payment_number' => 'VP-CFD-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $this->makeBankAccount($company)->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'GIFIM-CFD-001',
            'payment_amount' => 900000,
            'amount_mzn' => 900000,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $report = app(ReportService::class)->getMozambiqueFinancialComplianceDashboard([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'due_soon_days' => 7,
        ]);

        $this->assertGreaterThanOrEqual(1, (int) data_get($report, 'summary.active_indicators', 0));
        $this->assertGreaterThan(0, (int) data_get($report, 'summary.risk_score', 0));
        $this->assertTrue(
            collect((array) data_get($report, 'active_indicators', []))
                ->pluck('code')
                ->contains('gifim_pending_alerts')
        );

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-financial-compliance-dashboard', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'due_soon_days' => 7,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.active_indicators', (int) data_get($report, 'summary.active_indicators'));
        $response->assertJsonPath('summary.risk_level', (string) data_get($report, 'summary.risk_level'));
    }

    public function test_invoicing_report_summarizes_documents_exemptions_and_digital_operations(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $customerUser = $this->makeCounterpartyUser($company, 'client');
        \Workdo\Account\Models\Customer::query()->create([
            'user_id' => $customerUser->id,
            'company_name' => 'Cliente Invoicing Report',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@invoice.test',
            'customer_type' => 'private_company',
            'fiscal_residency_status' => 'resident',
            'fiscal_country' => 'Mozambique',
            'billing_currency_code' => 'USD',
            'operation_type' => 'domestic',
            'billing_address' => ['country' => 'Mozambique'],
            'shipping_address' => ['country' => 'Mozambique'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $service = ProductServiceItem::create([
            'name' => 'Serviço Digital Teste',
            'sku' => 'SRV-DIG-001',
            'sale_price' => 1000,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoiceA = SalesInvoice::query()->create([
            'invoice_number' => 'INV-RPT-001',
            'document_type' => 'FT',
            'document_series' => '2026-A',
            'invoice_date' => now()->startOfMonth()->addDays(1)->toDateString(),
            'operation_date' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(15)->toDateString(),
            'customer_id' => $customerUser->id,
            'subtotal' => 500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'balance_amount' => 500,
            'status' => 'posted',
            'type' => 'service',
            'fiscal_submission_status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoiceItem::query()->create([
            'invoice_id' => $invoiceA->id,
            'product_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 500,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'isento',
            'tax_exemption_reason' => '',
            'tax_amount' => 0,
            'total_amount' => 500,
        ]);

        $invoiceB = SalesInvoice::query()->create([
            'invoice_number' => 'INV-RPT-002',
            'document_type' => 'FT',
            'document_series' => '2026-A',
            'invoice_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'operation_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(16)->toDateString(),
            'customer_id' => $customerUser->id,
            'subtotal' => 1000,
            'tax_amount' => 160,
            'discount_amount' => 0,
            'total_amount' => 1160,
            'paid_amount' => 0,
            'balance_amount' => 1160,
            'status' => 'posted',
            'type' => 'service',
            'fiscal_submission_status' => 'submitted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoiceItem::query()->create([
            'invoice_id' => $invoiceB->id,
            'product_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 16,
            'vat_code' => 'digital_services',
            'tax_exemption_reason' => null,
            'tax_amount' => 160,
            'total_amount' => 1160,
        ]);

        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $report = app(ReportService::class)->getMozambiqueInvoicingReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $this->assertSame(2, (int) data_get($report, 'summary.total_documents'));
        $this->assertSame(1660.0, (float) data_get($report, 'summary.total_amount'));
        $this->assertSame(160.0, (float) data_get($report, 'summary.total_tax_amount'));
        $this->assertSame(500.0, (float) data_get($report, 'summary.total_exempt_amount'));
        $this->assertSame(1, (int) data_get($report, 'summary.digital_operations_count'));
        $this->assertSame(2, (int) data_get($report, 'by_status.posted'));
        $this->assertSame(2, (int) data_get($report, 'by_document_type.FT'));
        $this->assertCount(1, (array) data_get($report, 'missing_exemption_reason', []));

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-invoicing-report', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.total_documents', 2);
        $response->assertJsonPath('summary.digital_operations_count', 1);
    }

    public function test_currency_report_summarizes_repatriation_and_fx_differences(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        \Workdo\Account\Models\Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Cambial',
            'contact_person_name' => 'Tesouraria',
            'tax_number' => '500123456',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'Portugal',
            'withholding_tax_applicable' => true,
            'billing_address' => ['country' => 'Portugal'],
            'shipping_address' => ['country' => 'Portugal'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $customerUser = $this->makeCounterpartyUser($company, 'client');
        \Workdo\Account\Models\Customer::query()->create([
            'user_id' => $customerUser->id,
            'company_name' => 'Cliente Exportação',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@exportacao.test',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'South Africa',
            'billing_address' => ['country' => 'South Africa'],
            'shipping_address' => ['country' => 'South Africa'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankAccount = $this->makeBankAccount($company);

        VendorPayment::query()->create([
            'payment_number' => 'VP-CAMB-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendorUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CAMB-OUT-001',
            'payment_amount' => 2000,
            'currency_code' => 'EUR',
            'exchange_rate' => 70,
            'foreign_amount' => 28.57,
            'amount_mzn' => 2000,
            'fx_difference_amount' => 50,
            'is_international_payment' => true,
            'beneficiary_country' => 'Portugal',
            'service_type' => 'consulting',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::query()->create([
            'payment_number' => 'CP-CAMB-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customerUser->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CAMB-IN-001',
            'payment_amount' => 1000,
            'currency_code' => 'USD',
            'exchange_rate' => 63.5,
            'foreign_amount' => 15.75,
            'amount_mzn' => 1000,
            'fx_difference_amount' => 20,
            'is_export_receipt' => true,
            'receipt_origin_country' => 'South Africa',
            'export_reference' => 'EXP-CAMB-001',
            'intermediary_bank' => 'Banco Intermediário',
            'repatriation_status' => 'partial',
            'repatriated_amount_mzn' => 400,
            'fx_compliance_reference' => 'FX-COMP-001',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $report = app(ReportService::class)->getMozambiqueCurrencyReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $this->assertSame(2, (int) data_get($report, 'summary.total_operations'));
        $this->assertSame(2, (int) data_get($report, 'summary.foreign_currency_operations_count'));
        $this->assertSame(1, (int) data_get($report, 'summary.export_receipts_count'));
        $this->assertSame(1000.0, (float) data_get($report, 'summary.export_receipts_amount_mzn'));
        $this->assertSame(400.0, (float) data_get($report, 'summary.repatriated_amount_mzn'));
        $this->assertSame(600.0, (float) data_get($report, 'summary.pending_repatriation_amount_mzn'));
        $this->assertSame(70.0, (float) data_get($report, 'summary.total_fx_difference_amount_mzn'));

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-currency-report', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.total_operations', 2);
        $response->assertJsonPath('summary.total_fx_difference_amount_mzn', 70);
    }

    public function test_vendor_payment_requires_approval_before_clearing(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['approve-vendor-payments', 'cleared-vendor-payments']);

        $payment = VendorPayment::query()->create([
            'payment_number' => 'VP-APP-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'APP-VP-001',
            'payment_amount' => 0,
            'status' => 'pending',
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_risk_flags' => ['international_payment'],
            'approval_requested_at' => now(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))
            ->actingAs($company)
            ->post(route('account.vendor-payments.update-status', $payment), [
                'status' => 'cleared',
            ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('vendor_payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_vendor_payment_can_be_approved_and_then_cleared(): void
    {
        $company = $this->makeCompany();
        $vendor = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['approve-vendor-payments', 'cleared-vendor-payments']);

        $payment = VendorPayment::query()->create([
            'payment_number' => 'VP-APP-002',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'APP-VP-002',
            'payment_amount' => 0,
            'status' => 'pending',
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_risk_flags' => ['international_payment'],
            'approval_requested_at' => now(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $approveResponse = $this->actingAs($company)->post(route('account.vendor-payments.approve', $payment), [
            'approval_reference' => 'FIN-APP-WF-2026-001',
        ]);
        $approveResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendor_payments', [
            'id' => $payment->id,
            'approval_status' => 'approved',
            'approval_reference' => 'FIN-APP-WF-2026-001',
        ]);

        $clearResponse = $this->actingAs($company)->post(route('account.vendor-payments.update-status', $payment), [
            'status' => 'cleared',
        ]);
        $clearResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendor_payments', [
            'id' => $payment->id,
            'status' => 'cleared',
        ]);
    }

    public function test_customer_payment_requires_approval_before_clearing(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['approve-customer-payments', 'cleared-customer-payments']);

        $payment = CustomerPayment::query()->create([
            'payment_number' => 'CP-APP-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'APP-CP-001',
            'payment_amount' => 0,
            'status' => 'pending',
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_risk_flags' => ['gifim_threshold'],
            'approval_requested_at' => now(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))
            ->actingAs($company)
            ->patch(route('account.customer-payments.update-status', $payment), [
                'status' => 'cleared',
            ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_payment_can_be_approved_and_then_cleared(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['approve-customer-payments', 'cleared-customer-payments']);

        $payment = CustomerPayment::query()->create([
            'payment_number' => 'CP-APP-002',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'APP-CP-002',
            'payment_amount' => 0,
            'status' => 'pending',
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_risk_flags' => ['foreign_currency'],
            'approval_requested_at' => now(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $approveResponse = $this->actingAs($company)->patch(route('account.customer-payments.approve', $payment), [
            'approval_reference' => 'FIN-APP-WF-2026-002',
        ]);
        $approveResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'approval_status' => 'approved',
            'approval_reference' => 'FIN-APP-WF-2026-002',
        ]);

        $clearResponse = $this->actingAs($company)->patch(route('account.customer-payments.update-status', $payment), [
            'status' => 'cleared',
        ]);
        $clearResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'status' => 'cleared',
        ]);
    }

    public function test_customer_payment_blocks_foreign_currency_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);
        $this->provisionPermission('create-foreign-currency-customer-payments');

        Customer::create([
            'user_id' => $customer->id,
            'company_name' => 'Cliente Internacional Teste',
            'contact_person_name' => 'Gestor Tesouraria',
            'contact_person_email' => 'gestor.tesouraria@example.test',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'South Africa',
            'billing_address' => ['country' => 'South Africa'],
            'shipping_address' => ['country' => 'South Africa'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-FX-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))
            ->actingAs($company)
            ->post(route('account.customer-payments.store'), [
                'payment_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'FX-PERM-001',
                'payment_amount' => 1000,
                'currency_code' => 'USD',
                'exchange_rate' => 63.5,
                'foreign_amount' => 15,
                'notes' => 'Pagamento em moeda estrangeira para teste de permissao.',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'credit_notes' => [],
            ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['currency_code']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'FX-PERM-001',
        ]);
    }

    public function test_customer_payment_blocks_high_value_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCounterpartyUser($company, 'client');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-customer-payments']);
        $this->provisionPermission('create-high-value-customer-payments');

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-HIGH-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 300000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 300000,
            'paid_amount' => 0,
            'balance_amount' => 300000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))
            ->actingAs($company)
            ->post(route('account.customer-payments.store'), [
                'payment_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'cash',
                'reference_number' => 'HIGH-PERM-001',
                'payment_amount' => 300000,
                'currency_code' => 'MZN',
                'high_value_approval_reference' => 'FIN-APP-TEST-001',
                'gifim_alert_status' => 'pending',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 300000],
                ],
                'credit_notes' => [],
            ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'HIGH-PERM-001',
        ]);
    }

    public function test_customer_payment_blocks_other_users_bank_account_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $staffUser = $this->makeCounterpartyUser($company, 'staff');
        $customer = $this->makeCounterpartyUser($company, 'client');
        $this->grantPermissions($company, ['create-customer-payments']);
        $this->provisionPermission('use-all-bank-accounts-for-customer-payments');

        $bankAccount = BankAccount::create([
            'account_number' => 'SHARED-001',
            'account_name' => 'Conta do Staff',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $staffUser->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-BANK-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.customer-payments.index'))
            ->actingAs($company)
            ->post(route('account.customer-payments.store'), [
                'payment_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'BANK-PERM-001',
                'payment_amount' => 1000,
                'currency_code' => 'MZN',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'credit_notes' => [],
            ]);

        $response->assertRedirect(route('account.customer-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('customer_payments', [
            'reference_number' => 'BANK-PERM-001',
        ]);
    }

    public function test_customer_payment_allows_other_users_bank_account_when_explicit_permission_is_granted(): void
    {
        $company = $this->makeCompany();
        $staffUser = $this->makeCounterpartyUser($company, 'staff');
        $customer = $this->makeCounterpartyUser($company, 'client');
        $this->grantPermissions($company, [
            'create-customer-payments',
            'use-all-bank-accounts-for-customer-payments',
        ]);

        $bankAccount = BankAccount::create([
            'account_number' => 'SHARED-002',
            'account_name' => 'Conta Partilhada',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $staffUser->id,
            'created_by' => $company->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-BANK-PERM-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)
            ->post(route('account.customer-payments.store'), [
                'payment_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'BANK-PERM-002',
                'payment_amount' => 1000,
                'currency_code' => 'MZN',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'credit_notes' => [],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_payments', [
            'reference_number' => 'BANK-PERM-002',
            'bank_account_id' => $bankAccount->id,
        ]);
    }

    public function test_vendor_payment_blocks_foreign_currency_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->provisionPermission('create-foreign-currency-vendor-payments');
        $this->seedWithholdingRule(
            code: 'IRPC-SRV-NR-PERM-001',
            incomeType: 'services',
            appliesTo: 'non_resident',
            rate: 20
        );

        Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Internacional Permissao',
            'contact_person_name' => 'Gestor Fiscal',
            'contact_person_email' => 'fiscal.vendor@example.test',
            'fiscal_residency_status' => 'non_resident',
            'fiscal_country' => 'South Africa',
            'withholding_tax_applicable' => true,
            'billing_address' => ['country' => 'South Africa'],
            'shipping_address' => ['country' => 'South Africa'],
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-FX-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))
            ->actingAs($company)
            ->post(route('account.vendor-payments.store'), [
                'payment_date' => now()->toDateString(),
                'vendor_id' => $vendorUser->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'FX-VEND-PERM-001',
                'payment_amount' => 1000,
                'currency_code' => 'USD',
                'exchange_rate' => 63.5,
                'foreign_amount' => 15,
                'is_international_payment' => true,
                'beneficiary_country' => 'South Africa',
                'service_type' => 'consulting',
                'withholding_tax_treatment' => 'withheld',
                'withholding_tax_rate' => 20,
                'withholding_tax_amount' => 200,
                'fiscal_compliance_reference' => 'WHT-PERM-001',
                'financial_approval_reference' => 'FIN-PERM-001',
                'fx_authorization_reference' => 'FX-PERM-001',
                'notes' => 'Teste de permissao de moeda estrangeira.',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'debit_notes' => [],
            ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['currency_code']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'FX-VEND-PERM-001',
        ]);
    }

    public function test_vendor_payment_blocks_high_value_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $bankAccount = $this->makeBankAccount($company);
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->provisionPermission('create-high-value-vendor-payments');

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-HIGH-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 300000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 300000,
            'paid_amount' => 0,
            'balance_amount' => 300000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))
            ->actingAs($company)
            ->post(route('account.vendor-payments.store'), [
                'payment_date' => now()->toDateString(),
                'vendor_id' => $vendorUser->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'cash',
                'reference_number' => 'HIGH-VEND-PERM-001',
                'payment_amount' => 300000,
                'currency_code' => 'MZN',
                'high_value_approval_reference' => 'FIN-APP-VEND-001',
                'gifim_alert_status' => 'pending',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 300000],
                ],
                'debit_notes' => [],
            ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['payment_amount']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'HIGH-VEND-PERM-001',
        ]);
    }

    public function test_vendor_payment_blocks_other_users_bank_account_without_explicit_permission_when_permission_is_provisioned(): void
    {
        $company = $this->makeCompany();
        $staffUser = $this->makeCounterpartyUser($company, 'staff');
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, ['create-vendor-payments']);
        $this->provisionPermission('use-all-bank-accounts-for-vendor-payments');

        $bankAccount = BankAccount::create([
            'account_number' => 'SHARED-VEND-001',
            'account_name' => 'Conta do Staff',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $staffUser->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-BANK-PERM-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->from(route('account.vendor-payments.index'))
            ->actingAs($company)
            ->post(route('account.vendor-payments.store'), [
                'payment_date' => now()->toDateString(),
                'vendor_id' => $vendorUser->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'BANK-VEND-PERM-001',
                'payment_amount' => 1000,
                'currency_code' => 'MZN',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'debit_notes' => [],
            ]);

        $response->assertRedirect(route('account.vendor-payments.index'));
        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertDatabaseMissing('vendor_payments', [
            'reference_number' => 'BANK-VEND-PERM-001',
        ]);
    }

    public function test_vendor_payment_allows_other_users_bank_account_when_explicit_permission_is_granted(): void
    {
        $company = $this->makeCompany();
        $staffUser = $this->makeCounterpartyUser($company, 'staff');
        $vendorUser = $this->makeCounterpartyUser($company, 'vendor');
        $this->grantPermissions($company, [
            'create-vendor-payments',
            'use-all-bank-accounts-for-vendor-payments',
        ]);

        $bankAccount = BankAccount::create([
            'account_number' => 'SHARED-VEND-002',
            'account_name' => 'Conta Partilhada',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $staffUser->id,
            'created_by' => $company->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'FR-BANK-PERM-002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'vendor_id' => $vendorUser->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)
            ->post(route('account.vendor-payments.store'), [
                'payment_date' => now()->toDateString(),
                'vendor_id' => $vendorUser->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'BANK-VEND-PERM-002',
                'payment_amount' => 1000,
                'currency_code' => 'MZN',
                'allocations' => [
                    ['invoice_id' => $invoice->id, 'amount' => 1000],
                ],
                'debit_notes' => [],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_payments', [
            'reference_number' => 'BANK-VEND-PERM-002',
            'bank_account_id' => $bankAccount->id,
        ]);
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
        return BankAccount::create([
            'account_number' => 'FX-001',
            'account_name' => 'Conta Operacional',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Maputo',
            'account_type' => $accountType,
            'opening_balance' => 0,
            'current_balance' => 0,
            'iban' => 'MZ59000100000000000000123',
            'swift_code' => 'BCDMMZMA',
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

    private function provisionPermission(string $permissionName): void
    {
        Permission::firstOrCreate(
            ['name' => $permissionName, 'guard_name' => 'web'],
            [
                'add_on' => 'general',
                'module' => 'tests',
                'label' => $permissionName,
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedWithholdingRule(string $code, string $incomeType, string $appliesTo, float $rate): WithholdingTaxRule
    {
        return WithholdingTaxRule::query()->create([
            'code' => $code,
            'name' => "Rule {$code}",
            'income_type' => $incomeType,
            'rate' => $rate,
            'applies_to' => $appliesTo,
            'is_final_tax' => false,
            'legal_basis' => 'Test basis',
            'pgc_debit_account' => '622',
            'pgc_credit_account' => '245',
            'is_active' => true,
        ]);
    }

    private function seedTreatyRate(
        User $company,
        string $countryCode,
        string $countryName,
        string $incomeType,
        float $treatyRate,
        ?float $standardRate = null,
        ?string $code = null,
    ): WithholdingTaxTreatyRate {
        return WithholdingTaxTreatyRate::query()->create([
            'code' => $code,
            'country_code' => strtoupper($countryCode),
            'country_name' => $countryName,
            'income_type' => $incomeType,
            'standard_rate' => $standardRate,
            'treaty_rate' => $treatyRate,
            'requires_residency_certificate' => true,
            'legal_basis' => 'ADT configured for tests',
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => now()->addYear()->toDateString(),
            'is_active' => true,
            'created_by' => $company->id,
        ]);
    }
}
