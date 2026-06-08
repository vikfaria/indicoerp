<?php

namespace App\Services\AssistantActivation;

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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Employee;
use Workdo\Pos\Models\Pos;

class TenantUsageService
{
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

    public function find(string $usageKey): ?array
    {
        return $this->indexedDimensions()[$usageKey] ?? null;
    }

    public function resolve(string $usageKey, ?User $user = null, ?CarbonInterface $referenceDate = null): array
    {
        $dimension = $this->find($usageKey);

        if ($dimension === null) {
            return $this->buildMissingUsageResolution($usageKey, $user);
        }

        $subjectUser = $user ?? Auth::user();
        if (! $subjectUser) {
            return $this->buildNoUserResolution($dimension);
        }

        if ($subjectUser->isSuperAdminUser()) {
            return $this->buildSuperAdminResolution($dimension, $subjectUser, $referenceDate);
        }

        $tenantUser = $this->resolveTenantUser($subjectUser);
        if (! $tenantUser) {
            return $this->buildMissingTenantResolution($dimension, $subjectUser);
        }

        $usage = $this->resolveUsage($usageKey, $tenantUser, $referenceDate ?? now());

        return [
            'key' => $dimension['key'],
            'label' => $dimension['label'],
            'description' => $dimension['description'],
            'unit' => $dimension['unit'],
            'source' => $dimension['source'],
            'field' => $dimension['field'],
            'enforcement' => $dimension['enforcement'],
            'current_usage' => $usage['current_usage'],
            'breakdown' => $usage['breakdown'],
            'usage_breakdown' => $usage['breakdown'],
            'subject_user_id' => $subjectUser->id,
            'tenant_user_id' => $tenantUser->id,
        ];
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

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'dimensions_total' => count($resolvedDimensions),
                'current_usage_total' => collect($resolvedDimensions)->sum('current_usage'),
            ],
            'dimensions' => $resolvedDimensions,
        ];
    }

    private function buildMissingUsageResolution(string $usageKey, ?User $user): array
    {
        return [
            'key' => $usageKey,
            'label' => $this->humanizeKey($usageKey),
            'description' => '',
            'unit' => '',
            'source' => 'unknown',
            'field' => null,
            'enforcement' => 'manual',
            'current_usage' => 0,
            'breakdown' => [],
            'usage_breakdown' => [],
            'subject_user_id' => $user?->id,
            'tenant_user_id' => null,
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
            'current_usage' => 0,
            'breakdown' => [],
            'usage_breakdown' => [],
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
            'current_usage' => 0,
            'breakdown' => [],
            'usage_breakdown' => [],
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
            'current_usage' => $usage['current_usage'],
            'breakdown' => $usage['breakdown'],
            'usage_breakdown' => $usage['breakdown'],
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

    private function resolveUsage(string $usageKey, User $companyUser, CarbonInterface $referenceDate): array
    {
        return match ($usageKey) {
            'users' => $this->resolveUserUsage($companyUser),
            'storage_kb' => $this->resolveStorageUsage($companyUser),
            'companies' => [
                'current_usage' => 1,
                'breakdown' => [
                    'company_count' => 1,
                ],
            ],
            'document_series' => $this->resolveDocumentSeriesUsage($companyUser),
            'branches' => $this->resolveBranchUsage($companyUser),
            'warehouses' => $this->resolveWarehouseUsage($companyUser),
            'pos_registers' => $this->resolvePosUsage($companyUser),
            'employees' => $this->resolveEmployeeUsage($companyUser),
            'documents_per_month' => $this->resolveMonthlyDocumentUsage($companyUser, $referenceDate),
            'bank_accounts' => $this->resolveBankAccountUsage($companyUser),
            default => [
                'current_usage' => 0,
                'breakdown' => [],
            ],
        };
    }

    private function resolveUserUsage(User $companyUser): array
    {
        $count = User::query()
            ->where('created_by', $companyUser->id)
            ->where('is_disable', 0)
            ->count();

        return [
            'current_usage' => $count,
            'breakdown' => [
                'active_users' => $count,
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

    private function humanizeKey(string $key): string
    {
        return Str::of($key)->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
