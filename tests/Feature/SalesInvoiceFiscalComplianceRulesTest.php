<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AccountingPeriod;
use App\Models\MzVatCode;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\Customer;
use Workdo\ProductService\Models\ProductServiceItem;

class SalesInvoiceFiscalComplianceRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_sales_invoice_requires_exemption_reason_for_exempt_vat_code(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-sales-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);

        $client = $this->makeClient($company);
        $service = $this->makeService($company);

        $payload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'customer_id' => $client->id,
            'type' => 'service',
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'ISE',
                    'tax_exemption_reason' => '',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('sales-invoices.store'), $payload);
        $response->assertSessionHasErrors(['items.0.tax_exemption_reason']);
    }

    public function test_sales_invoice_late_issue_requires_reason_and_persists_deadline_fields(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-sales-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);

        $client = $this->makeClient($company);
        $service = $this->makeService($company);

        $payload = [
            'invoice_date' => '2026-05-12',
            'operation_date' => '2026-05-01',
            'due_date' => '2026-05-20',
            'customer_id' => $client->id,
            'type' => 'service',
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                    'vat_code' => 'NOR',
                ],
            ],
        ];

        $this->actingAs($company)
            ->post(route('sales-invoices.store'), $payload)
            ->assertSessionHasErrors(['late_issue_reason']);

        $payload['late_issue_reason'] = 'Atraso por indisponibilidade de sistema no fecho.';

        $this->actingAs($company)
            ->post(route('sales-invoices.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sales_invoices', [
            'created_by' => $company->id,
            'issued_with_delay' => 1,
        ]);

        $invoice = SalesInvoice::query()->where('created_by', $company->id)->latest('id')->first();
        $this->assertSame('2026-05-01', $invoice?->operation_date?->toDateString());
        $this->assertSame('2026-05-08', $invoice?->fiscal_issue_deadline?->toDateString());
        $this->assertSame('Atraso por indisponibilidade de sistema no fecho.', $invoice?->late_issue_reason);
    }

    public function test_sales_invoice_requires_tax_percentage_to_match_vat_code_configuration(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-sales-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);

        $client = $this->makeClient($company);
        $service = $this->makeService($company);
        $this->attachCustomerProfile($company, $client, 'resident', 'domestic');

        $payload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'customer_id' => $client->id,
            'type' => 'service',
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 0,
                    'vat_code' => 'NOR',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('sales-invoices.store'), $payload);
        $response->assertSessionHasErrors(['items.0.tax_percentage']);
    }

    public function test_sales_invoice_reverse_charge_requires_non_resident_or_export_customer_context(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-sales-invoices']);
        MzVatCode::seedDefaults();
        $this->createOpenPeriod($company, '2026-05-01', '2026-05-31', 5);

        $client = $this->makeClient($company);
        $service = $this->makeService($company);
        $this->attachCustomerProfile($company, $client, 'resident', 'domestic');

        $payload = [
            'invoice_date' => '2026-05-05',
            'due_date' => '2026-05-10',
            'customer_id' => $client->id,
            'type' => 'service',
            'payment_terms' => null,
            'notes' => null,
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                    'vat_code' => 'AUT',
                ],
            ],
        ];

        $response = $this->actingAs($company)->post(route('sales-invoices.store'), $payload);
        $response->assertSessionHasErrors(['items.0.vat_code']);
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

    private function makeService(User $company): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => 'Serviço Fiscal',
            'sku' => 'SRV-FISC-001',
            'sale_price' => 100,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function attachCustomerProfile(User $company, User $client, string $residency, string $operationType): void
    {
        Customer::create([
            'user_id' => $client->id,
            'company_name' => 'Cliente Fiscal',
            'contact_person_name' => 'Financeiro',
            'contact_person_email' => 'financeiro@cliente.test',
            'customer_type' => 'private_company',
            'fiscal_residency_status' => $residency,
            'fiscal_country' => $residency === 'non_resident' ? 'Portugal' : 'Mozambique',
            'vat_regime' => 'standard',
            'operation_type' => $operationType,
            'billing_currency_code' => $residency === 'non_resident' ? 'EUR' : 'MZN',
            'billing_address' => ['country' => $residency === 'non_resident' ? 'Portugal' : 'Mozambique'],
            'shipping_address' => ['country' => $residency === 'non_resident' ? 'Portugal' : 'Mozambique'],
            'same_as_billing' => true,
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
}
