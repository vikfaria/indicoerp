<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;
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

    public function test_mozambique_saft_export_returns_xml_filtered_by_period(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);

        SalesInvoice::create([
            'invoice_number' => 'FT-SAFT-IN',
            'invoice_date' => '2026-01-12',
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
            'fiscal_submission_status' => 'submitted',
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
}
