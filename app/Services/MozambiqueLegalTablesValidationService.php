<?php

namespace App\Services;

use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use App\Models\MzVatCode;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTreatyRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MozambiqueLegalTablesValidationService
{
    private const VALIDATION_STATUS_KEY = 'mz_legal_tables_validation_status';
    private const VALIDATION_COMPLETED_AT_KEY = 'mz_legal_tables_validation_completed_at';
    private const VALIDATION_NOTES_KEY = 'mz_legal_tables_validation_notes';
    private const REVIEW_STATUS_KEY = 'mz_legal_tables_review_status';
    private const REVIEWED_AT_KEY = 'mz_legal_tables_reviewed_at';
    private const REVIEW_NOTES_KEY = 'mz_legal_tables_review_notes';

    /**
     * @return array{company_id:int,generated_at:string,overall_status:string,summary:array<string,int|string>,checks:array<int,array<string,mixed>>,review:array<string,mixed>}
     */
    public function validate(int $companyId): array
    {
        $today = now()->toDateString();
        $checks = [];
        $summary = [
            'pass' => 0,
            'warn' => 0,
            'fail' => 0,
        ];

        foreach ($this->buildChecks($companyId, $today) as $check) {
            $status = strtolower((string) ($check['status'] ?? 'warn'));
            if (!array_key_exists($status, $summary)) {
                continue;
            }

            $summary[$status]++;
            $checks[] = $check;
        }

        $validationStatus = strtolower((string) (company_setting(self::VALIDATION_STATUS_KEY, $companyId) ?? 'not_started'));
        $validationCompletedAt = trim((string) (company_setting(self::VALIDATION_COMPLETED_AT_KEY, $companyId) ?? ''));
        $validationNotes = trim((string) (company_setting(self::VALIDATION_NOTES_KEY, $companyId) ?? ''));
        $reviewStatus = strtolower((string) (company_setting(self::REVIEW_STATUS_KEY, $companyId) ?? 'pending'));
        $reviewedAt = trim((string) (company_setting(self::REVIEWED_AT_KEY, $companyId) ?? ''));
        $reviewNotes = trim((string) (company_setting(self::REVIEW_NOTES_KEY, $companyId) ?? ''));

        $validationCompleted = $validationStatus === 'completed' && $validationCompletedAt !== '';
        $validationExecutionCheckStatus = 'warn';
        if ($validationCompleted) {
            $validationExecutionCheckStatus = 'pass';
        } elseif ($validationStatus === 'in_progress') {
            $validationExecutionCheckStatus = 'warn';
        } elseif ($validationStatus === 'completed' && $validationCompletedAt === '') {
            $validationExecutionCheckStatus = 'fail';
        }

        $checks[] = $this->makeCheck(
            'legal.tables.validation.execution',
            'Legal tables technical validation execution',
            $validationExecutionCheckStatus,
            $validationCompleted
                ? 'Technical validation of VAT, IRPS, INSS, ADT, GIFiM and IME tables is recorded as completed.'
                : ($validationStatus === 'in_progress'
                    ? 'Technical validation of the legal tables is in progress.'
                    : 'Technical validation of the legal tables has not been recorded yet.'),
            false,
            [
                'status' => $validationStatus,
                'completed_at' => $validationCompletedAt !== '' ? $validationCompletedAt : null,
                'notes' => $validationNotes !== '' ? $validationNotes : null,
            ]
        );

        $reviewCheckStatus = 'warn';
        if ($reviewStatus === 'approved' && $reviewedAt !== '') {
            $reviewCheckStatus = 'pass';
        } elseif ($reviewStatus === 'rejected') {
            $reviewCheckStatus = 'fail';
        } elseif ($reviewStatus === 'approved' && $reviewedAt === '') {
            $reviewCheckStatus = 'fail';
        }

        $checks[] = $this->makeCheck(
            'legal.tables.external_review',
            'Legal tables external review approval',
            $reviewCheckStatus,
            $reviewCheckStatus === 'pass'
                ? 'Legal/fiscal/contabilistic review has been approved.'
                : ($reviewStatus === 'rejected'
                    ? 'External review rejected the current legal table configuration.'
                    : 'External legal/fiscal/contabilistic review is pending.'),
            $reviewCheckStatus === 'fail',
            [
                'status' => $reviewStatus,
                'reviewed_at' => $reviewedAt !== '' ? $reviewedAt : null,
                'notes' => $reviewNotes !== '' ? $reviewNotes : null,
            ]
        );

        $summary[$validationExecutionCheckStatus] = ($summary[$validationExecutionCheckStatus] ?? 0) + 1;
        $summary[$reviewCheckStatus] = ($summary[$reviewCheckStatus] ?? 0) + 1;

        $overallStatus = 'ready';
        if ($summary['fail'] > 0) {
            $overallStatus = 'blocked';
        } elseif ($summary['warn'] > 0) {
            $overallStatus = 'attention';
        }

        return [
            'company_id' => $companyId,
            'generated_at' => now()->toDateTimeString(),
            'overall_status' => $overallStatus,
            'summary' => $summary,
            'checks' => $checks,
            'review' => [
                'validation_status' => $validationStatus,
                'validation_completed_at' => $validationCompletedAt !== '' ? $validationCompletedAt : null,
                'validation_notes' => $validationNotes !== '' ? $validationNotes : null,
                'review_status' => $reviewStatus,
                'reviewed_at' => $reviewedAt !== '' ? $reviewedAt : null,
                'review_notes' => $reviewNotes !== '' ? $reviewNotes : null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildChecks(int $companyId, string $today): array
    {
        return array_merge(
            $this->validateVatCodes($today),
            $this->validateIrpsTables($companyId, $today),
            $this->validateInssRates($companyId, $today),
            $this->validateMinimumWages($companyId, $today),
            $this->validateWithholdingRules(),
            $this->validateTreatyRates($companyId, $today),
            $this->validateGifimConfiguration(),
            $this->validateElectronicMoneyConfiguration()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateVatCodes(string $today): array
    {
        if (!Schema::hasTable('mz_vat_codes')) {
            return [
                $this->makeCheck(
                    'legal.tables.vat_codes',
                    'VAT legal table coverage',
                    'fail',
                    'The mz_vat_codes table is missing.',
                    true
                ),
            ];
        }

        $expectedCodes = $this->expectedVatCodes();
        $activeCodes = MzVatCode::query()
            ->where('is_active', true)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->get()
            ->keyBy('code');

        $missingCodes = [];
        $invalidCodes = [];

        foreach ($expectedCodes as $code => $expected) {
            $record = $activeCodes->get($code);
            if ($record === null) {
                $missingCodes[] = $code;
                continue;
            }

            $issues = [];
            $actualRate = round((float) ($record->rate ?? 0), 2);
            $expectedRate = round((float) $expected['rate'], 2);
            if (abs($actualRate - $expectedRate) > 0.01) {
                $issues[] = "rate={$actualRate} expected {$expectedRate}";
            }

            $actualType = strtolower(trim((string) ($record->type ?? '')));
            if ($actualType !== $expected['type']) {
                $issues[] = "type={$actualType} expected {$expected['type']}";
            }

            $actualSaftCode = strtoupper(trim((string) ($record->saft_tax_code ?? '')));
            if ($actualSaftCode !== $expected['saft_tax_code']) {
                $issues[] = "saft_tax_code={$actualSaftCode} expected {$expected['saft_tax_code']}";
            }

            if (trim((string) ($record->description ?? '')) === '') {
                $issues[] = 'description missing';
            }

            if (!empty($issues)) {
                $invalidCodes[$code] = $issues;
            }
        }

        $status = empty($missingCodes) && empty($invalidCodes) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.vat_codes',
                'VAT legal table coverage',
                $status,
                $status === 'pass'
                    ? 'All mandatory VAT legal codes are active and correctly configured.'
                    : 'VAT table validation failed. Missing or invalid codes detected.',
                true,
                [
                    'active_count' => $activeCodes->count(),
                    'expected_count' => count($expectedCodes),
                    'missing_codes' => $missingCodes,
                    'invalid_codes' => $invalidCodes,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateIrpsTables(int $companyId, string $today): array
    {
        if (!Schema::hasTable('mz_irps_tables') || !Schema::hasTable('mz_irps_brackets')) {
            return [
                $this->makeCheck(
                    'legal.tables.irps_table',
                    'IRPS legal table coverage',
                    'fail',
                    'The IRPS tables or brackets table is missing.',
                    true
                ),
            ];
        }

        $activeTables = MozIrpsTable::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        if ($activeTables->isEmpty()) {
            return [
                $this->makeCheck(
                    'legal.tables.irps_table',
                    'IRPS legal table coverage',
                    'fail',
                    'No active IRPS table was found for the current date.',
                    true
                ),
            ];
        }

        if ($activeTables->count() > 1) {
            return [
                $this->makeCheck(
                    'legal.tables.irps_table',
                    'IRPS legal table coverage',
                    'fail',
                    'More than one active IRPS table is effective for the current date.',
                    true,
                    [
                        'active_table_ids' => $activeTables->pluck('id')->all(),
                    ]
                ),
            ];
        }

        $activeTable = $activeTables->first();
        $expectedBrackets = array_values(MozambiquePayrollLegalDefaultsService::IRPS_BRACKETS);
        $actualBrackets = MozIrpsBracket::query()
            ->where('irps_table_id', $activeTable->id)
            ->orderBy('sequence')
            ->get();

        $duplicateSequences = MozIrpsBracket::query()
            ->where('irps_table_id', $activeTable->id)
            ->select('sequence', DB::raw('COUNT(*) as total'))
            ->groupBy('sequence')
            ->having('total', '>', 1)
            ->pluck('sequence')
            ->all();

        $missingSequences = [];
        $invalidBrackets = [];

        foreach ($expectedBrackets as $index => $expected) {
            $record = $actualBrackets->get($index);
            if ($record === null) {
                $missingSequences[] = $expected['sequence'];
                continue;
            }

            $issues = [];
            if ((int) $record->sequence !== (int) $expected['sequence']) {
                $issues[] = "sequence={$record->sequence} expected {$expected['sequence']}";
            }

            $actualRangeFrom = round((float) ($record->range_from ?? 0), 2);
            $expectedRangeFrom = round((float) $expected['range_from'], 2);
            if (abs($actualRangeFrom - $expectedRangeFrom) > 0.01) {
                $issues[] = "range_from={$actualRangeFrom} expected {$expectedRangeFrom}";
            }

            $expectedRangeTo = $expected['range_to'];
            $actualRangeTo = $record->range_to !== null ? round((float) $record->range_to, 2) : null;
            if ($expectedRangeTo === null) {
                if ($actualRangeTo !== null) {
                    $issues[] = 'range_to should be null for the final bracket';
                }
            } elseif ($actualRangeTo === null || abs($actualRangeTo - (float) $expectedRangeTo) > 0.01) {
                $issues[] = 'range_to mismatch';
            }

            $actualFixedAmount = round((float) ($record->fixed_amount ?? 0), 2);
            $expectedFixedAmount = round((float) $expected['fixed_amount'], 2);
            if (abs($actualFixedAmount - $expectedFixedAmount) > 0.01) {
                $issues[] = "fixed_amount={$actualFixedAmount} expected {$expectedFixedAmount}";
            }

            $actualRate = round((float) ($record->rate_percent ?? 0), 4);
            $expectedRate = round((float) $expected['rate_percent'], 4);
            if (abs($actualRate - $expectedRate) > 0.0001) {
                $issues[] = "rate_percent={$actualRate} expected {$expectedRate}";
            }

            if (!empty($issues)) {
                $invalidBrackets[(int) $record->sequence] = $issues;
            }
        }

        $status = empty($missingSequences) && empty($invalidBrackets) && empty($duplicateSequences) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.irps_table',
                'IRPS legal table coverage',
                $status,
                $status === 'pass'
                    ? 'The active IRPS table and its brackets match the official seeded configuration.'
                    : 'IRPS table validation failed. Missing, duplicate or invalid brackets were detected.',
                true,
                [
                    'active_table_id' => (int) $activeTable->id,
                    'active_table_name' => (string) ($activeTable->name ?? ''),
                    'brackets_count' => $actualBrackets->count(),
                    'expected_brackets_count' => count($expectedBrackets),
                    'missing_sequences' => $missingSequences,
                    'duplicate_sequences' => $duplicateSequences,
                    'invalid_brackets' => $invalidBrackets,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateInssRates(int $companyId, string $today): array
    {
        if (!Schema::hasTable('mz_inss_rates')) {
            return [
                $this->makeCheck(
                    'legal.tables.inss_rate',
                    'INSS legal rates',
                    'fail',
                    'The mz_inss_rates table is missing.',
                    true
                ),
            ];
        }

        $activeRates = MozInssRate::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        if ($activeRates->isEmpty()) {
            return [
                $this->makeCheck(
                    'legal.tables.inss_rate',
                    'INSS legal rates',
                    'fail',
                    'No active INSS rate was found for the current date.',
                    true
                ),
            ];
        }

        if ($activeRates->count() > 1) {
            return [
                $this->makeCheck(
                    'legal.tables.inss_rate',
                    'INSS legal rates',
                    'fail',
                    'More than one active INSS rate is effective for the current date.',
                    true,
                    [
                        'active_rate_ids' => $activeRates->pluck('id')->all(),
                    ]
                ),
            ];
        }

        $rate = $activeRates->first();
        $employeeRate = round((float) ($rate->employee_rate ?? 0), 4);
        $employerRate = round((float) ($rate->employer_rate ?? 0), 4);
        $expectedEmployeeRate = 3.0000;
        $expectedEmployerRate = 4.0000;
        $issues = [];

        if (abs($employeeRate - $expectedEmployeeRate) > 0.0001) {
            $issues[] = "employee_rate={$employeeRate} expected {$expectedEmployeeRate}";
        }

        if (abs($employerRate - $expectedEmployerRate) > 0.0001) {
            $issues[] = "employer_rate={$employerRate} expected {$expectedEmployerRate}";
        }

        if (abs(($employeeRate + $employerRate) - 7.0000) > 0.0001) {
            $issues[] = 'combined INSS rate must equal 7.0000';
        }

        $status = empty($issues) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.inss_rate',
                'INSS legal rates',
                $status,
                $status === 'pass'
                    ? 'The active INSS rate matches the expected employee/employer split.'
                    : 'INSS rate validation failed. Employee/employer split is inconsistent.',
                true,
                [
                    'active_rate_id' => (int) $rate->id,
                    'employee_rate' => $employeeRate,
                    'employer_rate' => $employerRate,
                    'issues' => $issues,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateMinimumWages(int $companyId, string $today): array
    {
        if (!Schema::hasTable('mz_minimum_wages')) {
            return [
                $this->makeCheck(
                    'legal.tables.minimum_wages',
                    'Minimum wage legal table',
                    'fail',
                    'The mz_minimum_wages table is missing.',
                    true
                ),
            ];
        }

        $expectedRows = MozambiquePayrollLegalDefaultsService::MINIMUM_WAGES;
        $expectedBySector = [];
        foreach ($expectedRows as $row) {
            $expectedBySector[$row['sector_code']] = $row;
        }

        $activeRows = MozMinimumWage::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderBy('sector_code')
            ->orderByDesc('effective_from')
            ->get();

        $duplicateSectors = MozMinimumWage::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->select('sector_code', DB::raw('COUNT(*) as total'))
            ->groupBy('sector_code')
            ->having('total', '>', 1)
            ->pluck('sector_code')
            ->all();

        $activeBySector = $activeRows->keyBy('sector_code');
        $missingSectors = [];
        $invalidRows = [];

        foreach ($expectedBySector as $sectorCode => $expected) {
            $record = $activeBySector->get($sectorCode);
            if ($record === null) {
                $missingSectors[] = $sectorCode;
                continue;
            }

            $issues = [];
            $actualAmount = round((float) ($record->monthly_amount ?? 0), 2);
            $expectedAmount = round((float) $expected['monthly_amount'], 2);
            if (abs($actualAmount - $expectedAmount) > 0.01) {
                $issues[] = "monthly_amount={$actualAmount} expected {$expectedAmount}";
            }

            $actualName = trim((string) ($record->sector_name ?? ''));
            if ($actualName === '') {
                $issues[] = 'sector_name missing';
            }

            if (!empty($issues)) {
                $invalidRows[$sectorCode] = $issues;
            }
        }

        $status = empty($missingSectors) && empty($invalidRows) && empty($duplicateSectors) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.minimum_wages',
                'Minimum wage legal table',
                $status,
                $status === 'pass'
                    ? 'All minimum wage sector rows are present and match the seeded official configuration.'
                    : 'Minimum wage validation failed. Missing or invalid sector rows were detected.',
                true,
                [
                    'active_rows_count' => $activeRows->count(),
                    'expected_rows_count' => count($expectedBySector),
                    'missing_sectors' => $missingSectors,
                    'duplicate_sectors' => $duplicateSectors,
                    'invalid_rows' => $invalidRows,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateWithholdingRules(): array
    {
        if (!Schema::hasTable('withholding_tax_rules')) {
            return [
                $this->makeCheck(
                    'legal.tables.withholding_rules',
                    'Withholding tax legal rules',
                    'fail',
                    'The withholding_tax_rules table is missing.',
                    true
                ),
            ];
        }

        $expectedRules = $this->expectedWithholdingRules();
        $activeRules = WithholdingTaxRule::query()->where('is_active', true)->get()->keyBy('code');

        $missingCodes = [];
        $invalidRules = [];

        foreach ($expectedRules as $code => $expected) {
            $record = $activeRules->get($code);
            if ($record === null) {
                $missingCodes[] = $code;
                continue;
            }

            $issues = [];
            $actualRate = round((float) ($record->rate ?? 0), 2);
            $expectedRate = round((float) $expected['rate'], 2);
            if (abs($actualRate - $expectedRate) > 0.01) {
                $issues[] = "rate={$actualRate} expected {$expectedRate}";
            }

            $actualAppliesTo = strtolower(trim((string) ($record->applies_to ?? '')));
            if ($actualAppliesTo !== $expected['applies_to']) {
                $issues[] = "applies_to={$actualAppliesTo} expected {$expected['applies_to']}";
            }

            if (trim((string) ($record->name ?? '')) === '') {
                $issues[] = 'name missing';
            }

            if (trim((string) ($record->pgc_debit_account ?? '')) === '' || trim((string) ($record->pgc_credit_account ?? '')) === '') {
                $issues[] = 'PGC debit/credit account missing';
            }

            if (!empty($issues)) {
                $invalidRules[$code] = $issues;
            }
        }

        $status = empty($missingCodes) && empty($invalidRules) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.withholding_rules',
                'Withholding tax legal rules',
                $status,
                $status === 'pass'
                    ? 'All mandatory withholding tax rules are active and consistent.'
                    : 'Withholding tax rule validation failed. Missing or invalid codes were detected.',
                true,
                [
                    'active_count' => $activeRules->count(),
                    'expected_count' => count($expectedRules),
                    'missing_codes' => $missingCodes,
                    'invalid_rules' => $invalidRules,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateTreatyRates(int $companyId, string $today): array
    {
        if (!Schema::hasTable('withholding_tax_treaty_rates')) {
            return [
                $this->makeCheck(
                    'legal.tables.withholding_treaties',
                    'ADT treaty rate configuration',
                    'fail',
                    'The withholding_tax_treaty_rates table is missing.',
                    true
                ),
            ];
        }

        $activeRates = WithholdingTaxTreatyRate::query()
            ->where('created_by', $companyId)
            ->activeAt(now())
            ->orderBy('country_name')
            ->orderBy('income_type')
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->get();

        if ($activeRates->isEmpty()) {
            return [
                $this->makeCheck(
                    'legal.tables.withholding_treaties',
                    'ADT treaty rate configuration',
                    'warn',
                    'No active ADT treaty rates are configured yet. Validate manually if cross-border withholding flows are used.',
                    false,
                    [
                        'active_count' => 0,
                    ]
                ),
            ];
        }

        $duplicateKeys = $activeRates
            ->groupBy(static function (WithholdingTaxTreatyRate $rate): string {
                $country = trim((string) ($rate->country_code ?? $rate->country_name ?? ''));
                $incomeType = strtolower(trim((string) ($rate->income_type ?? '')));

                return $country . '|' . $incomeType;
            })
            ->filter(static fn ($group): bool => $group->count() > 1)
            ->keys()
            ->values()
            ->all();

        $invalidRates = [];
        foreach ($activeRates as $rate) {
            $issues = [];
            $countryCode = strtoupper(trim((string) ($rate->country_code ?? '')));
            $countryName = trim((string) ($rate->country_name ?? ''));
            $incomeType = strtolower(trim((string) ($rate->income_type ?? '')));
            $standardRate = $rate->standard_rate !== null ? round((float) $rate->standard_rate, 4) : null;
            $treatyRate = round((float) ($rate->treaty_rate ?? 0), 4);

            if ($countryCode === '' && $countryName === '') {
                $issues[] = 'country code/name missing';
            }

            if ($incomeType === '') {
                $issues[] = 'income_type missing';
            }

            if ($treatyRate < 0 || $treatyRate > 100) {
                $issues[] = 'treaty_rate must be between 0 and 100';
            }

            if ($standardRate !== null && $treatyRate > $standardRate) {
                $issues[] = "treaty_rate={$treatyRate} cannot exceed standard_rate={$standardRate}";
            }

            if (trim((string) ($rate->legal_basis ?? '')) === '') {
                $issues[] = 'legal_basis missing';
            }

            if ($rate->requires_residency_certificate && trim((string) ($rate->legal_basis ?? '')) === '') {
                $issues[] = 'residency certificate requirement has no legal basis';
            }

            if (!empty($issues)) {
                $invalidRates[] = [
                    'id' => (int) $rate->id,
                    'code' => (string) ($rate->code ?? ''),
                    'issues' => $issues,
                ];
            }
        }

        $status = empty($duplicateKeys) && empty($invalidRates) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.withholding_treaties',
                'ADT treaty rate configuration',
                $status,
                $status === 'pass'
                    ? 'Configured ADT treaty rates are internally consistent.'
                    : 'ADT treaty rate validation failed. Duplicate or invalid rows were detected.',
                true,
                [
                    'active_count' => $activeRates->count(),
                    'duplicate_keys' => $duplicateKeys,
                    'invalid_rates' => $invalidRates,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateGifimConfiguration(): array
    {
        $requiredColumns = [
            'gifim_alert_required',
            'gifim_alert_category',
            'gifim_alert_status',
            'gifim_reference',
            'gifim_reported_at',
            'gifim_reported_by',
            'gifim_submitted_document',
            'gifim_justification',
            'high_value_approval_reference',
        ];

        $missingColumns = [];
        foreach (['customer_payments', 'vendor_payments'] as $table) {
            if (!Schema::hasTable($table)) {
                $missingColumns[$table] = ['table_missing'];
                continue;
            }

            $tableMissingColumns = [];
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $tableMissingColumns[] = $column;
                }
            }

            if (!empty($tableMissingColumns)) {
                $missingColumns[$table] = $tableMissingColumns;
            }
        }

        $cashThreshold = (float) config('sce.gifim.cash_threshold_mzn', 250000);
        $electronicThreshold = (float) config('sce.gifim.electronic_threshold_mzn', 750000);
        $electronicMethods = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) config('sce.gifim.electronic_payment_methods', [])
        )));

        $thresholdIssues = [];
        if ($cashThreshold <= 0) {
            $thresholdIssues[] = 'cash threshold must be positive';
        }
        if ($electronicThreshold <= 0) {
            $thresholdIssues[] = 'electronic threshold must be positive';
        }
        if ($cashThreshold >= $electronicThreshold) {
            $thresholdIssues[] = 'cash threshold must be below electronic threshold';
        }
        if (empty($electronicMethods)) {
            $thresholdIssues[] = 'electronic payment methods list is empty';
        }

        $status = empty($missingColumns) && empty($thresholdIssues) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.gifim_configuration',
                'GIFiM thresholds and payment schema',
                $status,
                $status === 'pass'
                    ? 'GIFiM thresholds are configured and payment tables expose the required communication fields.'
                    : 'GIFiM configuration validation failed. Thresholds or payment columns are missing.',
                true,
                [
                    'cash_threshold_mzn' => $cashThreshold,
                    'electronic_threshold_mzn' => $electronicThreshold,
                    'electronic_payment_methods' => $electronicMethods,
                    'missing_columns' => $missingColumns,
                    'threshold_issues' => $thresholdIssues,
                ]
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateElectronicMoneyConfiguration(): array
    {
        $requiredColumns = [
            'is_electronic_money_account',
            'electronic_money_entity',
            'electronic_money_level',
            'electronic_money_daily_limit_mzn',
            'electronic_money_monthly_limit_mzn',
            'electronic_money_limit_exempt_for_enterprise',
            'electronic_money_account_purpose',
        ];

        if (!Schema::hasTable('bank_accounts')) {
            return [
                $this->makeCheck(
                    'legal.tables.electronic_money_configuration',
                    'Electronic money account schema',
                    'fail',
                    'The bank_accounts table is missing.',
                    true
                ),
            ];
        }

        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('bank_accounts', $column)) {
                $missingColumns[] = $column;
            }
        }

        $status = empty($missingColumns) ? 'pass' : 'fail';

        return [
            $this->makeCheck(
                'legal.tables.electronic_money_configuration',
                'Electronic money account schema',
                $status,
                $status === 'pass'
                    ? 'Bank account electronic money fields are available for IME compliance tracking.'
                    : 'Electronic money account schema validation failed. Required columns are missing.',
                true,
                [
                    'missing_columns' => $missingColumns,
                ]
            ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function expectedVatCodes(): array
    {
        return [
            'NOR' => ['rate' => 16.00, 'type' => 'normal', 'saft_tax_code' => 'NOR'],
            'RED' => ['rate' => 5.00, 'type' => 'normal', 'saft_tax_code' => 'RED'],
            'ISE' => ['rate' => 0.00, 'type' => 'exempt', 'saft_tax_code' => 'ISE'],
            'ZER' => ['rate' => 0.00, 'type' => 'zero', 'saft_tax_code' => 'ZER'],
            'NSU' => ['rate' => 0.00, 'type' => 'not_subject', 'saft_tax_code' => 'NS'],
            'AUT' => ['rate' => 16.00, 'type' => 'reverse_charge', 'saft_tax_code' => 'AUT'],
            'IMP' => ['rate' => 16.00, 'type' => 'import', 'saft_tax_code' => 'IMP'],
            'digital_services' => ['rate' => 16.00, 'type' => 'digital', 'saft_tax_code' => 'DIG'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function expectedWithholdingRules(): array
    {
        return [
            'IRPC-SRV-R' => ['rate' => 10.00, 'applies_to' => 'resident'],
            'IRPC-SRV-NR' => ['rate' => 20.00, 'applies_to' => 'non_resident'],
            'IRPC-REND' => ['rate' => 10.00, 'applies_to' => 'both'],
            'IRPC-ROY' => ['rate' => 20.00, 'applies_to' => 'non_resident'],
            'IRPC-JUR' => ['rate' => 20.00, 'applies_to' => 'both'],
            'IRPC-DIV' => ['rate' => 20.00, 'applies_to' => 'both'],
            'IRPC-COM' => ['rate' => 10.00, 'applies_to' => 'resident'],
            'IRPC-GEST' => ['rate' => 20.00, 'applies_to' => 'non_resident'],
            'IRPC-AT' => ['rate' => 20.00, 'applies_to' => 'non_resident'],
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function makeCheck(
        string $code,
        string $label,
        string $status,
        string $details,
        bool $critical = false,
        array $meta = []
    ): array {
        return [
            'code' => $code,
            'label' => $label,
            'status' => $status,
            'critical' => $critical,
            'details' => $details,
            'meta' => $meta,
        ];
    }
}
