<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AccountingPeriod;
use App\Models\MzVatCode;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use App\Models\Warehouse;
use Workdo\Account\Models\Vendor;
use Workdo\ProductService\Models\ProductServiceItem;

class PurchaseInvoiceFiscalComplianceRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_purchase_invoice_requires_exemption_reason_for_exempt_vat_code(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-purchase-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);
        $this->seedMozambiqueCompanyContext($company);

        $vendor = $this->makeVendor($company, 'Fornecedor Isento', 'resident', null, '400123456');
        $warehouse = $this->makeWarehouse($company);
        $product = $this->makeProduct($company, 'service');

        $payload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'vendor_id' => $vendor->user_id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'ISE',
                    'tax_exemption_reason' => '',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('purchase-invoices.store'), $payload);
        $response->assertSessionHasErrors(['items.0.tax_exemption_reason']);
    }

    public function test_purchase_invoice_requires_tax_percentage_to_match_vat_code_configuration(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-purchase-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);
        $this->seedMozambiqueCompanyContext($company);

        $vendor = $this->makeVendor($company, 'Fornecedor Normal', 'resident', null, '400123456');
        $warehouse = $this->makeWarehouse($company);
        $product = $this->makeProduct($company, 'service');

        $payload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'vendor_id' => $vendor->user_id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'NOR',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('purchase-invoices.store'), $payload);
        $response->assertSessionHasErrors(['items.0.tax_percentage']);
    }

    public function test_purchase_invoice_reverse_charge_requires_service_line_and_non_resident_vendor_context(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-purchase-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);
        $this->seedMozambiqueCompanyContext($company);

        $residentVendor = $this->makeVendor($company, 'Fornecedor Resident', 'resident', null, '400123456');
        $warehouse = $this->makeWarehouse($company);
        $product = $this->makeProduct($company, 'service');

        $residentPayload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'vendor_id' => $residentVendor->user_id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                    'vat_code' => 'AUT',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('purchase-invoices.store'), $residentPayload);
        $response->assertSessionHasErrors(['items.0.vat_code']);

        $nonResidentVendor = $this->makeVendor($company, 'Fornecedor Externo', 'non_resident', 'PT123456789', null);

        $nonResidentPayload = [
            'invoice_date' => '2026-05-06',
            'due_date' => '2026-05-11',
            'vendor_id' => $nonResidentVendor->user_id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 150,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                    'vat_code' => 'AUT',
                ],
            ],
        ];

        $this->actingAs($company)
            ->post(route('purchase-invoices.store'), $nonResidentPayload)
            ->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::query()->latest('id')->first();
        $this->assertNotNull($invoice);

        $this->assertDatabaseHas('purchase_invoice_items', [
            'invoice_id' => $invoice->id,
            'vat_code' => 'AUT',
            'tax_percentage' => 16,
        ]);

        $item = PurchaseInvoiceItem::query()
            ->where('invoice_id', $invoice->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($item);
        $this->assertDatabaseHas('purchase_invoice_item_taxes', [
            'item_id' => $item->id,
            'vat_code' => 'AUT',
        ]);
    }

    public function test_purchase_invoice_persists_exemption_reason_on_items_and_taxes(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-purchase-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);
        $this->seedMozambiqueCompanyContext($company);

        $vendor = $this->makeVendor($company, 'Fornecedor Isento', 'resident', null, '400123456');
        $warehouse = $this->makeWarehouse($company);
        $product = $this->makeProduct($company, 'service');

        $payload = [
            'invoice_date' => '2026-05-07',
            'due_date' => '2026-05-12',
            'vendor_id' => $vendor->user_id,
            'warehouse_id' => $warehouse->id,
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'ISE',
                    'tax_exemption_reason' => 'Isenção legal aplicada.',
                ],
            ],
        ];

        $this->actingAs($company)
            ->post(route('purchase-invoices.store'), $payload)
            ->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::query()->latest('id')->first();
        $this->assertNotNull($invoice);

        $item = PurchaseInvoiceItem::query()
            ->where('invoice_id', $invoice->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame('ISE', (string) $item->vat_code);
        $this->assertSame('Isenção legal aplicada.', (string) $item->tax_exemption_reason);

        $this->assertDatabaseHas('purchase_invoice_item_taxes', [
            'item_id' => $item->id,
            'vat_code' => 'ISE',
            'tax_exemption_reason' => 'Isenção legal aplicada.',
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

    private function makeVendor(User $company, string $name, string $residency, ?string $foreignTaxNumber, ?string $taxNumber): Vendor
    {
        $vendorUser = User::factory()->create([
            'name' => $name,
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $attributes = [
            'user_id' => $vendorUser->id,
            'company_name' => $name,
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@' . strtolower(str_replace(' ', '', $name)) . '.test',
            'tax_number' => $taxNumber,
            'foreign_tax_number' => $foreignTaxNumber,
            'fiscal_residency_status' => $residency,
            'vendor_type' => 'service_provider',
            'fiscal_country' => $residency === 'non_resident' ? 'Portugal' : 'Mozambique',
            'vat_regime' => 'standard',
            'supply_type' => 'services',
            'payment_currency_code' => $residency === 'non_resident' ? 'EUR' : 'MZN',
            'reverse_charge_applicable' => $residency === 'non_resident',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ];

        if (Schema::hasColumn('vendors', 'is_active')) {
            $attributes['is_active'] = true;
        }

        return Vendor::create($attributes);
    }

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Armazém Fiscal',
            'address' => 'Maputo',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeProduct(User $company, string $type = 'service'): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Linha Fiscal',
            'sku' => 'FISC-001-' . strtoupper($type),
            'type' => $type,
            'purchase_price' => 100,
            'sale_price' => 120,
            'unit' => 'Unidade',
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

    private function createOpenPeriod(User $company, string $startDate, string $endDate, int $periodNumber): void
    {
        AccountingPeriod::create([
            'company_id' => $company->id,
            'fiscal_year' => substr($startDate, 0, 4),
            'period_number' => $periodNumber,
            'period_name' => 'Periodo ' . $periodNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
            'created_by' => $company->id,
        ]);
    }

    private function seedMozambiqueCompanyContext(User $company): void
    {
        setSetting('company_country', 'Mozambique', $company->id);
        setSetting('tax_type', 'NUIT', $company->id);
    }
}
