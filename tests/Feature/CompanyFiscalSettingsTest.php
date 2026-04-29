<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesProposal;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;

class CompanyFiscalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_company_settings_store_mozambique_tax_identifier_and_document_prefixes(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-company-settings']);

        $response = $this->actingAs($company)->post(route('settings.company.update'), [
            'settings' => [
                'company_name' => 'Empresa Demo',
                'company_country' => 'Mozambique',
                'tax_type' => 'NUIT',
                'company_tax_number' => '400123456',
                'vat_gst_number_switch' => 'on',
                'sales_invoice_prefix' => 'FT',
                'purchase_invoice_prefix' => 'FC',
                'sales_proposal_prefix' => 'PRF',
                'sales_return_prefix' => 'NC',
                'purchase_return_prefix' => 'ND',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'company_tax_number',
            'value' => '400123456',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'vat_number',
            'value' => '400123456',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'tax_type',
            'value' => 'NUIT',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'sales_invoice_prefix',
            'value' => 'FT',
        ]);
    }

    public function test_document_numbers_use_company_prefix_settings(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $vendor = $this->makeVendor($company);
        $warehouse = $this->makeWarehouse($company);

        Setting::create(['key' => 'sales_invoice_prefix', 'value' => 'FT', 'is_public' => true, 'created_by' => $company->id]);
        Setting::create(['key' => 'purchase_invoice_prefix', 'value' => 'FC', 'is_public' => true, 'created_by' => $company->id]);
        Setting::create(['key' => 'sales_proposal_prefix', 'value' => 'PRF', 'is_public' => true, 'created_by' => $company->id]);
        Setting::create(['key' => 'sales_return_prefix', 'value' => 'NC', 'is_public' => true, 'created_by' => $company->id]);
        Setting::create(['key' => 'purchase_return_prefix', 'value' => 'ND', 'is_public' => true, 'created_by' => $company->id]);

        $this->actingAs($company);

        $salesInvoice = SalesInvoice::create([
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
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $nextSalesInvoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 150,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 150,
            'paid_amount' => 0,
            'balance_amount' => 150,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $purchaseInvoice = PurchaseInvoice::create([
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
            'status' => 'draft',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $proposal = SalesProposal::create([
            'proposal_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'status' => 'draft',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $salesReturn = SalesInvoiceReturn::create([
            'return_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $salesInvoice->id,
            'reason' => 'damaged',
            'subtotal' => 10,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 10,
            'status' => 'draft',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $purchaseReturn = PurchaseReturn::create([
            'return_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'original_invoice_id' => $purchaseInvoice->id,
            'reason' => 'damaged',
            'subtotal' => 10,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 10,
            'status' => 'draft',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $creditNote = CreditNote::create([
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'reason' => 'credit adjustment',
            'status' => 'draft',
            'subtotal' => 10,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 10,
            'applied_amount' => 0,
            'balance_amount' => 10,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $debitNote = DebitNote::create([
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'reason' => 'debit adjustment',
            'status' => 'draft',
            'subtotal' => 10,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 10,
            'applied_amount' => 0,
            'balance_amount' => 10,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $expectedPeriod = now()->format('Y-m');

        $this->assertSame("FT-{$expectedPeriod}-001", $salesInvoice->invoice_number);
        $this->assertSame("FT-{$expectedPeriod}-002", $nextSalesInvoice->invoice_number);
        $this->assertSame("FC-{$expectedPeriod}-001", $purchaseInvoice->invoice_number);
        $this->assertSame("PRF-{$expectedPeriod}-001", $proposal->proposal_number);
        $this->assertSame("NC-{$expectedPeriod}-001", $salesReturn->return_number);
        $this->assertSame("ND-{$expectedPeriod}-001", $purchaseReturn->return_number);
        $this->assertSame("NC-{$expectedPeriod}-001", $creditNote->credit_note_number);
        $this->assertSame("ND-{$expectedPeriod}-001", $debitNote->debit_note_number);
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

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Fiscal Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            $user->givePermissionTo($permission);
        }
    }
}
