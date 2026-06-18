<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\MonthlyClosingChecklist;
use App\Models\OnboardingSession;
use App\Models\OnboardingStep;
use App\Models\Plan;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingReadinessService;
use App\Services\AssistantActivation\OnboardingSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Account\Models\OpeningBalance;
use Workdo\Contract\Models\Contract;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Services\AssistantActivation\ActivationMetricsService;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Payroll;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\Warehouse;
use Workdo\Pos\Models\Pos;
use App\Services\AssistantActivation\OnboardingStepRegistry;

class AssistantActivationOnboardingReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-07 12:00:00'));
        Cache::flush();
        config([
            'sce.saft.require_xsd_validation' => false,
            'sce.saft.xsd_path' => '',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_exposes_the_onboarding_readiness_contract_schema(): void
    {
        $service = app(OnboardingReadinessService::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame('Plan-aware onboarding progress plus critical configuration checks over applicable modules only.', $report['summary']['calculation_basis']);
        $this->assertSame(4, $report['summary']['state_codes_total']);
        $this->assertSame(2, $report['summary']['critical_block_types_total']);
        $this->assertSame(25, $report['summary']['critical_config_checks_total']);
        $this->assertSame(['ready', 'warning', 'blocked', 'critical'], $report['state_codes']);
        $this->assertSame(['config_missing', 'step_incomplete'], $report['critical_block_types']);
    }

    public function test_it_calculates_readiness_from_company_configuration_and_onboarding_progress(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $this->prepareCompanySettings($company);
        $this->prepareFiscalSetup($company);
        $this->prepareBillingMasterData($company);
        $this->prepareHrSetup($company);
        $this->prepareTreasurySetup($company);

        $session = app(OnboardingSessionService::class)->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.configure_fiscal_profile',
        ]);

        $this->createCompletedStep($session, $company, 'billing', 'billing.configure_fiscal_profile', 'Configurar perfil fiscal', 10);
        $this->createCompletedStep($session, $company, 'accounting', 'accounting.configure_chart_of_accounts', 'Configurar plano de contas', 10);
        $this->createCompletedStep($session, $company, 'hr', 'hr.create_employee_masterdata', 'Criar colaborador', 10);
        $this->createCompletedStep($session, $company, 'treasury', 'treasury.create_bank_accounts', 'Criar contas bancárias', 10);

        $report = app(OnboardingReadinessService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'Professional Plan'
        );

        $this->assertSame('blocked', $report['summary']['readiness_state']);
        $this->assertSame(73.33, $report['summary']['module_component_score']);
        $this->assertSame(100.0, $report['summary']['critical_config_score']);
        $this->assertSame(81.33, $report['summary']['overall_score']);
        $this->assertSame(4, $report['summary']['applicable_modules_total']);
        $this->assertSame(21, $report['summary']['applicable_config_checks_total']);
        $this->assertSame(6, $report['summary']['critical_blocks_total']);
        $this->assertSame(0, $report['summary']['config_blocks_total']);
        $this->assertSame(6, $report['summary']['step_blocks_total']);

        $checks = collect($report['critical_config_keys'])->keyBy('key');
        $this->assertTrue($checks['company_profile']['satisfied']);
        $this->assertTrue($checks['fiscal_profile']['satisfied']);
        $this->assertTrue($checks['document_series']['satisfied']);
        $this->assertTrue($checks['bank_accounts']['satisfied']);
        $this->assertTrue($checks['cash_accounts']['satisfied']);
        $this->assertTrue($checks['payment_methods']['satisfied']);
        $this->assertTrue($checks['reconciliation_rules']['satisfied']);
        $this->assertTrue($checks['mozambique_fiscal_compliance']['satisfied']);
        $this->assertSame('ok', $checks['mozambique_fiscal_compliance']['reason']);
        $this->assertContains('treasury', $checks['bank_accounts']['owner_modules']);
        $this->assertContains('treasury', $checks['cash_accounts']['owner_modules']);
        $this->assertContains('accounting', $checks['tax_profile']['owner_modules']);
        $this->assertTrue($checks['payroll_contributions']['satisfied']);
        $this->assertContains('hr', $checks['payroll_contributions']['owner_modules']);
        $this->assertSame([], $report['critical_blocks'][0]['owner_modules'] ?? []);
        $this->assertSame('step_incomplete', $report['critical_blocks'][0]['type']);
    }

    public function test_it_clears_config_driven_billing_pending_steps_when_live_configuration_is_already_valid(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $this->prepareCompanySettings($company);
        $this->prepareFiscalSetup($company);

        $report = app(OnboardingReadinessService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'Professional Plan'
        );

        $billingModule = collect($report['progress']['modules'])->firstWhere('key', 'billing');
        $criticalBlocks = collect($report['critical_blocks']);

        $this->assertNotNull($billingModule);
        $this->assertSame(50.0, $billingModule['progress_percent']);
        $this->assertFalse($criticalBlocks->contains(fn (array $block): bool => $block['key'] === 'billing.configure_fiscal_profile'));
        $this->assertFalse($criticalBlocks->contains(fn (array $block): bool => $block['key'] === 'billing.configure_document_series'));
        $this->assertFalse($criticalBlocks->contains(fn (array $block): bool => $block['key'] === 'billing.open_accounting_period'));
        $this->assertTrue($criticalBlocks->contains(fn (array $block): bool => $block['type'] === 'config_missing' && $block['key'] === 'customer_masterdata'));
        $this->assertTrue($criticalBlocks->contains(fn (array $block): bool => $block['type'] === 'config_missing' && $block['key'] === 'product_masterdata'));
    }

    public function test_it_reports_missing_tax_mapping_fields_when_vat_accounts_are_not_configured(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $this->prepareCompanySettings($company);
        $this->prepareFiscalSetup($company);

        MozTaxAccountMapping::query()
            ->where('created_by', $company->id)
            ->delete();

        $report = app(OnboardingReadinessService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'Professional Plan'
        );

        $checks = collect($report['critical_config_keys'])->keyBy('key');

        $this->assertFalse($checks['tax_profile']['satisfied']);
        $this->assertSame('missing_mapping', $checks['tax_profile']['reason']);
        $this->assertSame(['vat_output_account_id', 'vat_input_account_id'], $checks['tax_profile']['details']['missing_items']);
    }

    public function test_it_evaluates_inventory_and_pos_configuration_when_stock_layers_exist(): void
    {
        $plan = $this->createInventoryPlan();
        $company = $this->makeCompany($plan);

        $this->prepareInventorySetup($company);

        $report = app(OnboardingReadinessService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm', 'Pos'],
            'Inventory Plan'
        );

        $checks = collect($report['critical_config_keys'])->keyBy('key');
        $modules = collect($report['modules'])->keyBy('key');

        $this->assertSame(6, $report['summary']['applicable_modules_total']);
        $this->assertTrue($modules['inventory']['available']);
        $this->assertTrue($modules['pos']['available']);
        $this->assertTrue($checks['warehouses']['satisfied']);
        $this->assertTrue($checks['initial_stock']['satisfied']);
        $this->assertTrue($checks['fifo_layers']['satisfied']);
        $this->assertTrue($checks['pos_registers']['satisfied']);
        $this->assertSame('ok', $checks['warehouses']['reason']);
        $this->assertSame('ok', $checks['initial_stock']['reason']);
        $this->assertSame('ok', $checks['fifo_layers']['reason']);
        $this->assertSame('ok', $checks['pos_registers']['reason']);
    }

    public function test_it_aggregates_activation_metrics_across_ready_and_blocked_companies(): void
    {
        $plan = $this->createPlan();
        $this->enablePlanModules(['Account', 'ProductService', 'DoubleEntry', 'Hrm']);
        $readyCompany = $this->makeCompanyWithIdentity($plan, 'Empresa Ready Metrics', 'ready-metrics@example.com');
        $blockedCompany = $this->makeCompanyWithIdentity($plan, 'Empresa Blocked Metrics', 'blocked-metrics@example.com');

        $this->prepareCompanySettings($readyCompany);
        $this->prepareFiscalSetup($readyCompany);
        $this->prepareBillingMasterData($readyCompany);
        $this->prepareHrSetup($readyCompany);
        $this->prepareTreasurySetup($readyCompany);

        $readySession = app(OnboardingSessionService::class)->startForCompany($readyCompany->id, [
            'started_at' => now()->subHours(6),
            'last_activity_at' => now()->subHours(2),
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.issue_test_invoice',
        ]);

        $this->createCompletedStepsFromRegistry($readySession, $readyCompany);
        $readySession->forceFill([
            'progress_percent' => 100,
            'completed_at' => now()->subHours(2),
            'last_activity_at' => now()->subHours(2),
        ])->save();

        $blockedSession = app(OnboardingSessionService::class)->startForCompany($blockedCompany->id, [
            'started_at' => now()->subHour(),
            'last_activity_at' => now()->subMinutes(30),
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.issue_test_invoice',
        ]);

        $this->createBlockedStep(
            $blockedSession,
            $blockedCompany,
            'billing.issue_test_invoice',
            'Emitir factura de teste',
            60
        );

        $metrics = app(ActivationMetricsService::class)->calculate(collect([$readyCompany, $blockedCompany]));

        $this->assertSame(2, $metrics['summary']['companies_total']);
        $this->assertSame(1, $metrics['summary']['ready_companies_total']);
        $this->assertSame(0, $metrics['summary']['completed_companies_total']);
        $this->assertSame(2, $metrics['summary']['active_companies_total']);
        $this->assertSame(0, $metrics['summary']['not_started_companies_total']);
        $this->assertSame(1, $metrics['summary']['companies_with_blockers_total']);
        $this->assertSame(1, $metrics['summary']['blocked_steps_total']);
        $this->assertSame(1, $metrics['summary']['problematic_modules_total']);
        $this->assertSame(1, $metrics['time_to_readiness']['samples_total']);
        $this->assertSame(4.0, $metrics['time_to_readiness']['average_hours']);
        $this->assertSame(4.0, $metrics['time_to_readiness']['median_hours']);
        $this->assertSame($readyCompany->name, $metrics['time_to_readiness']['slowest_companies'][0]['company_name']);
        $this->assertSame($readyCompany->name, $metrics['time_to_readiness']['fastest_companies'][0]['company_name']);
        $this->assertSame('billing', $metrics['problematic_modules'][0]['key']);
        $this->assertSame($blockedCompany->name, $metrics['blocked_companies'][0]['company_name']);
        $this->assertGreaterThan(0, $metrics['summary']['critical_blocks_total']);
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'Professional Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);
    }

    private function createInventoryPlan(): Plan
    {
        return Plan::create([
            'name' => 'Inventory Plan',
            'status' => true,
            'free_plan' => false,
            'modules' => ['Account', 'ProductService', 'DoubleEntry', 'Hrm', 'Pos'],
            'package_price_yearly' => 960,
            'package_price_monthly' => 99,
            'storage_limit' => 51200,
            'trial' => true,
            'trial_days' => 30,
            'number_of_users' => 100,
        ]);
    }

    private function makeCompany(Plan $plan): User
    {
        return User::forceCreate([
            'name' => 'Empresa Readiness',
            'email' => 'readiness@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function makeCompanyWithIdentity(Plan $plan, string $name, string $email): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    /**
     * @param array<int, string> $modules
     */
    private function enablePlanModules(array $modules): void
    {
        foreach (array_values($modules) as $index => $module) {
            AddOn::updateOrCreate(
                ['module' => $module],
                [
                    'name' => $module,
                    'monthly_price' => 0,
                    'yearly_price' => 0,
                    'is_enable' => true,
                    'for_admin' => false,
                    'package_name' => $module,
                    'priority' => $index + 1,
                ]
            );
        }
    }

    private function prepareCompanySettings(User $company): void
    {
        setSetting('company_name', 'Empresa Readiness', $company->id);
        setSetting('company_email', 'readiness@example.com', $company->id);
        setSetting('company_country', 'Mozambique', $company->id);
        setSetting('company_telephone', '+258840000000', $company->id);
        setSetting('company_address', 'Avenida Julius Nyerere, Maputo', $company->id);
        setSetting('company_tax_number', '400123456', $company->id);
        setSetting('tax_type', 'NUIT', $company->id);

        setSetting('mz_company_sector_activity', 'Comércio e serviços', $company->id);
        setSetting('mz_company_operation_province', 'Maputo', $company->id);
        setSetting('mz_company_labour_regime', 'RGPS', $company->id);
        setSetting('mz_company_collective_agreements', 'Nenhum', $company->id);
        setSetting('mz_company_labour_directorate', 'Direcção Provincial do Trabalho de Maputo', $company->id);

        setSetting('mz_leave_min_notice_days', '7', $company->id);
        setSetting('mz_leave_entitlement_first_year_days', '12', $company->id);
        setSetting('mz_leave_entitlement_following_year_days', '30', $company->id);
        setSetting('mz_leave_count_non_working_days', '1', $company->id);
        setSetting('mz_leave_count_holidays', '1', $company->id);
    }

    private function prepareFiscalSetup(User $company): void
    {
        CompanyFiscalProfile::updateOrCreate(
            ['company_id' => $company->id],
            [
                'nuit' => '400123456',
                'legal_name' => 'Empresa Readiness, Lda',
                'fiscal_regime' => 'normal',
                'entity_classification' => 'medium',
                'accounting_framework' => 'pgc_nirf',
                'fiscal_year_start_month' => 1,
                'is_active' => true,
                'created_by' => $company->id,
            ]
        );

        FiscalDocumentType::seedDefaults();
        AccountingPeriod::generateForYear($company->id, '2026');

        $salesType = FiscalDocumentType::query()
            ->where('code', 'FT')
            ->firstOrFail();

        FiscalDocumentSeries::updateOrCreate(
            [
                'company_id' => $company->id,
                'fiscal_document_type_id' => $salesType->id,
                'series_code' => 'A',
            ],
            [
                'assigned_user_id' => $company->id,
                'terminal_code' => 'T1',
                'fiscal_regime_code' => 'normal',
                'fiscal_year' => '2026',
                'last_sequence' => 0,
                'is_active' => true,
                'valid_from' => now()->startOfYear()->toDateString(),
                'valid_to' => now()->endOfYear()->toDateString(),
                'created_by' => $company->id,
            ]
        );

        $vatOutput = $this->createChartAccount($company, '2431', 'IVA liquidado', 'credit');
        $vatInput = $this->createChartAccount($company, '2432', 'IVA dedutível', 'debit');
        $openingAccount = $this->createChartAccount($company, '1110', 'Caixa principal', 'debit');

        MozTaxAccountMapping::updateOrCreate(
            [
                'created_by' => $company->id,
                'is_active' => true,
                'effective_from' => now()->toDateString(),
            ],
            [
                'vat_output_account_id' => $vatOutput->id,
                'vat_input_account_id' => $vatInput->id,
                'withholding_payable_account_id' => null,
                'withholding_receivable_account_id' => null,
                'irpc_expense_account_id' => null,
                'effective_to' => null,
                'notes' => 'Tax profile for readiness tests',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]
        );

        OpeningBalance::create([
            'account_id' => $openingAccount->id,
            'financial_year' => '2026',
            'opening_balance' => 5000,
            'balance_type' => 'debit',
            'effective_date' => now()->toDateString(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $period = AccountingPeriod::query()
            ->forCompany($company->id)
            ->forYear('2026')
            ->where('period_number', 1)
            ->firstOrFail();

        MonthlyClosingChecklist::generateForPeriod($period->id, $company->id);
    }

    private function prepareBillingMasterData(User $company): void
    {
        Customer::create([
            'customer_code' => 'CUST-0001',
            'company_name' => 'Cliente Readiness',
            'contact_person_name' => 'Ana Cliente',
            'contact_person_email' => 'cliente@example.com',
            'contact_person_mobile' => '+258840000001',
            'tax_number' => '400123457',
            'fiscal_residency_status' => 'resident',
            'customer_type' => 'private_company',
            'fiscal_country' => 'Mozambique',
            'vat_regime' => 'normal',
            'operation_type' => 'local',
            'billing_currency_code' => 'MZN',
            'same_as_billing' => true,
            'payment_terms' => '30 dias',
            'billing_address' => ['address' => 'Rua 1'],
            'shipping_address' => ['address' => 'Rua 1'],
            'notes' => 'Cliente de teste',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        ProductServiceItem::create([
            'name' => 'Produto Readiness',
            'sku' => 'SKU-READINESS-001',
            'description' => 'Produto de validação',
            'sale_price' => 100.00,
            'purchase_price' => 70.00,
            'unit' => 'un',
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function prepareHrSetup(User $company): void
    {
        Employee::create([
            'employee_id' => 'EMP20260001',
            'gender' => 'Male',
            'date_of_joining' => now()->toDateString(),
            'employment_type' => 'permanent',
            'country' => 'Mozambique',
            'user_id' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveType::create([
            'name' => 'Férias anuais',
            'legal_code' => 'annual',
            'description' => 'Licença anual de teste',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'requires_supporting_document' => false,
            'must_be_consecutive' => false,
            'allow_cash_out' => false,
            'color' => '#00AA88',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Contract::create([
            'subject' => 'Template de contrato de admissão',
            'value' => null,
            'type_id' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'description' => 'Contrato template de validação',
            'status' => 'draft',
            'source_type' => 'template',
            'is_labour_contract' => true,
            'legal_contract_type' => 'indefinite',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->preparePayrollContributions($company);

        Payroll::create([
            'title' => 'Payroll Junho 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Teste de payroll',
            'total_gross_pay' => 1500,
            'total_deductions' => 150,
            'total_net_pay' => 1350,
            'total_irps' => 0,
            'total_inss_employee' => 0,
            'total_inss_employer' => 0,
            'employee_count' => 1,
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'bank_account_id' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function preparePayrollContributions(User $company): void
    {
        $irpsTable = MozIrpsTable::create([
            'name' => 'Tabela IRPS Readiness',
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozIrpsBracket::create([
            'irps_table_id' => $irpsTable->id,
            'range_from' => 0,
            'range_to' => null,
            'fixed_amount' => 0,
            'rate_percent' => 10,
            'sequence' => 1,
        ]);

        MozInssRate::create([
            'employee_rate' => 3.0000,
            'employer_rate' => 4.0000,
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);
    }

    private function prepareTreasurySetup(User $company): void
    {
        $bankAccount = BankAccount::create([
            'account_number' => '000123456',
            'account_name' => 'Conta Bancária Principal',
            'bank_name' => 'Banco de Teste',
            'branch_name' => 'Maputo',
            'account_type' => '0',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        BankAccount::create([
            'account_number' => '000123457',
            'account_name' => 'Caixa Pequena',
            'bank_name' => 'Caixa de Teste',
            'branch_name' => 'Maputo',
            'account_type' => 'cash',
            'opening_balance' => 200,
            'current_balance' => 200,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        BankTransaction::create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'credit',
            'reference_number' => 'BT-0001',
            'description' => 'Movimento de validação',
            'amount' => 250,
            'running_balance' => 1250,
            'transaction_status' => 'cleared',
            'reconciliation_status' => 'reconciled',
            'created_by' => $company->id,
        ]);
    }

    private function prepareInventorySetup(User $company): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Armazém Principal',
            'address' => 'Rua do Armazém, 1',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'phone' => '+258840000002',
            'email' => 'warehouse@example.com',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $product = ProductServiceItem::create([
            'name' => 'Produto Inventário',
            'sku' => 'SKU-INVENTORY-001',
            'description' => 'Produto usado no teste de FIFO e POS',
            'sale_price' => 120.00,
            'purchase_price' => 80.00,
            'unit' => 'un',
            'type' => 'product',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $movement = StockMovement::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'movement_date' => now()->toDateString(),
            'quantity' => 10,
            'unit_cost' => 80,
            'total_cost' => 800,
            'running_quantity' => 10,
            'running_value' => 800,
            'reference_type' => 'manual',
            'reference_id' => null,
            'warehouse_code' => $warehouse->id,
            'notes' => 'Stock inicial para validação',
            'journal_entry_id' => null,
            'created_by' => $company->id,
        ]);

        StockCostLayer::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'stock_movement_id' => $movement->id,
            'original_quantity' => 10,
            'remaining_quantity' => 10,
            'unit_cost' => 80,
            'entry_date' => now()->toDateString(),
            'is_exhausted' => false,
            'created_by' => $company->id,
        ]);

        Pos::create([
            'sale_number' => 'POS-2026-06-001',
            'document_type' => 'POS',
            'document_series' => 'A',
            'document_sequence' => 1,
            'establishment_id' => $warehouse->id,
            'customer_id' => null,
            'warehouse_id' => $warehouse->id,
            'pos_date' => now()->toDateString(),
            'status' => 'completed',
            'fiscal_submission_status' => 'not_required',
            'fiscal_submission_reference' => null,
            'fiscal_submitted_at' => null,
            'fiscal_validated_at' => null,
            'fiscal_validation_message' => null,
            'is_cancelled' => false,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancellation_reference' => null,
            'rectification_reference' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function createCompletedStepsFromRegistry(OnboardingSession $session, User $company): void
    {
        $registry = app(OnboardingStepRegistry::class);

        foreach ($registry->modules() as $module) {
            foreach ($module['steps'] as $step) {
                $session->steps()->create([
                    'company_id' => $company->id,
                    'module_key' => $module['key'],
                    'step_key' => $step['key'],
                    'step_label' => $step['label'],
                    'step_order' => $step['order'],
                    'is_required' => (bool) $step['required'],
                    'state' => 'completed',
                    'started_at' => now()->subMinutes(5),
                    'completed_at' => now(),
                    'created_by' => $company->id,
                ]);
            }
        }
    }

    private function createBlockedStep(OnboardingSession $session, User $company, string $stepKey, string $label, int $order): void
    {
        $session->steps()->create([
            'company_id' => $company->id,
            'module_key' => 'billing',
            'step_key' => $stepKey,
            'step_label' => $label,
            'step_order' => $order,
            'is_required' => false,
            'state' => 'blocked',
            'started_at' => now()->subHour(),
            'blocked_at' => now()->subMinutes(30),
            'skip_reason' => null,
            'created_by' => $company->id,
        ]);
    }

    private function createChartAccount(User $company, string $code, string $name, string $normalBalance): ChartOfAccount
    {
        $category = AccountCategory::create([
            'name' => 'Categoria ' . $code,
            'code' => 'CAT-' . $code,
            'type' => $normalBalance === 'credit' ? 'liabilities' : 'assets',
            'description' => 'Categoria de teste',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $accountType = AccountType::create([
            'category_id' => $category->id,
            'name' => 'Tipo ' . $code,
            'code' => 'TYP-' . $code,
            'normal_balance' => $normalBalance,
            'description' => 'Tipo de teste',
            'is_active' => true,
            'is_system_type' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type_id' => $accountType->id,
            'parent_account_id' => null,
            'level' => 1,
            'normal_balance' => $normalBalance,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_system_account' => false,
            'is_movement_account' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function createCompletedStep(OnboardingSession $session, User $company, string $moduleKey, string $stepKey, string $label, int $order): OnboardingStep
    {
        return $session->steps()->create([
            'company_id' => $company->id,
            'module_key' => $moduleKey,
            'step_key' => $stepKey,
            'step_label' => $label,
            'step_order' => $order,
            'is_required' => true,
            'state' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'created_by' => $company->id,
        ]);
    }
}
