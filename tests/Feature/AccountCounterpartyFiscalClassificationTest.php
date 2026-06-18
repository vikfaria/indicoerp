<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\Vendor;

class AccountCounterpartyFiscalClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_customer_store_requires_fiscal_classification_and_blocks_domestic_foreign_currency(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-customers']);

        $response = $this->from(route('account.customers.index'))
            ->actingAs($company)
            ->post(route('account.customers.store'), [
                'company_name' => 'Cliente Teste',
                'contact_person_name' => 'Financeiro',
                'contact_person_email' => 'cliente@teste.com',
                'tax_number' => '400123456',
                'fiscal_residency_status' => 'resident',
                'billing_currency_code' => 'USD',
                'billing_address' => $this->mozambiqueAddress('Cliente Teste'),
                'shipping_address' => $this->mozambiqueAddress('Cliente Teste'),
                'same_as_billing' => true,
            ]);

        $response->assertRedirect(route('account.customers.index'));
        $response->assertSessionHasErrors([
            'customer_type',
            'operation_type',
            'billing_currency_code',
        ]);
    }

    public function test_customer_store_requires_billing_currency_for_non_resident_customer(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-customers']);

        $response = $this->from(route('account.customers.index'))
            ->actingAs($company)
            ->post(route('account.customers.store'), [
                'company_name' => 'Cliente Externo',
                'contact_person_name' => 'Financeiro',
                'contact_person_email' => 'externo@teste.com',
                'fiscal_residency_status' => 'non_resident',
                'customer_type' => 'private_company',
                'fiscal_country' => 'South Africa',
                'operation_type' => 'export',
                'billing_address' => [
                    'name' => 'Cliente Externo',
                    'address_line_1' => 'Main Street 1',
                    'city' => 'Johannesburg',
                    'state' => 'Gauteng',
                    'country' => 'South Africa',
                    'zip_code' => '2000',
                ],
                'shipping_address' => [
                    'name' => 'Cliente Externo',
                    'address_line_1' => 'Main Street 1',
                    'city' => 'Johannesburg',
                    'state' => 'Gauteng',
                    'country' => 'South Africa',
                    'zip_code' => '2000',
                ],
                'same_as_billing' => true,
            ]);

        $response->assertRedirect(route('account.customers.index'));
        $response->assertSessionHasErrors(['billing_currency_code']);
    }

    public function test_vendor_store_rejects_inconsistent_adt_and_reverse_charge_configuration(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-vendors']);

        $response = $this->from(route('account.vendors.index'))
            ->actingAs($company)
            ->post(route('account.vendors.store'), [
                'company_name' => 'Fornecedor ADT Invalido',
                'contact_person_name' => 'Compliance',
                'contact_person_email' => 'vendor@teste.com',
                'tax_number' => '400123456',
                'fiscal_residency_status' => 'resident',
                'vendor_type' => 'service_provider',
                'fiscal_country' => 'Mozambique',
                'supply_type' => 'services',
                'payment_currency_code' => 'MZN',
                'reverse_charge_applicable' => true,
                'adt_eligible' => true,
                'billing_address' => $this->mozambiqueAddress('Fornecedor ADT Invalido'),
                'shipping_address' => $this->mozambiqueAddress('Fornecedor ADT Invalido'),
                'same_as_billing' => true,
            ]);

        $response->assertRedirect(route('account.vendors.index'));
        $response->assertSessionHasErrors([
            'reverse_charge_applicable',
            'adt_eligible',
            'adt_country',
            'foreign_tax_number',
            'compliance_documents',
        ]);
    }

    public function test_vendor_store_accepts_optional_user_placeholder_and_defaults_shipping_to_billing(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-vendors']);

        $response = $this->from(route('account.vendors.index'))
            ->actingAs($company)
            ->post(route('account.vendors.store'), [
                'user_id' => '0',
                'company_name' => 'Fornecedor Sem Utilizador',
                'contact_person_name' => 'Financeiro',
                'contact_person_email' => 'vendor@teste.com',
                'tax_number' => '400123456',
                'fiscal_residency_status' => 'resident',
                'vendor_type' => 'service_provider',
                'fiscal_country' => 'Mozambique',
                'supply_type' => 'services',
                'payment_currency_code' => 'MZN',
                'billing_address' => $this->mozambiqueAddress('Fornecedor Sem Utilizador'),
            ]);

        $response->assertRedirect(route('account.vendors.index'));
        $response->assertSessionHasNoErrors();

        $vendor = Vendor::query()
            ->where('company_name', 'Fornecedor Sem Utilizador')
            ->where('created_by', $company->id)
            ->firstOrFail();

        $this->assertNull($vendor->user_id);
        $this->assertSame($vendor->billing_address, $vendor->shipping_address);
        $this->assertSame('vendor@teste.com', $vendor->primary_email);
        $this->assertSame('MZN', $vendor->currency_code);
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

    private function mozambiqueAddress(string $name): array
    {
        return [
            'name' => $name,
            'address_line_1' => 'Av. Eduardo Mondlane',
            'city' => 'Maputo',
            'state' => 'Maputo',
            'country' => 'Mozambique',
            'zip_code' => '1100',
        ];
    }
}
