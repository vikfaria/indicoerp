<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Services\ReportService;

class AccountReportScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_customer_detail_report_is_scoped_and_includes_tax_identity(): void
    {
        $company = $this->makeCompany();
        $foreignCompany = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Relatorio');
        $foreignCustomer = $this->makeClient($foreignCompany, 'Cliente Externo');
        $warehouse = $this->makeWarehouse($company, 'Local Warehouse');
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->makeCustomerDetails($company, $customer, 'Cliente Relatorio Lda', '400111222');
        $this->makeCustomerDetails($foreignCompany, $foreignCustomer, 'Cliente Externo Lda', '499000999');

        $this->saveCompanySettings($company, [
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
        ]);

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
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
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

        CreditNote::create([
            'credit_note_number' => 'NC-2026-04-001',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'reason' => 'applied note',
            'status' => 'applied',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'applied_amount' => 20,
            'balance_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $report = app(ReportService::class)->getCustomerDetail($customer->id);

        $this->assertNotNull($report);
        $this->assertSame('Cliente Relatorio Lda', $report['customer']['company_name']);
        $this->assertSame('400111222', $report['customer']['tax_number']);
        $this->assertSame('NUIT', $report['customer']['tax_label']);
        $this->assertCount(1, $report['invoices']);
        $this->assertSame('FT-2026-04-001', $report['invoices']->first()->invoice_number);
        $this->assertCount(1, $report['credit_notes']);
        $this->assertSame('applied', $report['credit_notes']->first()->status);

        $this->assertNull(app(ReportService::class)->getCustomerDetail($foreignCustomer->id));
    }

    public function test_vendor_detail_report_is_scoped_and_includes_tax_identity(): void
    {
        $company = $this->makeCompany();
        $foreignCompany = $this->makeCompany();
        $vendor = $this->makeVendor($company, 'Fornecedor Relatorio');
        $foreignVendor = $this->makeVendor($foreignCompany, 'Fornecedor Externo');
        $warehouse = $this->makeWarehouse($company, 'Local Warehouse');
        $foreignWarehouse = $this->makeWarehouse($foreignCompany, 'Foreign Warehouse');

        $this->makeVendorDetails($company, $vendor, 'Fornecedor Relatorio SA', '400333444');
        $this->makeVendorDetails($foreignCompany, $foreignVendor, 'Fornecedor Externo SA', '499777666');

        $this->saveCompanySettings($company, [
            'company_country' => 'Mozambique',
            'tax_type' => 'NUIT',
        ]);

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
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
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

        DebitNote::create([
            'debit_note_number' => 'ND-2026-04-001',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'reason' => 'applied note',
            'status' => 'applied',
            'subtotal' => 20,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 20,
            'applied_amount' => 20,
            'balance_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $report = app(ReportService::class)->getVendorDetail($vendor->id);

        $this->assertNotNull($report);
        $this->assertSame('Fornecedor Relatorio SA', $report['vendor']['company_name']);
        $this->assertSame('400333444', $report['vendor']['tax_number']);
        $this->assertSame('NUIT', $report['vendor']['tax_label']);
        $this->assertCount(1, $report['invoices']);
        $this->assertSame('FC-2026-04-001', $report['invoices']->first()->invoice_number);
        $this->assertCount(1, $report['debit_notes']);
        $this->assertSame('applied', $report['debit_notes']->first()->status);

        $this->assertNull(app(ReportService::class)->getVendorDetail($foreignVendor->id));
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

    private function makeCustomerDetails(User $company, User $customer, string $companyName, string $taxNumber): Customer
    {
        return Customer::create([
            'user_id' => $customer->id,
            'customer_code' => 'CUST-' . str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'company_name' => $companyName,
            'contact_person_name' => 'Joana Cliente',
            'contact_person_email' => 'cliente@example.test',
            'contact_person_mobile' => '+258841111111',
            'tax_number' => $taxNumber,
            'payment_terms' => '15 days',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeVendorDetails(User $company, User $vendor, string $companyName, string $taxNumber): Vendor
    {
        return Vendor::create([
            'user_id' => $vendor->id,
            'vendor_code' => 'VEN-' . str_pad((string) (Vendor::count() + 1), 4, '0', STR_PAD_LEFT),
            'company_name' => $companyName,
            'contact_person_name' => 'Carlos Vendor',
            'contact_person_email' => 'vendor@example.test',
            'contact_person_mobile' => '+258842222222',
            'tax_number' => $taxNumber,
            'payment_terms' => '30 days',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function saveCompanySettings(User $company, array $settings): void
    {
        foreach ($settings as $key => $value) {
            setSetting($key, $value, $company->id);
        }
    }
}
