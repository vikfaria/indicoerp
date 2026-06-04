<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AuditTrail;
use App\Models\FiscalExportHistory;
use App\Models\MzVatCode;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\VendorPayment;
use Workdo\Pos\Models\Pos;
use Workdo\Pos\Models\PosItem;
use Workdo\ProductService\Models\ProductServiceItem;

class MozambiqueAccountingFiscalMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_mozambique_fiscal_map_endpoint_returns_aggregated_values(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);

        SalesInvoice::create([
            'invoice_number' => 'FT-TEST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 160,
            'discount_amount' => 0,
            'total_amount' => 1160,
            'paid_amount' => 0,
            'balance_amount' => 1160,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-TEST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'vendor_id' => $vendor->id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'discount_amount' => 0,
            'total_amount' => 580,
            'paid_amount' => 0,
            'balance_amount' => 580,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'validated',
        ]);

        CreditNote::create([
            'credit_note_number' => 'NC-TEST-001',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'reason' => 'Ajuste teste',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'applied_amount' => 0,
            'balance_amount' => 116,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        DebitNote::create([
            'debit_note_number' => 'ND-TEST-001',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'reason' => 'Ajuste teste',
            'status' => 'approved',
            'subtotal' => 50,
            'tax_amount' => 8,
            'discount_amount' => 0,
            'total_amount' => 58,
            'applied_amount' => 0,
            'balance_amount' => 58,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-map'));

        $response->assertOk();
        $response->assertHeader('X-SCE-Canonical-Route');
        $response->assertJsonPath('sales.documents', 1);
        $response->assertJsonPath('purchases.documents', 1);
        $response->assertJsonPath('vat.output_vat', 144);
        $response->assertJsonPath('vat.input_vat', 72);
        $response->assertJsonPath('vat.net_vat_payable', 72);
        $response->assertJsonPath('fiscal_status.sales.submitted', 1);
        $response->assertJsonPath('fiscal_status.purchases.validated', 1);
    }

    public function test_mozambique_fiscal_map_export_returns_csv(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-map.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('X-SCE-Canonical-Route');
        $response->assertSee('net_vat_payable', false);
        $response->assertSee('section', false);
    }

    public function test_mozambique_fiscal_map_includes_pos_sales_and_vat(): void
    {
        if (!Schema::hasTable('pos') || !Schema::hasTable('pos_items')) {
            $this->markTestSkipped('POS tables are not available in this test environment.');
        }

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->createPosSale($company, [
            'pos_date' => '2026-01-09',
            'status' => 'completed',
            'fiscal_submission_status' => 'submitted',
            'subtotal' => 1000,
            'tax_amount' => 160,
            'total_amount' => 1160,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-map', [
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertJsonPath('pos_sales.documents', 1);
        $response->assertJsonPath('pos_sales.taxable_base', 1000);
        $response->assertJsonPath('pos_sales.tax_amount', 160);
        $response->assertJsonPath('vat.output_vat', 160);
        $response->assertJsonPath('vat.net_vat_payable', 160);

        if (Schema::hasColumn('pos', 'fiscal_submission_status')) {
            $response->assertJsonPath('fiscal_status.pos.submitted', 1);
        }
    }

    public function test_mozambique_vat_declaration_endpoint_returns_monthly_values(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);

        SalesInvoice::create([
            'invoice_number' => 'FT-DECL-001',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-01-20',
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'tax_amount' => 160,
            'discount_amount' => 0,
            'total_amount' => 1160,
            'paid_amount' => 0,
            'balance_amount' => 1160,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-DECL-001',
            'invoice_date' => '2026-01-18',
            'due_date' => '2026-01-22',
            'vendor_id' => $vendor->id,
            'subtotal' => 400,
            'tax_amount' => 64,
            'discount_amount' => 0,
            'total_amount' => 464,
            'paid_amount' => 0,
            'balance_amount' => 464,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CreditNote::create([
            'credit_note_number' => 'NC-DECL-001',
            'credit_note_date' => '2026-02-10',
            'customer_id' => $customer->id,
            'reason' => 'ajuste',
            'status' => 'approved',
            'subtotal' => 50,
            'tax_amount' => 8,
            'discount_amount' => 0,
            'total_amount' => 58,
            'applied_amount' => 0,
            'balance_amount' => 58,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        DebitNote::create([
            'debit_note_number' => 'ND-DECL-001',
            'debit_note_date' => '2026-02-12',
            'vendor_id' => $vendor->id,
            'reason' => 'ajuste',
            'status' => 'approved',
            'subtotal' => 30,
            'tax_amount' => 4.8,
            'discount_amount' => 0,
            'total_amount' => 34.8,
            'applied_amount' => 0,
            'balance_amount' => 34.8,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-vat-declaration', [
            'from_date' => '2026-01-01',
            'to_date' => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertHeader('X-SCE-Canonical-Route');
        $response->assertJsonPath('monthly.0.period', '2026-01');
        $response->assertJsonPath('monthly.0.output_vat', 160);
        $response->assertJsonPath('monthly.0.input_vat', 64);
        $response->assertJsonPath('monthly.0.net_vat_payable', 96);
        $response->assertJsonPath('monthly.1.period', '2026-02');
        $response->assertJsonPath('monthly.1.output_vat', 0);
        $response->assertJsonPath('monthly.1.input_vat', 0);
        $response->assertJsonPath('totals.sales_vat', 160);
        $response->assertJsonPath('totals.purchase_vat', 64);
        $response->assertJsonPath('totals.credit_notes_vat', 8);
        $response->assertJsonPath('totals.debit_notes_vat', 4.8);
    }

    public function test_mozambique_vat_declaration_export_returns_csv(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-vat-declaration.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('X-SCE-Canonical-Route');
        $response->assertSee('net_vat_payable', false);
        $response->assertSee('monthly', false);
    }

    public function test_mozambique_vat_declaration_includes_pos_vat_totals(): void
    {
        if (!Schema::hasTable('pos') || !Schema::hasTable('pos_items')) {
            $this->markTestSkipped('POS tables are not available in this test environment.');
        }

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->createPosSale($company, [
            'pos_date' => '2026-02-10',
            'status' => 'completed',
            'subtotal' => 500,
            'tax_amount' => 80,
            'total_amount' => 580,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-vat-declaration', [
            'from_date' => '2026-02-01',
            'to_date' => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertJsonPath('monthly.0.period', '2026-02');
        $response->assertJsonPath('monthly.0.pos_vat', 80);
        $response->assertJsonPath('monthly.0.output_vat', 80);
        $response->assertJsonPath('totals.pos_vat', 80);
        $response->assertJsonPath('totals.output_vat', 80);
        $response->assertJsonPath('totals.net_vat_payable', 80);
    }

    public function test_mozambique_fiscal_submission_register_endpoint_returns_grouped_rows(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);

        SalesInvoice::create([
            'invoice_number' => 'FT-SUB-001',
            'invoice_date' => '2026-03-05',
            'due_date' => '2026-03-08',
            'customer_id' => $customer->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-SUB-001',
            'invoice_date' => '2026-03-10',
            'due_date' => '2026-03-14',
            'vendor_id' => $vendor->id,
            'subtotal' => 80,
            'tax_amount' => 12.8,
            'discount_amount' => 0,
            'total_amount' => 92.8,
            'paid_amount' => 0,
            'balance_amount' => 92.8,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-submission-register', [
            'from_date' => '2026-03-01',
            'to_date' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary_by_status.pending', 1);
        $response->assertJsonPath('summary_by_status.submitted', 1);
        $response->assertJsonFragment([
            'period' => '2026-03',
            'document_group' => 'sales_invoices',
            'fiscal_status' => 'submitted',
            'total' => 1,
        ]);
        $response->assertJsonFragment([
            'period' => '2026-03',
            'document_group' => 'purchase_invoices',
            'fiscal_status' => 'pending',
            'total' => 1,
        ]);
    }

    public function test_mozambique_fiscal_submission_register_export_returns_csv(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-submission-register.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('document_group', false);
        $response->assertSee('fiscal_status', false);

        $this->assertDatabaseHas('fiscal_export_histories', [
            'company_id' => $company->id,
            'export_type' => 'fiscal_submission_register_csv',
            'status' => 'generated',
        ]);

        $history = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'fiscal_submission_register_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);

        $auditEntry = AuditTrail::query()
            ->where('auditable_type', FiscalExportHistory::class)
            ->where('auditable_id', $history->id)
            ->where('event', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame('account.reports.mozambique-fiscal-submission-register.export', $auditEntry->route);
        $this->assertSame('fiscal_submission_register_csv', (string) data_get($auditEntry->changes, 'export_type'));
    }

    public function test_mozambique_fiscal_exports_history_endpoint_scopes_company_rows(): void
    {
        $company = $this->makeCompany();
        $otherCompany = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        FiscalExportHistory::create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'generated_by' => $company->id,
            'file_name' => 'company-saft.xml',
            'file_hash' => str_repeat('a', 64),
            'status' => 'generated',
        ]);

        FiscalExportHistory::create([
            'company_id' => $otherCompany->id,
            'export_type' => 'saft_xml',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'generated_by' => $otherCompany->id,
            'file_name' => 'other-saft.xml',
            'file_hash' => str_repeat('b', 64),
            'status' => 'generated',
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-exports-history', [
            'from_date' => '2026-03-01',
            'to_date' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.file_name', 'company-saft.xml');
        $response->assertJsonPath('summary_by_type.saft_xml', 1);
    }

    public function test_mozambique_fiscal_exports_submission_confirmation_updates_history(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $history = FiscalExportHistory::create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'generated_by' => $company->id,
            'file_name' => 'company-saft.xml',
            'file_hash' => str_repeat('c', 64),
            'status' => 'generated',
        ]);

        $response = $this->actingAs($company)->post(
            route('account.reports.mozambique-fiscal-exports-history.submit', $history->id),
            [
                'submission_channel' => 'manual_upload',
                'submission_reference' => 'AT-REF-2026-0001',
                'status' => 'submitted',
                'submitted_at' => '2026-04-10 09:30:00',
                'notes' => 'Comprovativo anexado no portal.',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'submitted');
        $response->assertJsonPath('data.submission_channel', 'manual_upload');
        $response->assertJsonPath('data.submission_reference', 'AT-REF-2026-0001');

        $history->refresh();
        $this->assertSame('submitted', $history->status);
        $this->assertSame('manual_upload', $history->submission_channel);
        $this->assertSame('AT-REF-2026-0001', $history->submission_reference);
        $this->assertSame('Comprovativo anexado no portal.', $history->metadata['submission_notes'] ?? null);

        $auditEntry = AuditTrail::query()
            ->where('auditable_type', FiscalExportHistory::class)
            ->where('auditable_id', $history->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame('account.reports.mozambique-fiscal-exports-history.submit', $auditEntry->route);
        $this->assertSame('submitted', (string) data_get($auditEntry->changes, 'status'));
        $this->assertSame('manual_upload', (string) data_get($auditEntry->changes, 'submission_channel'));
    }

    public function test_mozambique_fiscal_submission_register_includes_pos_source(): void
    {
        if (
            !Schema::hasTable('pos')
            || !Schema::hasTable('pos_items')
            || !Schema::hasColumn('pos', 'fiscal_submission_status')
        ) {
            $this->markTestSkipped('POS fiscal submission columns are not available in this test environment.');
        }

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->createPosSale($company, [
            'pos_date' => '2026-03-15',
            'status' => 'completed',
            'fiscal_submission_status' => 'validated',
            'subtotal' => 250,
            'tax_amount' => 40,
            'total_amount' => 290,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-submission-register', [
            'from_date' => '2026-03-01',
            'to_date' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary_by_status.validated', 1);
        $response->assertJsonFragment([
            'period' => '2026-03',
            'document_group' => 'pos_sales',
            'fiscal_status' => 'validated',
            'total' => 1,
        ]);
    }

    public function test_mozambique_fiscal_compliance_alerts_detect_invoicing_risks(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $customer = $this->makeClient($company);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-ALRT-001',
            'invoice_date' => '2026-04-10',
            'operation_date' => '2026-04-01',
            'fiscal_issue_deadline' => '2026-04-08',
            'issued_with_delay' => true,
            'late_issue_reason' => 'Falha operacional no fecho do dia.',
            'due_date' => '2026-04-15',
            'customer_id' => $customer->id,
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
            'counterparty_snapshot' => ['name' => 'Cliente sem NUIT'],
            'fiscal_submission_status' => 'submitted',
        ]);

        $product = ProductServiceItem::create([
            'name' => 'Servico isento',
            'sale_price' => 100,
            'purchase_price' => 100,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'ISE',
            'tax_exemption_reason' => null,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-compliance-alerts', [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-30',
            'refresh' => 1,
        ]));

        $response->assertOk();
        $alerts = $response->json('alerts');
        $this->assertIsArray($alerts);

        $lateAlert = $this->findAlertByCode($alerts, 'invoice_issued_with_delay');
        $this->assertNotNull($lateAlert);
        $this->assertSame(1, (int) $lateAlert['count']);

        $nuitAlert = $this->findAlertByCode($alerts, 'documents_without_valid_nuit');
        $this->assertNotNull($nuitAlert);
        $this->assertSame(1, (int) $nuitAlert['count']);

        $exemptionAlert = $this->findAlertByCode($alerts, 'documents_without_exemption_reason');
        $this->assertNotNull($exemptionAlert);
        $this->assertSame(1, (int) $exemptionAlert['count']);
    }

    public function test_mozambique_fiscal_compliance_alerts_detect_incomplete_counterparty_classification(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customerUser = $this->makeClient($company);
        Customer::query()->create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-CLASS-001',
            'company_name' => 'Cliente Classificacao Incompleta',
            'contact_person_name' => 'Cliente Teste',
            'contact_person_email' => 'cliente.classificacao@example.com',
            'contact_person_mobile' => '+258840000001',
            'tax_number' => '123456789',
            'fiscal_residency_status' => 'resident',
            'customer_type' => null,
            'fiscal_country' => 'Mozambique',
            'vat_regime' => 'standard',
            'operation_type' => null,
            'billing_currency_code' => 'MZN',
            'accounting_account_code' => '3.1.01',
            'payment_terms' => '30 days',
            'same_as_billing' => true,
            'billing_address' => [
                'name' => 'Cliente Classificacao Incompleta',
                'address_line_1' => 'Av. Teste 10',
                'address_line_2' => null,
                'city' => 'Maputo',
                'state' => 'Maputo',
                'country' => 'Mozambique',
                'zip_code' => '1100',
            ],
            'shipping_address' => [
                'name' => 'Cliente Classificacao Incompleta',
                'address_line_1' => 'Av. Teste 10',
                'address_line_2' => null,
                'city' => 'Maputo',
                'state' => 'Maputo',
                'country' => 'Mozambique',
                'zip_code' => '1100',
            ],
            'notes' => 'Fixture de alerta de classificacao',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $vendorUser = $this->makeVendor($company);
        Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'vendor_code' => 'VEN-CLASS-001',
            'company_name' => 'Fornecedor Classificacao Incompleta',
            'contact_person_name' => 'Fornecedor Teste',
            'contact_person_email' => 'fornecedor.classificacao@example.com',
            'contact_person_mobile' => '+258840000002',
            'tax_number' => '987654321',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => null,
            'fiscal_country' => 'Mozambique',
            'vat_regime' => 'standard',
            'supply_type' => null,
            'payment_currency_code' => 'MZN',
            'foreign_tax_number' => null,
            'withholding_tax_applicable' => false,
            'reverse_charge_applicable' => false,
            'adt_eligible' => false,
            'adt_country' => null,
            'compliance_documents' => [],
            'payment_terms' => '30 days',
            'billing_address' => [
                'name' => 'Fornecedor Classificacao Incompleta',
                'address_line_1' => 'Rua Teste 20',
                'address_line_2' => null,
                'city' => 'Maputo',
                'state' => 'Maputo',
                'country' => 'Mozambique',
                'zip_code' => '1100',
            ],
            'shipping_address' => [
                'name' => 'Fornecedor Classificacao Incompleta',
                'address_line_1' => 'Rua Teste 20',
                'address_line_2' => null,
                'city' => 'Maputo',
                'state' => 'Maputo',
                'country' => 'Mozambique',
                'zip_code' => '1100',
            ],
            'same_as_billing' => true,
            'notes' => 'Fixture de alerta de classificacao',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-CLASS-001',
            'invoice_date' => '2026-06-10',
            'due_date' => '2026-06-15',
            'customer_id' => $customerUser->id,
            'subtotal' => 250,
            'tax_amount' => 40,
            'discount_amount' => 0,
            'total_amount' => 290,
            'paid_amount' => 0,
            'balance_amount' => 290,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-CLASS-001',
            'invoice_date' => '2026-06-11',
            'due_date' => '2026-06-16',
            'vendor_id' => $vendorUser->id,
            'subtotal' => 180,
            'tax_amount' => 28.8,
            'discount_amount' => 0,
            'total_amount' => 208.8,
            'paid_amount' => 0,
            'balance_amount' => 208.8,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'validated',
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-compliance-alerts', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'refresh' => 1,
        ]));

        $response->assertOk();
        $alerts = $response->json('alerts');
        $this->assertIsArray($alerts);

        $classificationAlert = $this->findAlertByCode($alerts, 'counterparties_with_incomplete_fiscal_classification');
        $this->assertNotNull($classificationAlert);
        $this->assertSame(2, (int) $classificationAlert['count']);
    }

    public function test_mozambique_fiscal_compliance_alerts_detect_submission_backlog_and_saft_pending(): void
    {
        if (!Schema::hasTable('fiscal_calendar_events')) {
            $this->markTestSkipped('Fiscal calendar table is not available in this environment.');
        }

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $customer = $this->makeClient($company);

        SalesInvoice::create([
            'invoice_number' => 'FT-BKLG-001',
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'customer_id' => $customer->id,
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
            'fiscal_submission_status' => 'pending',
        ]);

        FiscalExportHistory::create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'generated_by' => $company->id,
            'file_name' => 'saft-maio.xml',
            'file_hash' => str_repeat('d', 64),
            'status' => 'generated',
            'submitted_at' => null,
        ]);

        DB::table('fiscal_calendar_events')->insert([
            'company_id' => $company->id,
            'code' => 'VAT-2026-05',
            'title' => 'Declaração IVA Maio',
            'description' => 'Teste automático',
            'obligation_type' => 'vat',
            'due_date' => now()->subDay()->toDateString(),
            'reference_period' => '2026-05',
            'status' => 'pending',
            'completed_date' => null,
            'completed_by' => null,
            'notes' => null,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiscal_calendar_events')->insert([
            'company_id' => $company->id,
            'code' => 'IRPC-2026-05',
            'title' => 'Pagamento por Conta IRPC Maio',
            'description' => 'Teste automático',
            'obligation_type' => 'irpc',
            'due_date' => now()->subDay()->toDateString(),
            'reference_period' => '2026-05',
            'status' => 'pending',
            'completed_date' => null,
            'completed_by' => null,
            'notes' => null,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiscal_calendar_events')->insert([
            'company_id' => $company->id,
            'code' => 'CONTAS-2026',
            'title' => 'Aprovação Contas Anuais',
            'description' => 'Teste automático',
            'obligation_type' => 'annual_accounts',
            'due_date' => now()->subDay()->toDateString(),
            'reference_period' => '2026',
            'status' => 'pending',
            'completed_date' => null,
            'completed_by' => null,
            'notes' => null,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-compliance-alerts', [
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'refresh' => 1,
        ]));

        $response->assertOk();
        $alerts = $response->json('alerts');
        $this->assertIsArray($alerts);

        $backlogAlert = $this->findAlertByCode($alerts, 'fiscal_submission_backlog');
        $this->assertNotNull($backlogAlert);
        $this->assertSame(1, (int) $backlogAlert['count']);

        $saftAlert = $this->findAlertByCode($alerts, 'saft_generated_not_submitted');
        $this->assertNotNull($saftAlert);
        $this->assertSame(1, (int) $saftAlert['count']);

        $vatOverdueAlert = $this->findAlertByCode($alerts, 'vat_deadline_overdue');
        $this->assertNotNull($vatOverdueAlert);
        $this->assertSame(1, (int) $vatOverdueAlert['count']);

        $irpcOverdueAlert = $this->findAlertByCode($alerts, 'irpc_deadline_overdue');
        $this->assertNotNull($irpcOverdueAlert);
        $this->assertSame(1, (int) $irpcOverdueAlert['count']);

        $annualAccountsOverdueAlert = $this->findAlertByCode($alerts, 'annual_accounts_deadline_overdue');
        $this->assertNotNull($annualAccountsOverdueAlert);
        $this->assertSame(1, (int) $annualAccountsOverdueAlert['count']);
    }

    public function test_mozambique_electronic_money_compliance_report_endpoint_and_export(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);

        $missingClassification = BankAccount::query()->create([
            'account_number' => 'EM-RPT-001',
            'account_name' => 'Conta EM Sem Classificacao',
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

        $exceeded = BankAccount::query()->create([
            'account_number' => 'EM-RPT-002',
            'account_name' => 'Conta EM Excedida',
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

        $nearLimit = BankAccount::query()->create([
            'account_number' => 'EM-RPT-003',
            'account_name' => 'Conta EM Limite',
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
            'payment_number' => 'VP-EM-RPT-001',
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $exceeded->id,
            'payment_method' => 'mobile_money',
            'reference_number' => 'EM-EXCEEDED',
            'payment_amount' => 1200,
            'amount_mzn' => 1200,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CustomerPayment::query()->create([
            'payment_number' => 'CP-EM-RPT-001',
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $nearLimit->id,
            'payment_method' => 'mobile_money',
            'reference_number' => 'EM-NEAR',
            'payment_amount' => 900,
            'amount_mzn' => 900,
            'currency_code' => 'MZN',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-electronic-money-compliance-report', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'refresh' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('summary.electronic_money_accounts', 3);
        $response->assertJsonPath('summary.missing_classification', 1);
        $response->assertJsonPath('summary.monthly_limit_exceeded', 1);
        $response->assertJsonPath('summary.monthly_limit_near_threshold', 1);
        $response->assertJsonFragment([
            'account_number' => $missingClassification->account_number,
        ]);

        $exportResponse = $this->actingAs($company)->get(route('account.reports.mozambique-electronic-money-compliance-report.export', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'refresh' => 1,
        ]));

        $exportResponse->assertOk();
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $exportResponse->assertSee('monthly_limit_exceeded', false);
        $exportResponse->assertSee('monthly_limit_near_threshold', false);

        $this->assertDatabaseHas('fiscal_export_histories', [
            'company_id' => $company->id,
            'export_type' => 'electronic_money_compliance_report_csv',
            'status' => 'generated',
        ]);
    }

    public function test_mozambique_saft_export_returns_xml_filtered_by_period(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        MzVatCode::seedDefaults();

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);
        $productIse = ProductServiceItem::create([
            'name' => 'Produto SAF-T ISE',
            'sale_price' => 100,
            'purchase_price' => 80,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $productAut = ProductServiceItem::create([
            'name' => 'Produto SAF-T AUT',
            'sale_price' => 200,
            'purchase_price' => 160,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoiceIn = SalesInvoice::create([
            'invoice_number' => 'FT-SAFT-IN',
            'invoice_date' => '2026-01-12',
            'due_date' => '2026-01-20',
            'customer_id' => $customer->id,
            'subtotal' => 300,
            'tax_amount' => 32,
            'discount_amount' => 0,
            'total_amount' => 332,
            'paid_amount' => 0,
            'balance_amount' => 332,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        SalesInvoiceItem::create([
            'invoice_id' => $invoiceIn->id,
            'product_id' => $productIse->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'vat_code' => 'ISE',
            'tax_exemption_reason' => 'Isenção legal',
        ]);

        SalesInvoiceItem::create([
            'invoice_id' => $invoiceIn->id,
            'product_id' => $productAut->id,
            'quantity' => 1,
            'unit_price' => 200,
            'discount_percentage' => 0,
            'tax_percentage' => 16,
            'vat_code' => 'AUT',
            'tax_exemption_reason' => null,
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-SAFT-OUT',
            'invoice_date' => '2026-02-03',
            'due_date' => '2026-02-10',
            'customer_id' => $customer->id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'discount_amount' => 0,
            'total_amount' => 580,
            'paid_amount' => 0,
            'balance_amount' => 580,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-SAFT-IN',
            'invoice_date' => '2026-01-14',
            'due_date' => '2026-01-22',
            'vendor_id' => $vendor->id,
            'subtotal' => 400,
            'tax_amount' => 64,
            'discount_amount' => 0,
            'total_amount' => 464,
            'paid_amount' => 0,
            'balance_amount' => 464,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'validated',
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-saft.export', [
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertIsString($xml);
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('<AuditFile', $xml);
        $this->assertStringContainsString('FT-SAFT-IN', $xml);
        $this->assertStringContainsString('FR-SAFT-IN', $xml);
        $this->assertStringNotContainsString('FT-SAFT-OUT', $xml);

        $xmlDocument = simplexml_load_string($xml);
        $this->assertNotFalse($xmlDocument);
        $xmlDocument->registerXPathNamespace('saft', 'urn:OECD:StandardAuditFile-Tax:MZ_1.0');

        $invoiceNodes = $xmlDocument->xpath('//saft:SalesInvoices/saft:Invoice[saft:InvoiceNo="FT-SAFT-IN"]');
        $this->assertIsArray($invoiceNodes);
        $this->assertNotEmpty($invoiceNodes);

        $lineTaxCodeNodes = $xmlDocument->xpath('//saft:SalesInvoices/saft:Invoice[saft:InvoiceNo="FT-SAFT-IN"]/saft:Line/saft:Tax/saft:TaxCode');
        $this->assertIsArray($lineTaxCodeNodes);
        $this->assertCount(2, $lineTaxCodeNodes);

        $lineTaxCodes = array_map(static fn ($node): string => (string) $node, $lineTaxCodeNodes);

        $this->assertContains('ISE', $lineTaxCodes);
        $this->assertContains('AUT', $lineTaxCodes);
        $this->assertSame(['ISE', 'AUT'], $lineTaxCodes);

        $firstLineReason = $xmlDocument->xpath('//saft:SalesInvoices/saft:Invoice[saft:InvoiceNo="FT-SAFT-IN"]/saft:Line[1]/saft:Tax/saft:TaxExemptionReason');
        $this->assertSame('Isenção legal', (string) ($firstLineReason[0] ?? ''));

        $this->assertDatabaseHas('fiscal_export_histories', [
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'status' => 'generated',
        ]);

        $history = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'saft_xml')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('mozambique-saft-2026-01-01-to-2026-01-31.xml', $history->file_name);
        $this->assertSame(hash('sha256', $xml), $history->file_hash);
        $this->assertSame('application/xml', data_get($history->metadata, 'content_type'));
        $this->assertTrue((bool) data_get($history->metadata, 'validation.well_formed'));
        $this->assertSame('2026', (string) data_get($history->metadata, 'fiscal_year'));
        $this->assertSame(strlen($xml), (int) data_get($history->metadata, 'xml_size_bytes'));
        $this->assertNotEmpty(data_get($history->metadata, 'generated_at'));
        $this->assertNotEmpty($history->file_path);
        $this->assertTrue(Storage::disk('local')->exists($history->file_path));

        if ($history->file_path) {
            Storage::disk('local')->delete($history->file_path);
        }
    }

    public function test_mozambique_saft_export_requires_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-saft.export'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
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

    private function createPosSale(User $company, array $data): void
    {
        $product = ProductServiceItem::create([
            'name' => 'POS Product',
            'sale_price' => $data['total_amount'],
            'purchase_price' => $data['subtotal'],
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $posPayload = [
            'sale_number' => 'POS-' . uniqid(),
            'customer_id' => null,
            'warehouse_id' => null,
            'pos_date' => $data['pos_date'],
            'status' => $data['status'] ?? 'completed',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ];

        if (Schema::hasColumn('pos', 'fiscal_submission_status') && isset($data['fiscal_submission_status'])) {
            $posPayload['fiscal_submission_status'] = $data['fiscal_submission_status'];
        }

        if (Schema::hasColumn('pos', 'is_cancelled')) {
            $posPayload['is_cancelled'] = false;
        }

        $pos = Pos::create($posPayload);

        PosItem::create([
            'pos_id' => $pos->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $data['subtotal'],
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'],
            'total_amount' => $data['total_amount'],
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function findAlertByCode(array $alerts, string $code): ?array
    {
        foreach ($alerts as $alert) {
            if (($alert['code'] ?? null) === $code) {
                return $alert;
            }
        }

        return null;
    }
}
