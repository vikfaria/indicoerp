<?php

namespace App\Services\AssistantActivation;

use App\Models\AccountingPeriod;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\MonthlyClosingChecklist;
use App\Models\OnboardingSession;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FiscalValidationService;
use App\Services\MozambiquePayrollTaxService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\MozCashClosing;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Account\Models\OpeningBalance;
use Workdo\Contract\Models\Contract;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Payroll;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Pos\Models\Pos;

class OnboardingReadinessService
{
    private const STATE_CODES = ['ready', 'warning', 'blocked', 'critical'];

    private const CRITICAL_BLOCK_TYPES = ['config_missing', 'step_incomplete'];

    private const CASH_ACCOUNT_TYPES = ['cash', 'petty_cash', 'cashbox', 'caixa', 'caixa_menor'];

    public function __construct(
        private readonly OnboardingStepRegistry $onboardingStepRegistry,
        private readonly OnboardingProgressService $onboardingProgressService,
        private readonly ReadinessScoreService $readinessScoreService,
        private readonly FiscalValidationService $fiscalValidationService,
        private readonly MozambiquePayrollTaxService $mozambiquePayrollTaxService
    ) {
    }

    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_readiness.catalog_version', 'unknown');
    }

    public function buildReport(): array
    {
        $formula = $this->readinessScoreService->buildReport();
        $criticalConfigCatalog = $this->criticalConfigCatalog();

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'calculation_basis' => 'Plan-aware onboarding progress plus critical configuration checks over applicable modules only.',
                'state_codes_total' => count(self::STATE_CODES),
                'critical_block_types_total' => count(self::CRITICAL_BLOCK_TYPES),
                'module_weights_total' => (float) ($formula['summary']['module_weights_total'] ?? 0),
                'critical_config_weights_total' => (float) ($formula['summary']['critical_config_weights_total'] ?? 0),
                'module_component_weight' => (float) ($formula['summary']['module_component_weight'] ?? 70),
                'critical_config_component_weight' => (float) ($formula['summary']['critical_config_component_weight'] ?? 30),
                'ready_threshold' => (int) ($formula['summary']['ready_threshold'] ?? 80),
                'warning_threshold' => (int) ($formula['summary']['warning_threshold'] ?? 60),
                'blocked_threshold' => (int) ($formula['summary']['blocked_threshold'] ?? 40),
                'critical_config_checks_total' => count($criticalConfigCatalog),
            ],
            'state_codes' => self::STATE_CODES,
            'critical_block_types' => self::CRITICAL_BLOCK_TYPES,
            'module_weights' => $formula['module_weights'],
            'critical_config_keys' => $criticalConfigCatalog,
        ];
    }

    /**
     * @param array<int, string> $planModules
     */
    public function calculateForCompany(User|int $company, ?array $planModules = null, ?string $planLabel = null): array
    {
        $company = $company instanceof User ? $company : User::find($company);

        if (! $company) {
            throw new InvalidArgumentException('Company not found.');
        }

        $progress = $this->onboardingProgressService->calculateForCompany($company, $planModules, $planLabel);

        return $this->buildCalculatedReport($company, null, $progress, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    public function calculateForSession(OnboardingSession $session, ?array $planModules = null, ?string $planLabel = null): array
    {
        $session->loadMissing('company');

        if (! $session->company) {
            throw new InvalidArgumentException('Session company not found.');
        }

        $progress = $this->onboardingProgressService->calculateForSession($session, $planModules, $planLabel);

        return $this->buildCalculatedReport($session->company, $session, $progress, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    private function buildCalculatedReport(User $company, ?OnboardingSession $session, array $progress, ?array $planModules, ?string $planLabel): array
    {
        $planModules = $this->normalizeList($planModules ?? (array) data_get($progress, 'meta.plan_modules', []));
        $planLabel = $planLabel ?? (string) data_get($progress, 'meta.plan_label');

        $progressModules = collect((array) data_get($progress, 'modules', []))->keyBy('key');
        $moduleWeights = collect($this->readinessScoreService->moduleWeights())->keyBy('key');
        $configWeights = collect($this->readinessScoreService->criticalConfigWeights())->keyBy('key');
        $ownershipMap = $this->buildConfigOwnershipMap();

        $moduleReports = [];
        $applicableModuleWeightTotal = 0.0;
        $moduleWeightedScoreTotal = 0.0;
        $applicableModulesTotal = 0;
        $outOfScopeModulesTotal = 0;

        foreach ($this->onboardingStepRegistry->modules() as $module) {
            $progressModule = $progressModules->get($module['key']);
            $weight = (float) ($moduleWeights->get($module['key'], ['weight' => 0])['weight'] ?? 0);
            $availableStepCount = (int) ($progressModule['available_step_count'] ?? 0);
            $available = (bool) ($progressModule['available'] ?? false) && $availableStepCount > 0;
            $progressPercent = $available ? (float) ($progressModule['progress_percent'] ?? 0) : 0.0;

            if ($available) {
                $applicableModulesTotal++;
                $applicableModuleWeightTotal += $weight;
                $moduleWeightedScoreTotal += ($progressPercent * $weight);
            } else {
                $outOfScopeModulesTotal++;
            }

            $moduleReports[] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'weight' => $weight,
                'available' => $available,
                'applicable' => $available,
                'out_of_scope' => ! $available,
                'score' => $available ? $progressPercent : null,
                'progress_percent' => $progressPercent,
                'contribution' => $available ? round($progressPercent * $weight, 2) : 0.0,
                'step_count' => (int) ($progressModule['step_count'] ?? $module['step_count']),
                'available_step_count' => $availableStepCount,
                'unavailable_step_count' => (int) ($progressModule['unavailable_step_count'] ?? 0),
                'required_step_count' => (int) ($progressModule['required_step_count'] ?? $module['required_step_count']),
                'required_available_step_count' => (int) ($progressModule['required_available_step_count'] ?? 0),
                'completed_required_step_count' => (int) ($progressModule['completed_required_step_count'] ?? 0),
                'completed_step_count' => (int) ($progressModule['completed_step_count'] ?? 0),
                'partial_step_count' => (int) ($progressModule['partial_step_count'] ?? 0),
                'pending_step_count' => (int) ($progressModule['pending_step_count'] ?? 0),
                'blocked_step_count' => (int) ($progressModule['blocked_step_count'] ?? 0),
                'technical_modules' => $module['technical_modules'],
            ];
        }

        $availableModuleKeys = array_values(array_map(
            static fn (array $module): string => (string) $module['key'],
            array_values(array_filter($moduleReports, static fn (array $module): bool => (bool) $module['available']))
        ));

        $moduleComponentScore = $applicableModuleWeightTotal > 0
            ? round($moduleWeightedScoreTotal / $applicableModuleWeightTotal, 2)
            : 0.0;

        $configReports = [];
        $applicableConfigWeightTotal = 0.0;
        $configWeightedScoreTotal = 0.0;
        $applicableConfigChecksTotal = 0;
        $outOfScopeConfigChecksTotal = 0;

        foreach ($this->criticalConfigCatalog() as $entry) {
            $ownerModules = $entry['owner_modules'] ?? [];
            $applicableOwnerModules = array_values(array_intersect($ownerModules, $availableModuleKeys));
            $applicable = $applicableOwnerModules !== [];
            $evaluation = $this->evaluateConfigCheck($company->id, $entry['key'], $entry['label'], $applicable, $ownerModules, $applicableOwnerModules);
            $weight = (float) ($entry['weight'] ?? 0);

            if ($applicable) {
                $applicableConfigChecksTotal++;
                $applicableConfigWeightTotal += $weight;

                if ($evaluation['satisfied']) {
                    $configWeightedScoreTotal += $weight;
                }
            } else {
                $outOfScopeConfigChecksTotal++;
            }

            $configReports[] = array_merge($evaluation, [
                'weight' => $weight,
                'owner_modules' => $ownerModules,
                'applicable_owner_modules' => $applicableOwnerModules,
                'applicable' => $applicable,
                'out_of_scope' => ! $applicable,
                'score' => $applicable ? ($evaluation['satisfied'] ? 100.0 : 0.0) : null,
                'contribution' => $applicable && $evaluation['satisfied'] ? $weight : 0.0,
            ]);
        }

        $criticalConfigScore = $applicableConfigWeightTotal > 0
            ? round(($configWeightedScoreTotal / $applicableConfigWeightTotal) * 100, 2)
            : 0.0;

        $formula = $this->readinessScoreService->formula();
        $moduleComponentWeight = (float) ($formula['module_component_weight'] ?? 70);
        $criticalConfigComponentWeight = (float) ($formula['critical_config_component_weight'] ?? 30);
        $readyThreshold = (int) ($formula['ready_threshold'] ?? 80);
        $warningThreshold = (int) ($formula['warning_threshold'] ?? 60);
        $blockedThreshold = (int) ($formula['blocked_threshold'] ?? 40);

        $overallScore = round(
            ($moduleComponentScore * ($moduleComponentWeight / 100))
            + ($criticalConfigScore * ($criticalConfigComponentWeight / 100)),
            2
        );

        $stepBlocks = $this->buildStepBlocks($progress);
        $configBlocks = array_values(array_map(
            fn (array $check): array => [
                'type' => 'config_missing',
                'key' => $check['key'],
                'label' => $check['label'],
                'owner_modules' => $check['owner_modules'],
                'applicable_owner_modules' => $check['applicable_owner_modules'],
                'reason' => $check['reason'],
                'message' => $this->buildConfigBlockMessage($check),
                'details' => $check['details'],
            ],
            array_values(array_filter(
                $configReports,
                fn (array $check): bool => ($check['applicable'] ?? false) && ! ($check['satisfied'] ?? false)
            ))
        ));

        $criticalBlocks = array_merge($configBlocks, $stepBlocks);
        $readinessState = $this->resolveReadinessState(
            $overallScore,
            count($criticalBlocks),
            count(array_filter($moduleReports, fn (array $module): bool => $module['available'])),
            $applicableConfigChecksTotal,
            $readyThreshold,
            $warningThreshold,
            $blockedThreshold
        );

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
                'plan_label' => $planLabel,
                'plan_modules' => $planModules,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'session_id' => $session?->id ?? data_get($progress, 'meta.session_id'),
                'session_status' => $session?->status ?? data_get($progress, 'meta.session_status'),
            ],
            'summary' => [
                'readiness_state' => $readinessState,
                'overall_score' => $overallScore,
                'module_component_score' => $moduleComponentScore,
                'critical_config_score' => $criticalConfigScore,
                'module_component_weight' => $moduleComponentWeight,
                'critical_config_component_weight' => $criticalConfigComponentWeight,
                'ready_threshold' => $readyThreshold,
                'warning_threshold' => $warningThreshold,
                'blocked_threshold' => $blockedThreshold,
                'applicable_modules_total' => count(array_filter($moduleReports, fn (array $module): bool => $module['available'])),
                'out_of_scope_modules_total' => $outOfScopeModulesTotal,
                'applicable_module_weight_total' => round($applicableModuleWeightTotal, 2),
                'applicable_config_checks_total' => $applicableConfigChecksTotal,
                'out_of_scope_config_checks_total' => $outOfScopeConfigChecksTotal,
                'applicable_config_weight_total' => round($applicableConfigWeightTotal, 2),
                'critical_blocks_total' => count($criticalBlocks),
                'config_blocks_total' => count($configBlocks),
                'step_blocks_total' => count($stepBlocks),
                'available_steps_total' => (int) data_get($progress, 'summary.available_steps_total', 0),
                'completed_steps_total' => (int) data_get($progress, 'summary.completed_steps_total', 0),
                'partial_steps_total' => (int) data_get($progress, 'summary.partial_steps_total', 0),
                'blocked_steps_total' => (int) data_get($progress, 'summary.blocked_steps_total', 0),
            ],
            'progress' => $progress,
            'modules' => $moduleReports,
            'critical_config_keys' => $configReports,
            'critical_blocks' => $criticalBlocks,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildStepBlocks(array $progress): array
    {
        $blocks = [];

        foreach ((array) data_get($progress, 'modules', []) as $module) {
            if (! (bool) ($module['available'] ?? false)) {
                continue;
            }

            foreach ((array) ($module['steps'] ?? []) as $step) {
                if (! (bool) ($step['available'] ?? false) || ! (bool) ($step['required'] ?? false)) {
                    continue;
                }

                $stepProgress = (float) ($step['progress_percent'] ?? 0);
                if ($stepProgress >= 100.0) {
                    continue;
                }

                $state = (string) ($step['state'] ?? 'pending');

                $blocks[] = [
                    'type' => 'step_incomplete',
                    'module_key' => $module['key'],
                    'module_label' => $module['label'],
                    'key' => $step['key'],
                    'label' => $step['label'],
                    'checklist_key' => $step['checklist_key'] ?? null,
                    'reason' => match ($state) {
                        'blocked' => 'blocked_step',
                        'in_progress' => 'partial_step',
                        default => 'pending_step',
                    },
                    'message' => $this->buildStepBlockMessage($step, $stepProgress),
                    'state' => $state,
                    'state_label' => $step['state_label'] ?? ucfirst($state),
                    'progress_percent' => $stepProgress,
                    'items_total' => (int) ($step['items_total'] ?? 0),
                    'items_completed_total' => (int) ($step['items_completed_total'] ?? 0),
                    'items_blocked_total' => (int) ($step['items_blocked_total'] ?? 0),
                    'items_pending_total' => (int) ($step['items_pending_total'] ?? 0),
                    'items_not_applicable_total' => (int) ($step['items_not_applicable_total'] ?? 0),
                ];
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function criticalConfigCatalog(): array
    {
        $ownershipMap = $this->buildConfigOwnershipMap();
        $catalog = [];

        foreach ($this->readinessScoreService->criticalConfigWeights() as $entry) {
            $catalog[] = [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'weight' => $entry['weight'],
                'description' => $entry['description'],
                'owner_modules' => $ownershipMap[$entry['key']] ?? [],
            ];
        }

        return $catalog;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildConfigOwnershipMap(): array
    {
        $map = [];

        foreach ($this->onboardingStepRegistry->modules() as $module) {
            $configKeys = (array) ($module['required_config_keys'] ?? []);

            foreach ((array) ($module['steps'] ?? []) as $step) {
                $configKeys = array_merge($configKeys, (array) ($step['config_keys'] ?? []));
            }

            $keys = array_values(array_unique(array_filter($configKeys)));

            foreach ($keys as $key) {
                $map[$key] ??= [];
                $map[$key][] = $module['key'];
            }
        }

        foreach ($map as $key => $modules) {
            $map[$key] = array_values(array_unique($modules));
            sort($map[$key]);
        }

        return $map;
    }

    private function evaluateConfigCheck(
        int $companyId,
        string $key,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        return match ($key) {
            'company_profile' => $this->evaluateCompanyProfile($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'fiscal_profile' => $this->evaluateFiscalProfile($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'document_series' => $this->evaluateDocumentSeries($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'accounting_period' => $this->evaluateAccountingPeriod($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'tax_profile' => $this->evaluateTaxProfile($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'mozambique_fiscal_compliance' => $this->evaluateMozambiqueFiscalCompliance($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'customer_masterdata' => $this->evaluateCustomerMasterdata($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'product_masterdata' => $this->evaluateProductMasterdata($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'chart_of_accounts' => $this->evaluateChartOfAccounts($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'opening_balances' => $this->evaluateOpeningBalances($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'closing_rules' => $this->evaluateClosingRules($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'hr_company_profile' => $this->evaluateHrCompanyProfile($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'employee_masterdata' => $this->evaluateEmployeeMasterdata($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'contract_templates' => $this->evaluateContractTemplates($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'payroll_calendar' => $this->evaluatePayrollCalendar($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'payroll_contributions' => $this->evaluatePayrollContributions($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'warehouses' => $this->evaluateWarehouses($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'initial_stock' => $this->evaluateInitialStock($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'fifo_layers' => $this->evaluateFifoLayers($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'pos_registers' => $this->evaluatePosRegisters($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'leave_policy' => $this->evaluateLeavePolicy($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'bank_accounts' => $this->evaluateBankAccounts($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'cash_accounts' => $this->evaluateCashAccounts($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'payment_methods' => $this->evaluatePaymentMethods($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            'reconciliation_rules' => $this->evaluateReconciliationRules($companyId, $label, $applicable, $ownerModules, $applicableOwnerModules),
            default => [
                'key' => $key,
                'label' => $label,
                'satisfied' => false,
                'reason' => 'unknown_check',
                'details' => null,
            ],
        };
    }

    private function evaluateCompanyProfile(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('company_profile', $label);
        }

        $settings = getCompanyAllSetting($companyId);
        $requiredFields = [
            'company_name',
            'company_email',
            'company_country',
            'company_telephone',
            'company_address',
        ];
        $missingFields = array_values(array_filter($requiredFields, static fn (string $field): bool => trim((string) ($settings[$field] ?? '')) === ''));

        $taxType = strtoupper(trim((string) ($settings['tax_type'] ?? '')));
        $country = strtolower(trim((string) ($settings['company_country'] ?? '')));
        $taxNumber = trim((string) ($settings['company_tax_number'] ?? $settings['vat_number'] ?? ''));
        $needsTaxNumber = $taxType !== '' || str_contains($country, 'mozambique') || str_contains($country, 'moçambique');

        if ($missingFields !== []) {
            return [
                'key' => 'company_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fields',
                'details' => [
                    'missing_fields' => $missingFields,
                    'tax_type' => $taxType !== '' ? $taxType : null,
                    'tax_number' => $taxNumber !== '' ? $taxNumber : null,
                ],
            ];
        }

        if ($needsTaxNumber) {
            try {
                $this->fiscalValidationService->validateNuit($taxNumber, 'company_tax_number');
            } catch (\Throwable $exception) {
                return [
                    'key' => 'company_profile',
                    'label' => $label,
                    'satisfied' => false,
                    'reason' => 'invalid_tax_number',
                    'details' => $exception->getMessage(),
                ];
            }
        }

        return [
            'key' => 'company_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'company_name' => trim((string) ($settings['company_name'] ?? '')),
                'company_country' => trim((string) ($settings['company_country'] ?? '')),
                'company_email' => trim((string) ($settings['company_email'] ?? '')),
                'company_telephone' => trim((string) ($settings['company_telephone'] ?? '')),
                'company_address' => trim((string) ($settings['company_address'] ?? '')),
                'tax_type' => $taxType !== '' ? $taxType : null,
                'tax_number' => $taxNumber !== '' ? $taxNumber : null,
            ],
        ];
    }

    private function evaluateFiscalProfile(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('fiscal_profile', $label);
        }

        if (! Schema::hasTable('company_fiscal_profiles')) {
            return $this->missingTableConfigResult('fiscal_profile', $label, 'company_fiscal_profiles');
        }

        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $profile) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_profile',
                'details' => null,
            ];
        }

        $requiredFields = ['nuit', 'legal_name', 'fiscal_regime', 'accounting_framework', 'entity_classification'];
        $missingFields = array_values(array_filter($requiredFields, static fn (string $field): bool => trim((string) ($profile->{$field} ?? '')) === ''));

        if ($missingFields !== []) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fields',
                'details' => [
                    'profile_id' => $profile->id,
                    'missing_fields' => $missingFields,
                ],
            ];
        }

        if ($profile->license_expiry_date !== null && ! $profile->isLicenseValid()) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'license_expired',
                'details' => [
                    'profile_id' => $profile->id,
                    'license_expiry_date' => $profile->license_expiry_date?->toDateString(),
                ],
            ];
        }

        try {
            $this->fiscalValidationService->validateNuit($profile->nuit, 'nuit');
        } catch (\Throwable $exception) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'invalid_nuit',
                'details' => $exception->getMessage(),
            ];
        }

        return [
            'key' => 'fiscal_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'profile_id' => $profile->id,
                'nuit' => $profile->nuit,
                'legal_name' => $profile->legal_name,
                'fiscal_regime' => $profile->fiscal_regime,
                'accounting_framework' => $profile->accounting_framework,
                'entity_classification' => $profile->entity_classification,
            ],
        ];
    }

    private function evaluateDocumentSeries(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('document_series', $label);
        }

        if (! Schema::hasTable('fiscal_document_series')) {
            return $this->missingTableConfigResult('document_series', $label, 'fiscal_document_series');
        }

        $today = now()->toDateString();

        $series = FiscalDocumentSeries::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('fiscalDocumentType', function ($query): void {
                $query->where('is_active', true)->where('category', 'sales');
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        if (! $series) {
            return [
                'key' => 'document_series',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_series',
                'details' => null,
            ];
        }

        return [
            'key' => 'document_series',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'series_id' => $series->id,
                'series_code' => $series->series_code,
                'document_type' => $series->fiscalDocumentType?->code,
                'valid_from' => $series->valid_from?->toDateString(),
                'valid_to' => $series->valid_to?->toDateString(),
            ],
        ];
    }

    private function evaluateAccountingPeriod(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('accounting_period', $label);
        }

        try {
            $period = $this->fiscalValidationService->validatePeriodOpen(now()->toDateString(), $companyId);
        } catch (\Throwable $exception) {
            return [
                'key' => 'accounting_period',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'period_not_open',
                'details' => $exception->getMessage(),
            ];
        }

        return [
            'key' => 'accounting_period',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'period_id' => $period->id ?? null,
                'period_name' => $period->period_name ?? null,
                'status' => $period->status ?? 'open',
            ],
        ];
    }

    private function evaluateTaxProfile(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('tax_profile', $label);
        }

        if (! Schema::hasTable('mz_tax_account_mappings')) {
            return $this->missingTableConfigResult('tax_profile', $label, 'mz_tax_account_mappings');
        }

        $today = now()->toDateString();
        $mapping = MozTaxAccountMapping::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->whereNotNull('vat_output_account_id')
            ->whereNotNull('vat_input_account_id')
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        if (! $mapping) {
            return [
                'key' => 'tax_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_mapping',
                'details' => null,
            ];
        }

        return [
            'key' => 'tax_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'mapping_id' => $mapping->id,
                'vat_output_account_id' => $mapping->vat_output_account_id,
                'vat_input_account_id' => $mapping->vat_input_account_id,
                'effective_from' => $mapping->effective_from?->toDateString(),
                'effective_to' => $mapping->effective_to?->toDateString(),
            ],
        ];
    }

    private function evaluateMozambiqueFiscalCompliance(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('mozambique_fiscal_compliance', $label);
        }

        $checks = [
            'company_profile' => $this->evaluateCompanyProfile($companyId, $label, true, $ownerModules, $applicableOwnerModules),
            'fiscal_profile' => $this->evaluateFiscalProfile($companyId, $label, true, $ownerModules, $applicableOwnerModules),
            'document_series' => $this->evaluateDocumentSeries($companyId, $label, true, $ownerModules, $applicableOwnerModules),
            'accounting_period' => $this->evaluateAccountingPeriod($companyId, $label, true, $ownerModules, $applicableOwnerModules),
            'tax_profile' => $this->evaluateTaxProfile($companyId, $label, true, $ownerModules, $applicableOwnerModules),
        ];

        $calendarRoutesReady = Route::has('sce.fiscal.calendar')
            && Route::has('sce.fiscal.generate-calendar')
            && Route::has('sce.fiscal.complete-event')
            && Route::has('sce.fiscal.calendar.export');

        $saftValidationRequired = (bool) config('sce.saft.require_xsd_validation', false);
        $saftXsdPath = trim((string) config('sce.saft.xsd_path', ''));
        $saftXsdReady = ! $saftValidationRequired || ($saftXsdPath !== '' && is_file($saftXsdPath));

        $checks['calendar_routes'] = [
            'key' => 'calendar_routes',
            'label' => __('Fiscal calendar routes'),
            'satisfied' => $calendarRoutesReady,
            'reason' => $calendarRoutesReady ? 'ok' : 'missing_calendar_routes',
            'details' => [
                'calendar' => Route::has('sce.fiscal.calendar'),
                'generate' => Route::has('sce.fiscal.generate-calendar'),
                'complete' => Route::has('sce.fiscal.complete-event'),
                'export' => Route::has('sce.fiscal.calendar.export'),
            ],
        ];

        $checks['saft_xsd'] = [
            'key' => 'saft_xsd',
            'label' => __('SAF-T XSD validation'),
            'satisfied' => $saftXsdReady,
            'reason' => $saftXsdReady ? 'ok' : 'missing_saft_xsd',
            'details' => [
                'required' => $saftValidationRequired,
                'xsd_path' => $saftValidationRequired ? $saftXsdPath : null,
            ],
        ];

        $missingItems = [];
        $blockingReason = 'ok';

        foreach ($checks as $checkKey => $check) {
            if (! (bool) ($check['satisfied'] ?? false)) {
                $missingItems[] = $checkKey;
                $blockingReason = (string) ($check['reason'] ?? 'missing_fiscal_compliance');
                break;
            }
        }

        if ($missingItems !== []) {
            return [
                'key' => 'mozambique_fiscal_compliance',
                'label' => $label,
                'satisfied' => false,
                'reason' => $blockingReason,
                'details' => [
                    'missing_items' => $missingItems,
                    'calendar_routes_ready' => $calendarRoutesReady,
                    'saft_validation_required' => $saftValidationRequired,
                    'saft_xsd_path' => $saftValidationRequired ? $saftXsdPath : null,
                    'checks' => $checks,
                ],
            ];
        }

        return [
            'key' => 'mozambique_fiscal_compliance',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'missing_items' => [],
                'calendar_routes_ready' => true,
                'saft_validation_required' => $saftValidationRequired,
                'saft_xsd_path' => $saftValidationRequired ? $saftXsdPath : null,
                'checks' => $checks,
            ],
        ];
    }

    private function evaluateCustomerMasterdata(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('customer_masterdata', $label);
        }

        if (! Schema::hasTable('customers')) {
            return $this->missingTableConfigResult('customer_masterdata', $label, 'customers');
        }

        $customer = Customer::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->orderByDesc('id')
            ->first();

        if (! $customer) {
            return [
                'key' => 'customer_masterdata',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_customers',
                'details' => null,
            ];
        }

        return [
            'key' => 'customer_masterdata',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'company_name' => $customer->company_name,
            ],
        ];
    }

    private function evaluateProductMasterdata(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('product_masterdata', $label);
        }

        if (! Schema::hasTable('product_service_items')) {
            return $this->missingTableConfigResult('product_masterdata', $label, 'product_service_items');
        }

        $product = ProductServiceItem::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $product) {
            return [
                'key' => 'product_masterdata',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_products',
                'details' => null,
            ];
        }

        return [
            'key' => 'product_masterdata',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ],
        ];
    }

    private function evaluateChartOfAccounts(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('chart_of_accounts', $label);
        }

        if (! Schema::hasTable('chart_of_accounts')) {
            return $this->missingTableConfigResult('chart_of_accounts', $label, 'chart_of_accounts');
        }

        $account = ChartOfAccount::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->orderByDesc('id')
            ->first();

        if (! $account) {
            return [
                'key' => 'chart_of_accounts',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_accounts',
                'details' => null,
            ];
        }

        return [
            'key' => 'chart_of_accounts',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
            ],
        ];
    }

    private function evaluateOpeningBalances(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('opening_balances', $label);
        }

        if (! Schema::hasTable('opening_balances')) {
            return $this->missingTableConfigResult('opening_balances', $label, 'opening_balances');
        }

        $openingBalance = OpeningBalance::query()
            ->whereHas('account', function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->orderByDesc('id')
            ->first();

        if (! $openingBalance) {
            return [
                'key' => 'opening_balances',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_opening_balances',
                'details' => null,
            ];
        }

        return [
            'key' => 'opening_balances',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'opening_balance_id' => $openingBalance->id,
                'account_id' => $openingBalance->account_id,
                'financial_year' => $openingBalance->financial_year,
                'opening_balance' => $openingBalance->opening_balance,
            ],
        ];
    }

    private function evaluateClosingRules(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('closing_rules', $label);
        }

        if (! Schema::hasTable('monthly_closing_checklists')) {
            return $this->missingTableConfigResult('closing_rules', $label, 'monthly_closing_checklists');
        }

        $hasChecklist = MonthlyClosingChecklist::query()
            ->where('company_id', $companyId)
            ->exists();

        if (! $hasChecklist) {
            return [
                'key' => 'closing_rules',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_closing_checklists',
                'details' => null,
            ];
        }

        return [
            'key' => 'closing_rules',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'checklists_total' => MonthlyClosingChecklist::query()
                    ->where('company_id', $companyId)
                    ->count(),
            ],
        ];
    }

    private function evaluateHrCompanyProfile(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('hr_company_profile', $label);
        }

        $settings = getCompanyAllSetting($companyId);
        $requiredFields = [
            'mz_company_sector_activity',
            'mz_company_operation_province',
            'mz_company_labour_regime',
            'mz_company_collective_agreements',
            'mz_company_labour_directorate',
        ];
        $missingFields = array_values(array_filter($requiredFields, static fn (string $field): bool => trim((string) ($settings[$field] ?? '')) === ''));

        if ($missingFields !== []) {
            return [
                'key' => 'hr_company_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fields',
                'details' => [
                    'missing_fields' => $missingFields,
                ],
            ];
        }

        return [
            'key' => 'hr_company_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'sector_activity' => trim((string) ($settings['mz_company_sector_activity'] ?? '')),
                'operation_province' => trim((string) ($settings['mz_company_operation_province'] ?? '')),
                'labour_regime' => trim((string) ($settings['mz_company_labour_regime'] ?? '')),
                'collective_agreements' => trim((string) ($settings['mz_company_collective_agreements'] ?? '')),
                'labour_directorate' => trim((string) ($settings['mz_company_labour_directorate'] ?? '')),
            ],
        ];
    }

    private function evaluateEmployeeMasterdata(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('employee_masterdata', $label);
        }

        if (! Schema::hasTable('employees')) {
            return $this->missingTableConfigResult('employee_masterdata', $label, 'employees');
        }

        $employee = Employee::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->orderByDesc('id')
            ->first();

        if (! $employee) {
            return [
                'key' => 'employee_masterdata',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_employees',
                'details' => null,
            ];
        }

        return [
            'key' => 'employee_masterdata',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_id,
                'user_id' => $employee->user_id,
            ],
        ];
    }

    private function evaluateContractTemplates(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('contract_templates', $label);
        }

        if (! Schema::hasTable('contracts')) {
            return $this->missingTableConfigResult('contract_templates', $label, 'contracts');
        }

        $template = Contract::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('source_type', 'template')
            ->orderByDesc('id')
            ->first();

        if (! $template) {
            return [
                'key' => 'contract_templates',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_templates',
                'details' => null,
            ];
        }

        return [
            'key' => 'contract_templates',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'contract_id' => $template->id,
                'template_number' => $template->template_number,
                'subject' => $template->subject,
            ],
        ];
    }

    private function evaluatePayrollCalendar(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('payroll_calendar', $label);
        }

        if (! Schema::hasTable('payrolls')) {
            return $this->missingTableConfigResult('payroll_calendar', $label, 'payrolls');
        }

        $payroll = Payroll::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where(function ($query): void {
                $query->whereNotNull('pay_date')
                    ->orWhereNotNull('pay_period_start')
                    ->orWhereNotNull('pay_period_end');
            })
            ->orderByDesc('id')
            ->first();

        if (! $payroll) {
            return [
                'key' => 'payroll_calendar',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_payrolls',
                'details' => null,
            ];
        }

        return [
            'key' => 'payroll_calendar',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'payroll_id' => $payroll->id,
                'title' => $payroll->title,
                'pay_date' => $payroll->pay_date?->toDateString(),
                'pay_period_start' => $payroll->pay_period_start?->toDateString(),
                'pay_period_end' => $payroll->pay_period_end?->toDateString(),
            ],
        ];
    }

    private function evaluatePayrollContributions(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('payroll_contributions', $label);
        }

        $today = now()->toDateString();
        $irps = $this->mozambiquePayrollTaxService->calculateIrps(50000, $companyId, $today);
        $inss = $this->mozambiquePayrollTaxService->calculateInss(50000, $companyId, $today);

        $missingItems = [];
        $reason = null;

        if (! (bool) ($irps['configured'] ?? false)) {
            $missingItems[] = 'irps_table';
            $reason = match ((string) ($irps['rule'] ?? '')) {
                'table_missing' => 'missing_irps_table',
                'no_matching_bracket' => 'missing_irps_brackets',
                default => 'missing_contributions',
            };
        }

        if (! (bool) ($inss['configured'] ?? false)) {
            $missingItems[] = 'inss_rate';
            $reason ??= 'missing_inss_rate';
        }

        if ($missingItems !== []) {
            return [
                'key' => 'payroll_contributions',
                'label' => $label,
                'satisfied' => false,
                'reason' => $reason ?? 'missing_contributions',
                'details' => [
                    'missing_items' => array_values(array_unique($missingItems)),
                    'irps_rule' => $irps['rule'] ?? null,
                    'irps_table_id' => $irps['table_id'] ?? null,
                    'irps_bracket_id' => $irps['bracket_id'] ?? null,
                    'inss_rate_id' => $inss['rate_id'] ?? null,
                    'employee_rate' => $inss['employee_rate'] ?? null,
                    'employer_rate' => $inss['employer_rate'] ?? null,
                ],
            ];
        }

        return [
            'key' => 'payroll_contributions',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'irps_table_id' => $irps['table_id'] ?? null,
                'irps_bracket_id' => $irps['bracket_id'] ?? null,
                'inss_rate_id' => $inss['rate_id'] ?? null,
                'employee_rate' => $inss['employee_rate'] ?? null,
                'employer_rate' => $inss['employer_rate'] ?? null,
            ],
        ];
    }

    private function evaluateWarehouses(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('warehouses', $label);
        }

        if (! Schema::hasTable('warehouses')) {
            return $this->missingTableConfigResult('warehouses', $label, 'warehouses');
        }

        $warehouse = Warehouse::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $warehouse) {
            return [
                'key' => 'warehouses',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_warehouses',
                'details' => null,
            ];
        }

        return [
            'key' => 'warehouses',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'is_active' => (bool) $warehouse->is_active,
            ],
        ];
    }

    private function evaluateInitialStock(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('initial_stock', $label);
        }

        if (! Schema::hasTable('stock_movements')) {
            return $this->missingTableConfigResult('initial_stock', $label, 'stock_movements');
        }

        $movement = StockMovement::query()
            ->where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->orderByDesc('id')
            ->first();

        if (! $movement) {
            return [
                'key' => 'initial_stock',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_initial_stock',
                'details' => null,
            ];
        }

        return [
            'key' => 'initial_stock',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'movement_id' => $movement->id,
                'product_id' => $movement->product_id,
                'quantity' => (float) $movement->quantity,
                'movement_type' => $movement->movement_type,
                'warehouse_code' => $movement->warehouse_code,
            ],
        ];
    }

    private function evaluateFifoLayers(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('fifo_layers', $label);
        }

        if (! Schema::hasTable('stock_cost_layers')) {
            return $this->missingTableConfigResult('fifo_layers', $label, 'stock_cost_layers');
        }

        $layer = StockCostLayer::query()
            ->where('company_id', $companyId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_exhausted', false)
            ->orderByDesc('id')
            ->first();

        if (! $layer) {
            return [
                'key' => 'fifo_layers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fifo_layers',
                'details' => null,
            ];
        }

        return [
            'key' => 'fifo_layers',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'layer_id' => $layer->id,
                'movement_id' => $layer->stock_movement_id,
                'product_id' => $layer->product_id,
                'remaining_quantity' => (float) $layer->remaining_quantity,
                'unit_cost' => (float) $layer->unit_cost,
            ],
        ];
    }

    private function evaluatePosRegisters(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('pos_registers', $label);
        }

        if (! Schema::hasTable('pos')) {
            return $this->missingTableConfigResult('pos_registers', $label, 'pos');
        }

        $pos = Pos::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->whereNotNull('warehouse_id')
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            })
            ->orderByDesc('id')
            ->first();

        if (! $pos) {
            return [
                'key' => 'pos_registers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_pos_registers',
                'details' => null,
            ];
        }

        return [
            'key' => 'pos_registers',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'pos_id' => $pos->id,
                'sale_number' => $pos->sale_number,
                'warehouse_id' => $pos->warehouse_id,
                'status' => $pos->status,
                'is_cancelled' => (bool) $pos->is_cancelled,
            ],
        ];
    }

    private function evaluateLeavePolicy(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('leave_policy', $label);
        }

        if (! Schema::hasTable('leave_types')) {
            return $this->missingTableConfigResult('leave_policy', $label, 'leave_types');
        }

        $settings = getCompanyAllSetting($companyId);
        $requiredFields = [
            'mz_leave_min_notice_days',
            'mz_leave_entitlement_first_year_days',
            'mz_leave_entitlement_following_year_days',
            'mz_leave_count_non_working_days',
            'mz_leave_count_holidays',
        ];
        $missingFields = array_values(array_filter($requiredFields, static fn (string $field): bool => trim((string) ($settings[$field] ?? '')) === ''));

        if ($missingFields !== []) {
            return [
                'key' => 'leave_policy',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fields',
                'details' => [
                    'missing_fields' => $missingFields,
                ],
            ];
        }

        $leaveType = LeaveType::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->orderByDesc('id')
            ->first();

        if (! $leaveType) {
            return [
                'key' => 'leave_policy',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_leave_types',
                'details' => null,
            ];
        }

        return [
            'key' => 'leave_policy',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'min_notice_days' => (int) ($settings['mz_leave_min_notice_days'] ?? 0),
                'first_year_days' => (int) ($settings['mz_leave_entitlement_first_year_days'] ?? 0),
                'following_year_days' => (int) ($settings['mz_leave_entitlement_following_year_days'] ?? 0),
            ],
        ];
    }

    private function evaluateBankAccounts(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('bank_accounts', $label);
        }

        if (! Schema::hasTable('bank_accounts')) {
            return $this->missingTableConfigResult('bank_accounts', $label, 'bank_accounts');
        }

        $bankAccount = BankAccount::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $bankAccount) {
            return [
                'key' => 'bank_accounts',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_bank_accounts',
                'details' => null,
            ];
        }

        return [
            'key' => 'bank_accounts',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'bank_account_id' => $bankAccount->id,
                'account_name' => $bankAccount->account_name,
                'account_type' => $bankAccount->account_type,
            ],
        ];
    }

    private function evaluateCashAccounts(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('cash_accounts', $label);
        }

        if (! Schema::hasTable('bank_accounts')) {
            return $this->missingTableConfigResult('cash_accounts', $label, 'bank_accounts');
        }

        $cashAccount = BankAccount::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->whereIn('account_type', self::CASH_ACCOUNT_TYPES)
            ->orderByDesc('id')
            ->first();

        if (! $cashAccount) {
            return [
                'key' => 'cash_accounts',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_cash_accounts',
                'details' => null,
            ];
        }

        return [
            'key' => 'cash_accounts',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'bank_account_id' => $cashAccount->id,
                'account_name' => $cashAccount->account_name,
                'account_type' => $cashAccount->account_type,
            ],
        ];
    }

    private function evaluatePaymentMethods(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('payment_methods', $label);
        }

        $methods = array_values(array_filter(array_map(
            static fn ($method): string => trim((string) $method),
            (array) config('sce.gifim.electronic_payment_methods', [])
        )));

        if ($methods === []) {
            return [
                'key' => 'payment_methods',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_methods',
                'details' => null,
            ];
        }

        return [
            'key' => 'payment_methods',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'methods' => $methods,
                'methods_total' => count($methods),
            ],
        ];
    }

    private function evaluateReconciliationRules(
        int $companyId,
        string $label,
        bool $applicable,
        array $ownerModules,
        array $applicableOwnerModules
    ): array {
        if (! $applicable) {
            return $this->outOfScopeConfigResult('reconciliation_rules', $label);
        }

        if (! Schema::hasTable('bank_accounts')) {
            return $this->missingTableConfigResult('reconciliation_rules', $label, 'bank_accounts');
        }

        $hasActiveBankAccount = BankAccount::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->exists();

        if (! $hasActiveBankAccount) {
            return [
                'key' => 'reconciliation_rules',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_bank_accounts',
                'details' => null,
            ];
        }

        $hasBankTransactionsQuery = BankTransaction::query()
            ->where('created_by', $companyId);

        if (Schema::hasColumn('bank_transactions', 'creator_id')) {
            $hasBankTransactionsQuery->orWhere('creator_id', $companyId);
        }

        $hasBankTransactions = $hasBankTransactionsQuery->exists();

        $hasCashClosings = Schema::hasTable('mz_cash_closings')
            && MozCashClosing::query()
                ->where(function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
                })
                ->exists();

        if (! $hasBankTransactions && ! $hasCashClosings) {
            return [
                'key' => 'reconciliation_rules',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_reconciliation_activity',
                'details' => null,
            ];
        }

        return [
            'key' => 'reconciliation_rules',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'bank_transactions' => $hasBankTransactions,
                'cash_closings' => $hasCashClosings,
            ],
        ];
    }

    private function buildConfigBlockMessage(array $check): string
    {
        return match ($check['reason'] ?? 'unknown') {
            'missing_fields' => __('Critical configuration is incomplete.'),
            'missing_profile' => __('Critical configuration record is missing.'),
            'missing_series' => __('No valid active document series is available.'),
            'period_not_open' => __('No open accounting period exists for today.'),
            'missing_mapping' => __('Tax mapping is missing or incomplete.'),
            'missing_calendar_routes' => __('Fiscal calendar and export routes are unavailable.'),
            'missing_saft_xsd' => __('SAF-T XSD validation is enabled but the schema path is missing.'),
            'missing_customers' => __('No customer masterdata exists.'),
            'missing_products' => __('No product or service masterdata exists.'),
            'missing_accounts' => __('No chart of accounts records exist.'),
            'missing_opening_balances' => __('No opening balances were found.'),
            'missing_closing_checklists' => __('Closing rules have not been generated.'),
            'missing_employees' => __('No employee masterdata exists.'),
            'missing_templates' => __('No contract templates exist.'),
            'missing_payrolls' => __('No payroll calendar entries exist.'),
            'missing_contributions' => __('No payroll contribution rules are configured.'),
            'missing_irps_table' => __('No active IRPS table exists.'),
            'missing_irps_brackets' => __('No IRPS brackets are configured.'),
            'missing_inss_rate' => __('No active INSS rate exists.'),
            'missing_warehouses' => __('No active warehouse exists.'),
            'missing_initial_stock' => __('No initial stock movements were found.'),
            'missing_fifo_layers' => __('No active FIFO layers were found.'),
            'missing_pos_registers' => __('No valid POS register or sale was found.'),
            'missing_leave_types' => __('No leave policy masterdata exists.'),
            'missing_bank_accounts' => __('No active bank or cash account exists.'),
            'missing_cash_accounts' => __('No active cash account exists.'),
            'missing_methods' => __('No supported payment methods are configured.'),
            'missing_reconciliation_activity' => __('No reconciliation activity was found.'),
            'license_expired' => __('The fiscal profile license has expired.'),
            'invalid_nuit', 'invalid_tax_number' => __('A valid NUIT is required.'),
            default => __('Configuration is not ready.'),
        };
    }

    private function buildStepBlockMessage(array $step, float $progressPercent): string
    {
        if ($progressPercent <= 0.0) {
            return __('Required onboarding step is pending.');
        }

        return __('Required onboarding step is only partially completed.');
    }

    private function resolveReadinessState(
        float $overallScore,
        int $criticalBlocksTotal,
        int $availableModulesTotal,
        int $applicableConfigChecksTotal,
        int $readyThreshold,
        int $warningThreshold,
        int $blockedThreshold
    ): string {
        if ($availableModulesTotal === 0 || $applicableConfigChecksTotal === 0) {
            return 'critical';
        }

        if ($criticalBlocksTotal === 0) {
            if ($overallScore >= $readyThreshold) {
                return 'ready';
            }

            if ($overallScore >= $warningThreshold) {
                return 'warning';
            }

            return 'critical';
        }

        if ($overallScore >= $blockedThreshold) {
            return 'blocked';
        }

        return 'critical';
    }

    /**
     * @return array<int, string>
     */
    private function normalizeList(?array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $values ?? []
        ))));
    }

    private function outOfScopeConfigResult(string $key, string $label): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'satisfied' => false,
            'reason' => 'out_of_scope',
            'details' => null,
        ];
    }

    private function missingTableConfigResult(string $key, string $label, string $table): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'satisfied' => false,
            'reason' => 'table_missing',
            'details' => [
                'table' => $table,
            ],
        ];
    }
}
