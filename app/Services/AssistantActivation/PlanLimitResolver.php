<?php

namespace App\Services\AssistantActivation;

use App\Models\Plan;
use App\Models\FiscalDocumentSeries;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use App\Services\AssistantActivation\TenantFeatureOverrideService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Employee;
use Workdo\Pos\Models\Pos;

class PlanLimitResolver
{
    private const NEAR_LIMIT_PERCENT = 80;

    public function __construct(
        private readonly PlanLimitMatrixService $limitMatrixService,
        private readonly TenantUsageService $tenantUsageService,
        private readonly TenantFeatureOverrideService $tenantFeatureOverrideService,
        private readonly AssistantActivationCacheService $cacheService
    ) {
    }

    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_limits.catalog_version', 'unknown');
    }

    public function dimensions(): array
    {
        $dimensions = (array) config('assistant_activation_limits.dimensions', []);

        return array_values(array_map(function (array $dimension): array {
            return $this->normalizeDimension($dimension);
        }, $dimensions));
    }

    public function indexedDimensions(): array
    {
        return collect($this->dimensions())
            ->keyBy('key')
            ->all();
    }

    public function find(string $limitKey): ?array
    {
        return $this->indexedDimensions()[$limitKey] ?? null;
    }

    public function buildCatalogReport(): array
    {
        $dimensions = $this->dimensions();
        $limitMatrix = $this->limitMatrixService->buildReport();

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'dimensions_total' => count($dimensions),
                'plan_families_total' => $limitMatrix['summary']['families_total'],
                'plan_field_dimensions_total' => collect($dimensions)->where('source', 'plan_field')->count(),
                'contract_dimensions_total' => collect($dimensions)->where('source', 'contract')->count(),
            ],
            'dimensions' => $dimensions,
            'plan_families' => $limitMatrix['families'],
        ];
    }

    public function resolve(string $limitKey, ?User $user = null, ?CarbonInterface $referenceDate = null): array
    {
        $dimension = $this->find($limitKey);

        if ($dimension === null) {
            return $this->buildMissingLimitResolution($limitKey, $user);
        }

        $subjectUser = $user ?? Auth::user();
        if (! $subjectUser) {
            return $this->buildNoUserResolution($dimension);
        }

        return $this->cacheService->rememberLimit(
            $limitKey,
            $subjectUser,
            $referenceDate,
            function () use ($dimension, $limitKey, $subjectUser, $referenceDate): array {
                if ($subjectUser->isSuperAdminUser()) {
                    return $this->buildSuperAdminResolution($dimension, $subjectUser, $referenceDate);
                }

                $tenantUser = $this->resolveTenantUser($subjectUser);
                if (! $tenantUser) {
                    return $this->buildMissingTenantResolution($dimension, $subjectUser);
                }

                $subscription = $this->resolveSubscriptionState($tenantUser);
                $override = $this->tenantFeatureOverrideService->resolveLimitOverride($tenantUser, $limitKey);

                if ($override !== null) {
                    return $this->buildOverrideResolution(
                        $dimension,
                        $subscription,
                        $tenantUser,
                        $referenceDate ?? now(),
                        $override
                    );
                }

                $planSnapshot = $this->resolvePlanSnapshot($subscription['plan_id']);
                $familyLimits = $this->limitMatrixService->resolveFamilyLimits(
                    $subscription['plan_family'],
                    $planSnapshot
                );

                $contract = $familyLimits['limits'][$limitKey] ?? null;
                if ($contract === null) {
                    return $this->buildMissingLimitResolution($limitKey, $subjectUser);
                }

                $usage = $this->resolveUsage($limitKey, $tenantUser, $referenceDate ?? now());
                $state = $this->resolveState((int) $contract['value'], $usage['current_usage']);

                return [
                    'key' => $dimension['key'],
                    'label' => $dimension['label'],
                    'description' => $dimension['description'],
                    'unit' => $dimension['unit'],
                    'source' => $dimension['source'],
                    'field' => $dimension['field'],
                    'enforcement' => $dimension['enforcement'],
                    'contracted_limit' => (int) $contract['value'],
                    'contracted_limit_display' => $this->formatLimitValue($contract['value']),
                    'current_usage' => $usage['current_usage'],
                    'remaining' => $state['remaining'],
                    'usage_percent' => $state['usage_percent'],
                    'threshold_percent' => self::NEAR_LIMIT_PERCENT,
                    'state' => $state['state'],
                    'unlimited' => $state['unlimited'],
                    'plan_family' => $subscription['plan_family'],
                    'plan_name' => $subscription['plan_name'],
                    'plan_id' => $subscription['plan_id'],
                    'subscription_state' => $subscription['state'],
                    'subscription' => $subscription,
                    'resolved_from' => $contract['resolved_from'] ?? 'default',
                    'contract_details' => $contract,
                    'usage_breakdown' => $usage['breakdown'],
                    'reasons' => $this->resolveReasons($subscription['state']),
                ];
            }
        );
    }

    /**
     * @return array{meta:array<string,mixed>,summary:array<string,mixed>,dimensions:array<int,array<string,mixed>>}
     */
    public function buildReport(?User $user = null, ?CarbonInterface $referenceDate = null): array
    {
        $resolvedDimensions = array_map(
            fn (array $dimension) => $this->resolve($dimension['key'], $user, $referenceDate),
            $this->dimensions()
        );

        $stateCounts = collect($resolvedDimensions)->countBy('state')->all();
        $subscriptionStateCounts = collect($resolvedDimensions)->countBy('subscription_state')->all();

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'dimensions_total' => count($resolvedDimensions),
                'within_limit_total' => $stateCounts['within_limit'] ?? 0,
                'near_limit_total' => $stateCounts['near_limit'] ?? 0,
                'exceeded_total' => $stateCounts['exceeded'] ?? 0,
                'unlimited_total' => collect($resolvedDimensions)->where('unlimited', true)->count(),
                'subscription_state_counts' => $subscriptionStateCounts,
            ],
            'dimensions' => $resolvedDimensions,
        ];
    }

    private function buildMissingLimitResolution(string $limitKey, ?User $user): array
    {
        return [
            'key' => $limitKey,
            'label' => $this->humanizeKey($limitKey),
            'description' => '',
            'unit' => '',
            'source' => 'unknown',
            'field' => null,
            'enforcement' => 'manual',
            'contracted_limit' => null,
            'contracted_limit_display' => null,
            'current_usage' => 0,
            'remaining' => null,
            'usage_percent' => null,
            'threshold_percent' => self::NEAR_LIMIT_PERCENT,
            'state' => 'hidden',
            'unlimited' => false,
            'plan_family' => null,
            'plan_name' => null,
            'plan_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'resolved_from' => null,
            'contract_details' => null,
            'usage_breakdown' => [],
            'reasons' => ['limit_unknown'],
            'subject_user_id' => $user?->id,
            'tenant_user_id' => null,
        ];
    }

    private function buildOverrideResolution(
        array $dimension,
        array $subscription,
        User $tenantUser,
        CarbonInterface $referenceDate,
        array $override
    ): array {
        $usage = $this->resolveUsage($dimension['key'], $tenantUser, $referenceDate);
        $contractedLimit = array_key_exists('limit_value', $override) && $override['limit_value'] !== null
            ? (int) $override['limit_value']
            : -1;
        $state = $this->resolveState($contractedLimit, $usage['current_usage']);

        return [
            'key' => $dimension['key'],
            'label' => $dimension['label'],
            'description' => $dimension['description'],
            'unit' => $dimension['unit'],
            'source' => $dimension['source'],
            'field' => $dimension['field'],
            'enforcement' => $dimension['enforcement'],
            'contracted_limit' => $contractedLimit,
            'contracted_limit_display' => $this->formatLimitValue($contractedLimit),
            'current_usage' => $usage['current_usage'],
            'remaining' => $state['remaining'],
            'usage_percent' => $state['usage_percent'],
            'threshold_percent' => self::NEAR_LIMIT_PERCENT,
            'state' => $state['state'],
            'unlimited' => $state['unlimited'],
            'plan_family' => $subscription['plan_family'],
            'plan_name' => $subscription['plan_name'],
            'plan_id' => $subscription['plan_id'],
            'subscription_state' => $subscription['state'],
            'subscription' => $subscription,
            'resolved_from' => 'override',
            'contract_details' => $override,
            'usage_breakdown' => $usage['breakdown'],
            'reasons' => ['tenant_override'],
            'override' => $override,
        ];
    }

    private function buildNoUserResolution(array $dimension): array
    {
        return [
            'key' => $dimension['key'],
            'label' => $dimension['label'],
            'description' => $dimension['description'],
            'unit' => $dimension['unit'],
            'source' => $dimension['source'],
            'field' => $dimension['field'],
            'enforcement' => $dimension['enforcement'],
            'contracted_limit' => null,
            'contracted_limit_display' => null,
            'current_usage' => 0,
            'remaining' => null,
            'usage_percent' => null,
            'threshold_percent' => self::NEAR_LIMIT_PERCENT,
            'state' => 'hidden',
            'unlimited' => false,
            'plan_family' => null,
            'plan_name' => null,
            'plan_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'resolved_from' => null,
            'contract_details' => null,
            'usage_breakdown' => [],
            'reasons' => ['no_user_context'],
            'subject_user_id' => null,
            'tenant_user_id' => null,
        ];
    }

    private function buildMissingTenantResolution(array $dimension, User $user): array
    {
        return [
            'key' => $dimension['key'],
            'label' => $dimension['label'],
            'description' => $dimension['description'],
            'unit' => $dimension['unit'],
            'source' => $dimension['source'],
            'field' => $dimension['field'],
            'enforcement' => $dimension['enforcement'],
            'contracted_limit' => null,
            'contracted_limit_display' => null,
            'current_usage' => 0,
            'remaining' => null,
            'usage_percent' => null,
            'threshold_percent' => self::NEAR_LIMIT_PERCENT,
            'state' => 'hidden',
            'unlimited' => false,
            'plan_family' => null,
            'plan_name' => null,
            'plan_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'resolved_from' => null,
            'contract_details' => null,
            'usage_breakdown' => [],
            'reasons' => ['tenant_context_missing'],
            'subject_user_id' => $user->id,
            'tenant_user_id' => null,
        ];
    }

    private function buildSuperAdminResolution(array $dimension, User $user, ?CarbonInterface $referenceDate = null): array
    {
        $usage = $this->resolveUsage($dimension['key'], $user, $referenceDate ?? now());

        return [
            'key' => $dimension['key'],
            'label' => $dimension['label'],
            'description' => $dimension['description'],
            'unit' => $dimension['unit'],
            'source' => $dimension['source'],
            'field' => $dimension['field'],
            'enforcement' => $dimension['enforcement'],
            'contracted_limit' => -1,
            'contracted_limit_display' => 'unlimited',
            'current_usage' => $usage['current_usage'],
            'remaining' => null,
            'usage_percent' => null,
            'threshold_percent' => self::NEAR_LIMIT_PERCENT,
            'state' => 'within_limit',
            'unlimited' => true,
            'plan_family' => 'enterprise',
            'plan_name' => 'Super Admin',
            'plan_id' => null,
            'subscription_state' => 'superadmin',
            'subscription' => [
                'state' => 'superadmin',
                'plan_id' => null,
                'plan_name' => 'Super Admin',
                'plan_family' => 'enterprise',
                'plan_expire_date' => null,
                'trial_expire_date' => null,
            ],
            'resolved_from' => 'default',
            'contract_details' => null,
            'usage_breakdown' => $usage['breakdown'],
            'reasons' => ['superadmin_bypass'],
            'subject_user_id' => $user->id,
            'tenant_user_id' => $user->id,
        ];
    }

    private function resolveTenantUser(User $user): ?User
    {
        if (in_array($user->type, ['company', 'superadmin', 'super admin'], true)) {
            return $user;
        }

        return $user->createdBy;
    }

    private function resolveSubscriptionState(User $tenantUser): array
    {
        if ($tenantUser->isSuperAdminUser()) {
            return [
                'state' => 'superadmin',
                'plan_id' => null,
                'plan_name' => 'Super Admin',
                'plan_family' => 'enterprise',
                'plan_expire_date' => null,
                'trial_expire_date' => null,
            ];
        }

        if ((int) $tenantUser->active_plan <= 0) {
            return [
                'state' => 'inactive',
                'plan_id' => null,
                'plan_name' => null,
                'plan_family' => 'custom',
                'plan_expire_date' => null,
                'trial_expire_date' => $tenantUser->trial_expire_date,
            ];
        }

        $plan = Plan::find($tenantUser->active_plan);
        $planFamily = $this->normalizePlanFamily($plan?->name);

        if ($tenantUser->plan_expire_date && now()->gt($tenantUser->plan_expire_date)) {
            return [
                'state' => 'expired',
                'plan_id' => $tenantUser->active_plan,
                'plan_name' => $plan?->name,
                'plan_family' => $planFamily,
                'plan_expire_date' => $tenantUser->plan_expire_date,
                'trial_expire_date' => $tenantUser->trial_expire_date,
            ];
        }

        return [
            'state' => 'active',
            'plan_id' => $tenantUser->active_plan,
            'plan_name' => $plan?->name,
            'plan_family' => $planFamily,
            'plan_expire_date' => $tenantUser->plan_expire_date,
            'trial_expire_date' => $tenantUser->trial_expire_date,
        ];
    }

    private function resolvePlanSnapshot(?int $planId): ?array
    {
        if ($planId === null) {
            return null;
        }

        $plan = Plan::find($planId);
        if (! $plan) {
            return null;
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'users_limit' => (int) $plan->number_of_users,
            'storage_limit_kb' => (int) $plan->storage_limit,
        ];
    }

    private function resolveUsage(string $limitKey, User $companyUser, CarbonInterface $referenceDate): array
    {
        return $this->tenantUsageService->resolve($limitKey, $companyUser, $referenceDate);
    }

    private function resolveUserUsage(User $companyUser): array
    {
        return [
            'current_usage' => User::query()
                ->where('created_by', $companyUser->id)
                ->where('is_disable', 0)
                ->count(),
            'breakdown' => [
                'active_users' => User::query()
                    ->where('created_by', $companyUser->id)
                    ->where('is_disable', 0)
                    ->count(),
            ],
        ];
    }

    private function resolveStorageUsage(User $companyUser): array
    {
        if (! Schema::hasTable('media')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'bytes' => 0,
                    'kb' => 0,
                    'reason' => 'table_missing',
                ],
            ];
        }

        $bytes = (int) Media::query()
            ->where('created_by', $companyUser->id)
            ->sum('size');

        return [
            'current_usage' => (int) ceil($bytes / 1024),
            'breakdown' => [
                'bytes' => $bytes,
                'kb' => (int) ceil($bytes / 1024),
            ],
        ];
    }

    private function resolveDocumentSeriesUsage(User $companyUser): array
    {
        if (! Schema::hasTable('fiscal_document_series')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $count = FiscalDocumentSeries::query()
            ->where('company_id', $companyUser->id)
            ->where('is_active', true)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'active_series' => $count,
            ],
        ];
    }

    private function resolveBranchUsage(User $companyUser): array
    {
        if (! Schema::hasTable('branches')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $count = Branch::query()
            ->where('created_by', $companyUser->id)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'branches' => $count,
            ],
        ];
    }

    private function resolveWarehouseUsage(User $companyUser): array
    {
        if (! Schema::hasTable('warehouses')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $count = Warehouse::query()
            ->where('created_by', $companyUser->id)
            ->where('is_active', true)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'active_warehouses' => $count,
            ],
        ];
    }

    private function resolvePosUsage(User $companyUser): array
    {
        if (! Schema::hasTable('pos')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $query = Pos::query()->where('created_by', $companyUser->id);
        $distinctWarehouses = (clone $query)
            ->whereNotNull('warehouse_id')
            ->distinct()
            ->count('warehouse_id');

        $count = $distinctWarehouses > 0 ? $distinctWarehouses : $query->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'distinct_warehouses' => $distinctWarehouses,
                'pos_records' => $query->count(),
            ],
        ];
    }

    private function resolveEmployeeUsage(User $companyUser): array
    {
        if (! Schema::hasTable('employees')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $count = Employee::query()
            ->where('created_by', $companyUser->id)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'employees' => $count,
            ],
        ];
    }

    private function resolveMonthlyDocumentUsage(User $companyUser, CarbonInterface $referenceDate): array
    {
        $windowStart = $referenceDate->copy()->startOfMonth()->toDateString();
        $windowEnd = $referenceDate->copy()->endOfMonth()->toDateString();

        $breakdown = [
            'sales_invoices' => $this->countDateScopedRecords(SalesInvoice::class, 'invoice_date', $companyUser->id, $windowStart, $windowEnd),
            'purchase_invoices' => $this->countDateScopedRecords(PurchaseInvoice::class, 'invoice_date', $companyUser->id, $windowStart, $windowEnd),
            'sales_returns' => $this->countDateScopedRecords(SalesInvoiceReturn::class, 'return_date', $companyUser->id, $windowStart, $windowEnd),
            'purchase_returns' => $this->countDateScopedRecords(PurchaseReturn::class, 'return_date', $companyUser->id, $windowStart, $windowEnd),
            'credit_notes' => $this->countDateScopedRecords(CreditNote::class, 'credit_note_date', $companyUser->id, $windowStart, $windowEnd),
            'debit_notes' => $this->countDateScopedRecords(DebitNote::class, 'debit_note_date', $companyUser->id, $windowStart, $windowEnd),
            'pos' => $this->countDateScopedRecords(Pos::class, 'pos_date', $companyUser->id, $windowStart, $windowEnd),
            'customer_payments' => $this->countDateScopedRecords(CustomerPayment::class, 'payment_date', $companyUser->id, $windowStart, $windowEnd),
            'vendor_payments' => $this->countDateScopedRecords(VendorPayment::class, 'payment_date', $companyUser->id, $windowStart, $windowEnd),
        ];

        return [
            'current_usage' => array_sum($breakdown),
            'breakdown' => $breakdown + [
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
            ],
        ];
    }

    private function resolveBankAccountUsage(User $companyUser): array
    {
        if (! Schema::hasTable('bank_accounts')) {
            return [
                'current_usage' => 0,
                'breakdown' => [
                    'reason' => 'table_missing',
                ],
            ];
        }

        $count = BankAccount::query()
            ->where('created_by', $companyUser->id)
            ->where('is_active', true)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'active_bank_accounts' => $count,
            ],
        ];
    }

    private function countDateScopedRecords(string $modelClass, string $dateColumn, int $companyId, string $startDate, string $endDate): int
    {
        $model = new $modelClass();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn) || ! Schema::hasColumn($table, 'created_by')) {
            return 0;
        }

        return (int) $modelClass::query()
            ->where('created_by', $companyId)
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->count();
    }

    private function resolveState(int $contractedLimit, int $currentUsage): array
    {
        if ($contractedLimit < 0) {
            return [
                'state' => 'within_limit',
                'remaining' => null,
                'usage_percent' => null,
                'unlimited' => true,
            ];
        }

        $remaining = max($contractedLimit - $currentUsage, 0);

        if ($contractedLimit === 0) {
            return [
                'state' => $currentUsage > 0 ? 'exceeded' : 'within_limit',
                'remaining' => $remaining,
                'usage_percent' => $currentUsage > 0 ? 100 : 0,
                'unlimited' => false,
            ];
        }

        if ($currentUsage > $contractedLimit) {
            return [
                'state' => 'exceeded',
                'remaining' => 0,
                'usage_percent' => (int) round(($currentUsage / $contractedLimit) * 100),
                'unlimited' => false,
            ];
        }

        $usagePercent = (int) round(($currentUsage / $contractedLimit) * 100);

        return [
            'state' => $usagePercent >= self::NEAR_LIMIT_PERCENT ? 'near_limit' : 'within_limit',
            'remaining' => $remaining,
            'usage_percent' => $usagePercent,
            'unlimited' => false,
        ];
    }

    private function resolveReasons(string $subscriptionState): array
    {
        return $subscriptionState === 'active'
            ? []
            : ['subscription_' . $subscriptionState];
    }

    private function resolvePlanFamily(?string $planName): string
    {
        $normalized = $this->normalizeLabel($planName);

        foreach ((array) config('assistant_activation.plan_families', []) as $familyKey => $family) {
            $aliases = array_merge(
                [$familyKey],
                (array) ($family['aliases'] ?? []),
                [(string) ($family['label'] ?? '')]
            );

            foreach ($aliases as $alias) {
                $aliasNormalized = $this->normalizeLabel($alias);

                if ($aliasNormalized !== '' && $normalized !== '' && str_contains($normalized, $aliasNormalized)) {
                    return (string) $familyKey;
                }
            }
        }

        return 'custom';
    }

    private function normalizePlanFamily(?string $planName): string
    {
        return $this->resolvePlanFamily($planName);
    }

    private function normalizeDimension(array $dimension): array
    {
        return [
            'key' => (string) Arr::get($dimension, 'key', ''),
            'label' => (string) Arr::get($dimension, 'label', ''),
            'unit' => (string) Arr::get($dimension, 'unit', ''),
            'source' => (string) Arr::get($dimension, 'source', 'contract'),
            'field' => Arr::get($dimension, 'field'),
            'enforcement' => (string) Arr::get($dimension, 'enforcement', 'manual'),
            'description' => (string) Arr::get($dimension, 'description', ''),
        ];
    }

    private function normalizeLabel(?string $label): string
    {
        return Str::of((string) $label)
            ->lower()
            ->replace(['_', '-', '/'], ' ')
            ->squish()
            ->toString();
    }

    private function humanizeKey(string $key): string
    {
        return Str::of($key)->replace(['.', '_', '-'], ' ')->title()->toString();
    }

    private function formatLimitValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'n/a';
        }

        if (is_numeric($value) && (int) $value < 0) {
            return 'unlimited';
        }

        return (string) $value;
    }
}
