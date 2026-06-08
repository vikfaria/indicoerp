<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\MonthlyClosingChecklist;
use App\Models\OnboardingSession;
use App\Models\Plan;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingCompletionService;
use App\Services\AssistantActivation\OnboardingSessionService;
use App\Services\AssistantActivation\OnboardingStepRegistry;
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
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Payroll;
use Workdo\ProductService\Models\ProductServiceItem;

class AssistantActivationOnboardingCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-07 12:00:00'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_exposes_the_onboarding_completion_contract_schema(): void
    {
        $service = app(OnboardingCompletionService::class);
        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame('Onboarding can be concluded when the session is active or already completed, required steps are resolved and readiness is ready.', $report['summary']['calculation_basis']);
        $this->assertSame(2, $report['summary']['decision_states_total']);
        $this->assertSame(5, $report['summary']['blocker_codes_total']);
        $this->assertSame(5, $report['summary']['validation_checks_total']);
        $this->assertSame(['complete', 'blocked'], $report['decision_states']);
        $this->assertSame([
            'missing_session',
            'session_inactive',
            'session_abandoned',
            'required_steps_incomplete',
            'readiness_blocked',
        ], $report['blocker_codes']);
    }

    public function test_it_allows_completion_when_all_required_steps_and_readiness_are_ready(): void
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

        $this->createCompletedStepsFromRegistry($session, $company);

        $report = app(OnboardingCompletionService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'Professional Plan'
        );

        $this->assertSame('complete', $report['summary']['completion_state']);
        $this->assertTrue($report['summary']['can_complete']);
        $this->assertFalse($report['summary']['already_completed']);
        $this->assertSame('ready', $report['summary']['readiness_state']);
        $this->assertSame(100.0, $report['summary']['readiness_score']);
        $this->assertSame(23, $report['summary']['required_steps_total']);
        $this->assertSame(23, $report['summary']['completed_required_steps_total']);
        $this->assertSame(0, $report['summary']['incomplete_required_steps_total']);
        $this->assertSame(0, $report['summary']['critical_blocks_total']);
        $this->assertSame(0, $report['summary']['blockers_total']);
        $this->assertSame([], $report['blockers']);
        $this->assertSame([], $report['critical_blocks']);
    }

    public function test_it_blocks_completion_when_required_steps_are_missing_and_readiness_is_not_ready(): void
    {
        $plan = $this->createPlan();
        $company = $this->makeCompany($plan);

        $session = app(OnboardingSessionService::class)->startForCompany($company->id, [
            'current_module_key' => 'billing',
            'current_step_key' => 'billing.configure_fiscal_profile',
        ]);

        $report = app(OnboardingCompletionService::class)->calculateForCompany(
            $company,
            ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
            'Professional Plan'
        );

        $this->assertSame('blocked', $report['summary']['completion_state']);
        $this->assertFalse($report['summary']['can_complete']);
        $this->assertFalse($report['summary']['already_completed']);
        $this->assertSame('critical', $report['summary']['readiness_state']);
        $this->assertSame(23, $report['summary']['required_steps_total']);
        $this->assertSame(0, $report['summary']['completed_required_steps_total']);
        $this->assertSame(23, $report['summary']['incomplete_required_steps_total']);
        $this->assertGreaterThan(0, $report['summary']['critical_blocks_total']);
        $this->assertGreaterThan(0, $report['summary']['blockers_total']);
        $this->assertSame($session->id, $report['meta']['session_id']);
        $this->assertContains('required_steps_incomplete', array_column($report['blockers'], 'code'));
        $this->assertContains('readiness_blocked', array_column($report['blockers'], 'code'));
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

    private function makeCompany(Plan $plan): User
    {
        return User::forceCreate([
            'name' => 'Empresa Completion',
            'email' => 'completion@example.com',
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

    private function prepareCompanySettings(User $company): void
    {
        setSetting('company_name', 'Empresa Completion', $company->id);
        setSetting('company_email', 'completion@example.com', $company->id);
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
                'legal_name' => 'Empresa Completion, Lda',
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
                'notes' => 'Tax profile for completion tests',
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
            'company_name' => 'Cliente Completion',
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
            'name' => 'Produto Completion',
            'sku' => 'SKU-COMPLETION-001',
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
            'name' => 'Tabela IRPS Completion',
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
}
