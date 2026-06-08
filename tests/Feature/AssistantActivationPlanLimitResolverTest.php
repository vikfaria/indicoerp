<?php

namespace Tests\Feature;

use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\Plan;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AssistantActivation\PlanLimitResolver;
use App\Services\AssistantActivation\TenantUsageService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Employee;
use Workdo\Pos\Models\Pos;

class AssistantActivationPlanLimitResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-07 12:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_resolves_core_limit_dimensions_and_report_states(): void
    {
        Config::set('assistant_activation_limits.plan_families.free.limits', [
            'users' => 2,
            'storage_kb' => 10,
            'companies' => 1,
            'document_series' => 1,
            'branches' => 2,
            'warehouses' => 1,
            'pos_registers' => 2,
            'employees' => 2,
            'documents_per_month' => 9,
            'bank_accounts' => 1,
        ]);

        $plan = Plan::create([
            'name' => 'Free Plan',
            'status' => true,
            'free_plan' => true,
            'modules' => ['Account', 'Hrm', 'ProductService', 'DoubleEntry'],
            'package_price_yearly' => 0,
            'package_price_monthly' => 0,
            'storage_limit' => 10,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 2,
        ]);

        $company = $this->makeUser('Empresa Teste', 'company', $plan->id, null, null);
        $customer = $this->makeUser('Cliente Teste', 'client', null, $company->id, $company->id, true);
        $vendor = $this->makeUser('Fornecedor Teste', 'vendor', null, $company->id, $company->id, true);
        $activeUser = $this->makeUser('Utilizador Activo', 'client', null, $company->id, $company->id);
        $this->makeUser('Utilizador Inactivo', 'client', null, $company->id, $company->id, true);

        $branch = Branch::create([
            'branch_name' => 'Maputo Centro',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $warehouseA = Warehouse::create([
            'name' => 'Armazem A',
            'address' => 'Rua 1',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '840000000',
            'email' => 'armazem-a@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Warehouse::create([
            'name' => 'Armazem B',
            'address' => 'Rua 2',
            'city' => 'Maputo',
            'zip_code' => '1101',
            'phone' => '840000001',
            'email' => 'armazem-b@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankAccount = BankAccount::create([
            'account_number' => '000123456',
            'account_name' => 'Conta Principal',
            'bank_name' => 'Banco Teste',
            'branch_name' => 'Centro',
            'account_type' => '0',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $documentType = FiscalDocumentType::create([
            'code' => 'FT',
            'name' => 'Factura',
            'saft_document_type' => 'FT',
            'category' => 'sales',
            'requires_hash' => true,
            'requires_series' => true,
            'is_credit_document' => false,
            'is_active' => true,
        ]);

        FiscalDocumentSeries::create([
            'company_id' => $company->id,
            'fiscal_document_type_id' => $documentType->id,
            'series_code' => 'A',
            'fiscal_year' => '2026',
            'last_sequence' => 0,
            'is_active' => true,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-12-31',
            'created_by' => $company->id,
        ]);

        $salesInvoice = SalesInvoice::create([
            'invoice_number' => 'SI-2026-06-001',
            'document_type' => 'SI',
            'document_series' => 'A',
            'document_sequence' => 1,
            'invoice_date' => '2026-06-07',
            'due_date' => '2026-06-30',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseA->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'product',
            'payment_terms' => '30 dias',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        $purchaseInvoice = PurchaseInvoice::create([
            'invoice_number' => 'PI-2026-06-001',
            'document_type' => 'PI',
            'document_series' => 'A',
            'document_sequence' => 1,
            'invoice_date' => '2026-06-07',
            'due_date' => '2026-06-30',
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouseA->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'debit_note_applied' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'payment_terms' => '30 dias',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        $salesReturn = SalesInvoiceReturn::create([
            'return_number' => 'SR-2026-06-001',
            'document_type' => 'SR',
            'document_series' => 'A',
            'document_sequence' => 1,
            'return_date' => '2026-06-07',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseA->id,
            'original_invoice_id' => $salesInvoice->id,
            'reason' => 'other',
            'subtotal' => 20,
            'tax_amount' => 3.2,
            'discount_amount' => 0,
            'total_amount' => 23.2,
            'status' => 'approved',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        $purchaseReturn = PurchaseReturn::create([
            'return_number' => 'PR-2026-06-001',
            'document_type' => 'PR',
            'document_series' => 'A',
            'document_sequence' => 1,
            'return_date' => '2026-06-07',
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouseA->id,
            'original_invoice_id' => $purchaseInvoice->id,
            'reason' => 'other',
            'subtotal' => 20,
            'tax_amount' => 3.2,
            'discount_amount' => 0,
            'total_amount' => 23.2,
            'status' => 'approved',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        CreditNote::create([
            'credit_note_number' => 'NC-2026-06-001',
            'document_type' => 'NC',
            'document_series' => 'A',
            'document_sequence' => 1,
            'credit_note_date' => '2026-06-07',
            'customer_id' => $customer->id,
            'invoice_id' => $salesInvoice->id,
            'return_id' => $salesReturn->id,
            'reason' => 'Ajuste teste',
            'status' => 'approved',
            'subtotal' => 20,
            'tax_amount' => 3.2,
            'discount_amount' => 0,
            'total_amount' => 23.2,
            'applied_amount' => 0,
            'balance_amount' => 23.2,
            'notes' => 'Teste',
            'approved_by' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        DebitNote::create([
            'debit_note_number' => 'ND-2026-06-001',
            'document_type' => 'ND',
            'document_series' => 'A',
            'document_sequence' => 1,
            'debit_note_date' => '2026-06-07',
            'vendor_id' => $vendor->id,
            'invoice_id' => $purchaseInvoice->id,
            'return_id' => $purchaseReturn->id,
            'reason' => 'Ajuste teste',
            'status' => 'approved',
            'subtotal' => 20,
            'tax_amount' => 3.2,
            'discount_amount' => 0,
            'total_amount' => 23.2,
            'applied_amount' => 0,
            'balance_amount' => 23.2,
            'notes' => 'Teste',
            'approved_by' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        Pos::create([
            'sale_number' => 'POS-2026-06-001',
            'document_type' => 'POS',
            'document_series' => 'A',
            'document_sequence' => 1,
            'pos_date' => '2026-06-07',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseA->id,
            'status' => 'completed',
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'fiscal_submission_status' => 'pending',
        ]);

        CustomerPayment::create([
            'payment_number' => 'CP-2026-06-001',
            'payment_date' => '2026-06-07',
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'reference_number' => 'REF-CP-001',
            'payment_amount' => 23.2,
            'status' => 'cleared',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        VendorPayment::create([
            'payment_number' => 'VP-2026-06-001',
            'payment_date' => '2026-06-07',
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'reference_number' => 'REF-VP-001',
            'payment_amount' => 23.2,
            'status' => 'cleared',
            'notes' => 'Teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::create([
            'employee_id' => 'EMP-001',
            'date_of_joining' => '2026-06-01',
            'employment_type' => 'full_time',
            'branch_id' => $branch->id,
            'user_id' => $activeUser->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        DB::table('media')->insert([
            'model_type' => User::class,
            'model_id' => $company->id,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'attachments',
            'name' => 'Documento de teste',
            'file_name' => 'documento-teste.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 8192,
            'manipulations' => json_encode([]),
            'custom_properties' => json_encode([]),
            'generated_conversions' => json_encode([]),
            'responsive_images' => json_encode([]),
            'order_column' => 1,
            'directory_id' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolver = app(PlanLimitResolver::class);
        $usageService = app(TenantUsageService::class);
        $referenceDate = CarbonImmutable::parse('2026-06-07 12:00:00');

        $users = $resolver->resolve('users', $company, $referenceDate);
        $storage = $resolver->resolve('storage_kb', $company, $referenceDate);
        $companies = $resolver->resolve('companies', $company, $referenceDate);
        $documentSeries = $resolver->resolve('document_series', $company, $referenceDate);
        $branches = $resolver->resolve('branches', $company, $referenceDate);
        $warehouses = $resolver->resolve('warehouses', $company, $referenceDate);
        $posRegisters = $resolver->resolve('pos_registers', $company, $referenceDate);
        $employees = $resolver->resolve('employees', $company, $referenceDate);
        $documents = $resolver->resolve('documents_per_month', $company, $referenceDate);
        $bankAccounts = $resolver->resolve('bank_accounts', $company, $referenceDate);

        $this->assertSame('free', $users['plan_family']);
        $this->assertSame('Free Plan', $users['plan_name']);
        $this->assertSame('active', $users['subscription_state']);

        $this->assertSame(2, $users['contracted_limit']);
        $this->assertSame(1, $users['current_usage']);
        $this->assertSame(1, $users['remaining']);
        $this->assertSame(50, $users['usage_percent']);
        $this->assertSame('within_limit', $users['state']);
        $this->assertSame(1, $users['usage_breakdown']['active_users']);

        $usageUsers = $usageService->resolve('users', $company, $referenceDate);
        $this->assertSame(1, $usageUsers['current_usage']);
        $this->assertSame(1, $usageUsers['usage_breakdown']['active_users']);

        $this->assertSame(10, $storage['contracted_limit']);
        $this->assertSame(8, $storage['current_usage']);
        $this->assertSame(2, $storage['remaining']);
        $this->assertSame(80, $storage['usage_percent']);
        $this->assertSame('near_limit', $storage['state']);
        $this->assertSame(8192, $storage['usage_breakdown']['bytes']);
        $this->assertSame(8, $storage['usage_breakdown']['kb']);

        $usageDocuments = $usageService->resolve('documents_per_month', $company, $referenceDate);
        $this->assertSame(9, $usageDocuments['current_usage']);
        $this->assertSame(1, $usageDocuments['usage_breakdown']['vendor_payments']);

        $usageReport = $usageService->buildReport($company, $referenceDate);
        $this->assertSame(10, $usageReport['summary']['dimensions_total']);
        $this->assertSame(26, $usageReport['summary']['current_usage_total']);

        $this->assertSame(1, $companies['contracted_limit']);
        $this->assertSame(1, $companies['current_usage']);
        $this->assertSame(0, $companies['remaining']);
        $this->assertSame(100, $companies['usage_percent']);
        $this->assertSame('near_limit', $companies['state']);

        $this->assertSame(1, $documentSeries['contracted_limit']);
        $this->assertSame(1, $documentSeries['current_usage']);
        $this->assertSame('near_limit', $documentSeries['state']);
        $this->assertSame(1, $documentSeries['usage_breakdown']['active_series']);

        $this->assertSame(2, $branches['contracted_limit']);
        $this->assertSame(1, $branches['current_usage']);
        $this->assertSame('within_limit', $branches['state']);
        $this->assertSame(1, $branches['usage_breakdown']['branches']);

        $this->assertSame(1, $warehouses['contracted_limit']);
        $this->assertSame(2, $warehouses['current_usage']);
        $this->assertSame(0, $warehouses['remaining']);
        $this->assertSame(200, $warehouses['usage_percent']);
        $this->assertSame('exceeded', $warehouses['state']);

        $this->assertSame(2, $posRegisters['contracted_limit']);
        $this->assertSame(1, $posRegisters['current_usage']);
        $this->assertSame('within_limit', $posRegisters['state']);
        $this->assertSame(1, $posRegisters['usage_breakdown']['distinct_warehouses']);
        $this->assertSame(1, $posRegisters['usage_breakdown']['pos_records']);

        $this->assertSame(2, $employees['contracted_limit']);
        $this->assertSame(1, $employees['current_usage']);
        $this->assertSame('within_limit', $employees['state']);
        $this->assertSame(1, $employees['usage_breakdown']['employees']);

        $this->assertSame(9, $documents['contracted_limit']);
        $this->assertSame(9, $documents['current_usage']);
        $this->assertSame(100, $documents['usage_percent']);
        $this->assertSame('near_limit', $documents['state']);

        $this->assertSame(1, $bankAccounts['contracted_limit']);
        $this->assertSame(1, $bankAccounts['current_usage']);
        $this->assertSame('near_limit', $bankAccounts['state']);
        $this->assertSame(1, $bankAccounts['usage_breakdown']['active_bank_accounts']);

        $this->assertSame(1, $documents['usage_breakdown']['sales_invoices']);
        $this->assertSame(1, $documents['usage_breakdown']['purchase_invoices']);
        $this->assertSame(1, $documents['usage_breakdown']['sales_returns']);
        $this->assertSame(1, $documents['usage_breakdown']['purchase_returns']);
        $this->assertSame(1, $documents['usage_breakdown']['credit_notes']);
        $this->assertSame(1, $documents['usage_breakdown']['debit_notes']);
        $this->assertSame(1, $documents['usage_breakdown']['pos']);
        $this->assertSame(1, $documents['usage_breakdown']['customer_payments']);
        $this->assertSame(1, $documents['usage_breakdown']['vendor_payments']);
        $this->assertSame('2026-06-01', $documents['usage_breakdown']['window_start']);
        $this->assertSame('2026-06-30', $documents['usage_breakdown']['window_end']);

        $report = $resolver->buildReport($company, $referenceDate);

        $this->assertSame(10, $report['summary']['dimensions_total']);
        $this->assertSame(4, $report['summary']['within_limit_total']);
        $this->assertSame(5, $report['summary']['near_limit_total']);
        $this->assertSame(1, $report['summary']['exceeded_total']);
        $this->assertSame(10, $report['summary']['subscription_state_counts']['active']);
    }

    public function test_it_marks_limit_resolutions_as_expired_when_subscription_is_past_due(): void
    {
        Config::set('assistant_activation_limits.plan_families.free.limits', [
            'users' => 2,
        ]);

        $plan = Plan::create([
            'name' => 'Free Plan',
            'status' => true,
            'free_plan' => true,
            'modules' => ['Account'],
            'package_price_yearly' => 0,
            'package_price_monthly' => 0,
            'storage_limit' => 10,
            'trial' => false,
            'trial_days' => 0,
            'number_of_users' => 2,
        ]);

        $company = $this->makeUser('Empresa Expirada', 'company', $plan->id, null, null);
        $company->update([
            'plan_expire_date' => now()->subDay()->toDateString(),
        ]);

        $resolver = app(PlanLimitResolver::class);
        $resolution = $resolver->resolve('users', $company, CarbonImmutable::parse('2026-06-07 12:00:00'));

        $this->assertSame('expired', $resolution['subscription_state']);
        $this->assertContains('subscription_expired', $resolution['reasons']);
        $this->assertSame(2, $resolution['contracted_limit']);
        $this->assertSame(0, $resolution['current_usage']);
        $this->assertSame('within_limit', $resolution['state']);
    }

    private function makeUser(string $name, string $type, ?int $activePlan, ?int $createdBy, ?int $creatorId, bool $disabled = false): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => Str::slug($name) . '@example.com',
            'type' => $type,
            'active_plan' => $activePlan ?? 0,
            'is_disable' => $disabled ? 1 : 0,
            'created_by' => $createdBy,
            'creator_id' => $creatorId,
        ]);
    }
}
