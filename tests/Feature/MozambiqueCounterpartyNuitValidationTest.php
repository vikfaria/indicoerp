<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\Vendor;

class MozambiqueCounterpartyNuitValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_customer_update_rejects_invalid_nuit_when_company_requires_nuit(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $customer = Customer::create([
            'company_name' => 'Cliente Teste',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'tax_number' => '400123456',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Teste',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'contact_person_mobile' => null,
            'tax_number' => 'ABC-123',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ]);

        $response->assertSessionHasErrors(['tax_number']);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'tax_number' => '400123456',
        ]);
    }

    public function test_vendor_update_rejects_invalid_nuit_when_company_requires_nuit(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-vendors']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $vendor = Vendor::create([
            'company_name' => 'Fornecedor Teste',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'tax_number' => '400123456',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('account.vendors.update', $vendor->id), [
            'company_name' => 'Fornecedor Teste',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '123-ABC',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ]);

        $response->assertSessionHasErrors(['tax_number']);
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'tax_number' => '400123456',
        ]);
    }

    public function test_customer_and_vendor_update_normalize_valid_nuit(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers', 'edit-vendors']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $customer = Customer::create([
            'company_name' => 'Cliente Teste',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'tax_number' => '400123456',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $vendor = Vendor::create([
            'company_name' => 'Fornecedor Teste',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'tax_number' => '400123456',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Teste',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400 123 456',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ])->assertSessionHasNoErrors();

        $this->actingAs($company)->put(route('account.vendors.update', $vendor->id), [
            'company_name' => 'Fornecedor Teste',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400-123-456',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'tax_number' => '400123456',
        ]);
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'tax_number' => '400123456',
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

    private function setCompanyMozambiqueNuitSettings(User $company): void
    {
        Setting::updateOrCreate(
            ['created_by' => $company->id, 'key' => 'company_country'],
            ['value' => 'Mozambique', 'is_public' => true]
        );
        Setting::updateOrCreate(
            ['created_by' => $company->id, 'key' => 'tax_type'],
            ['value' => 'NUIT', 'is_public' => true]
        );

        Cache::forget('company_settings_' . $company->id);
        Cache::forget('company_settings_' . $company->id . '_public');
    }

    private function baseAddress(): array
    {
        return [
            'name' => 'Head Office',
            'address_line_1' => 'Rua 1',
            'address_line_2' => null,
            'city' => 'Maputo',
            'state' => 'Maputo',
            'country' => 'Mozambique',
            'zip_code' => '1100',
        ];
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
}
