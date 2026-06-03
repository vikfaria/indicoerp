<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
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
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
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
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
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
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
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
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
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
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
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
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
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

    public function test_customer_update_allows_non_resident_without_nuit(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $customer = Customer::create([
            'company_name' => 'Cliente Externo',
            'contact_person_name' => 'Rita',
            'contact_person_email' => 'rita@example.com',
            'tax_number' => null,
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Externo',
            'contact_person_name' => 'Rita',
            'contact_person_email' => 'rita@example.com',
            'contact_person_mobile' => null,
            'tax_number' => null,
            'fiscal_residency_status' => 'non_resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Portugal',
            'operation_type' => 'export',
            'billing_currency_code' => 'USD',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ])->assertSessionHasNoErrors();
    }

    public function test_customer_update_blocks_critical_fiscal_change_after_posted_invoice(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $clientUser = User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $customer = Customer::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Cliente Fiscal',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-LOCK-001',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'customer_id' => $clientUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        $response = $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Fiscal Alterado',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ]);

        $response->assertSessionHasErrors(['tax_number']);
    }

    public function test_vendor_update_blocks_critical_fiscal_change_after_posted_purchase_invoice(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-vendors']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $vendorUser = User::factory()->create([
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Fiscal',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-LOCK-001',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'vendor_id' => $vendorUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'submitted',
        ]);

        $response = $this->actingAs($company)->put(route('account.vendors.update', $vendor->id), [
            'company_name' => 'Fornecedor Fiscal Alterado',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ]);

        $response->assertSessionHasErrors(['tax_number']);
    }

    public function test_customer_profile_is_locked_after_non_critical_update_when_fiscal_history_exists(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $clientUser = User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $customer = Customer::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Cliente Lock',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-LOCK-002',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'customer_id' => $clientUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Lock',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'payment_terms' => '15 dias',
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => 'Atualização comercial sem alteração fiscal crítica.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'fiscal_identity_lock_reason' => 'fiscal_documents_issued',
        ]);
        $this->assertNotNull($customer->fresh()?->fiscal_identity_locked_at);
    }

    public function test_vendor_profile_is_locked_after_non_critical_update_when_fiscal_history_exists(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-vendors']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $vendorUser = User::factory()->create([
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Lock',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-LOCK-002',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'vendor_id' => $vendorUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.vendors.update', $vendor->id), [
            'company_name' => 'Fornecedor Lock',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
            'payment_terms' => '30 dias',
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => 'Atualização operacional sem alteração fiscal crítica.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'fiscal_identity_lock_reason' => 'fiscal_documents_issued',
        ]);
        $this->assertNotNull($vendor->fresh()?->fiscal_identity_locked_at);
    }

    public function test_customer_critical_fiscal_change_can_be_overridden_with_reason_and_is_audited(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-customers', 'manage-account-reports']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $clientUser = User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $customer = Customer::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Cliente Override',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_number' => 'FT-LOCK-003',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'customer_id' => $clientUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'service',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.customers.update', $customer->id), [
            'company_name' => 'Cliente Override Novo Nome',
            'contact_person_name' => 'Ana',
            'contact_person_email' => 'ana@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'operation_type' => 'domestic',
            'billing_currency_code' => 'MZN',
            'fiscal_identity_lock_reason' => 'Correção fiscal suportada por documento retificativo interno.',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'Cliente Override Novo Nome',
            'fiscal_identity_lock_reason' => 'Correção fiscal suportada por documento retificativo interno.',
        ]);

        $auditEntry = AuditTrail::query()
            ->where('auditable_type', Customer::class)
            ->where('auditable_id', $customer->id)
            ->where('event', 'fiscal_override')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame('Cliente Override Novo Nome', data_get($auditEntry->new_values, 'company_name'));
        $this->assertSame(
            'Correção fiscal suportada por documento retificativo interno.',
            data_get($auditEntry->changes, 'fiscal_identity_lock_reason')
        );
        $this->assertContains('company_name', (array) data_get($auditEntry->changes, 'fields', []));
    }

    public function test_vendor_critical_fiscal_change_can_be_overridden_with_reason_and_is_audited(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-vendors', 'manage-account-reports']);
        $this->setCompanyMozambiqueNuitSettings($company);

        $vendorUser = User::factory()->create([
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'company_name' => 'Fornecedor Override',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
            'billing_address' => $this->baseAddress(),
            'shipping_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'FR-LOCK-003',
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-05-15',
            'vendor_id' => $vendorUser->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->put(route('account.vendors.update', $vendor->id), [
            'company_name' => 'Fornecedor Override Novo Nome',
            'contact_person_name' => 'Bruno',
            'contact_person_email' => 'bruno@example.com',
            'contact_person_mobile' => null,
            'tax_number' => '400123456',
            'fiscal_residency_status' => 'resident',
            'vendor_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'supply_type' => 'services',
            'payment_currency_code' => 'MZN',
            'fiscal_identity_lock_reason' => 'Correção fiscal suportada por documento retificativo interno.',
            'payment_terms' => null,
            'billing_address' => $this->baseAddress(),
            'same_as_billing' => true,
            'notes' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'company_name' => 'Fornecedor Override Novo Nome',
            'fiscal_identity_lock_reason' => 'Correção fiscal suportada por documento retificativo interno.',
        ]);

        $auditEntry = AuditTrail::query()
            ->where('auditable_type', Vendor::class)
            ->where('auditable_id', $vendor->id)
            ->where('event', 'fiscal_override')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame('Fornecedor Override Novo Nome', data_get($auditEntry->new_values, 'company_name'));
        $this->assertSame(
            'Correção fiscal suportada por documento retificativo interno.',
            data_get($auditEntry->changes, 'fiscal_identity_lock_reason')
        );
        $this->assertContains('company_name', (array) data_get($auditEntry->changes, 'fields', []));
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
