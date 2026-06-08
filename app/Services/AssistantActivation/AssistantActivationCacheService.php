<?php

namespace App\Services\AssistantActivation;

use App\Models\User;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssistantActivationCacheService
{
    private const FEATURE_CACHE_PREFIX = 'assistant_activation:feature:';
    private const LIMIT_CACHE_PREFIX = 'assistant_activation:limit:';
    private const COMPANY_VERSION_PREFIX = 'assistant_activation:company_version:';
    private const PLAN_VERSION_PREFIX = 'assistant_activation:plan_version:';
    private const MODULE_VERSION_KEY = 'assistant_activation:module_version';

    public function rememberFeature(string $featureKey, ?User $user, Closure $resolver): array
    {
        return Cache::remember(
            $this->featureCacheKey($featureKey, $user),
            now()->addMinutes($this->ttlMinutes()),
            $resolver
        );
    }

    public function rememberLimit(string $limitKey, ?User $user, ?CarbonInterface $referenceDate, Closure $resolver): array
    {
        return Cache::remember(
            $this->limitCacheKey($limitKey, $user, $referenceDate),
            now()->addMinutes($this->ttlMinutes()),
            $resolver
        );
    }

    public function featureCacheKey(string $featureKey, ?User $user): string
    {
        $tenantUser = $this->resolveTenantUser($user);
        $tenantUserId = $tenantUser?->id;
        $planId = $tenantUser?->active_plan ? (int) $tenantUser->active_plan : null;

        $payload = [
            'scope' => 'feature',
            'catalog_version' => (string) config('assistant_activation_features.catalog_version', 'unknown'),
            'feature_key' => $featureKey,
            'subject_user_id' => $user?->id,
            'tenant_user_id' => $tenantUserId,
            'company_version' => $tenantUserId ? $this->currentCompanyVersion($tenantUserId) : 'guest',
            'plan_version' => $planId ? $this->currentPlanVersion($planId) : 'none',
            'module_version' => $this->currentModuleVersion(),
        ];

        return self::FEATURE_CACHE_PREFIX . hash('sha256', json_encode($payload));
    }

    public function limitCacheKey(string $limitKey, ?User $user, ?CarbonInterface $referenceDate): string
    {
        $tenantUser = $this->resolveTenantUser($user);
        $tenantUserId = $tenantUser?->id;
        $planId = $tenantUser?->active_plan ? (int) $tenantUser->active_plan : null;

        $payload = [
            'scope' => 'limit',
            'catalog_version' => (string) config('assistant_activation_limits.catalog_version', 'unknown'),
            'limit_key' => $limitKey,
            'reference_date' => $referenceDate?->toDateString(),
            'subject_user_id' => $user?->id,
            'tenant_user_id' => $tenantUserId,
            'company_version' => $tenantUserId ? $this->currentCompanyVersion($tenantUserId) : 'guest',
            'plan_version' => $planId ? $this->currentPlanVersion($planId) : 'none',
            'module_version' => $this->currentModuleVersion(),
        ];

        return self::LIMIT_CACHE_PREFIX . hash('sha256', json_encode($payload));
    }

    public function currentCompanyVersion(int $companyId): string
    {
        return $this->currentVersion($this->companyVersionKey($companyId));
    }

    public function touchCompanyVersion(int $companyId): string
    {
        return $this->touchVersion($this->companyVersionKey($companyId));
    }

    public function currentPlanVersion(int $planId): string
    {
        return $this->currentVersion($this->planVersionKey($planId));
    }

    public function touchPlanVersion(int $planId): string
    {
        return $this->touchVersion($this->planVersionKey($planId));
    }

    public function currentModuleVersion(): string
    {
        return $this->currentVersion(self::MODULE_VERSION_KEY);
    }

    public function touchModuleVersion(): string
    {
        return $this->touchVersion(self::MODULE_VERSION_KEY);
    }

    public function touchUserCompanyVersion(?User $user): ?string
    {
        $companyId = $this->resolveCompanyId($user);

        if ($companyId === null) {
            return null;
        }

        return $this->touchCompanyVersion($companyId);
    }

    private function resolveTenantUser(?User $user): ?User
    {
        if (! $user) {
            return null;
        }

        if ($user->isSuperAdminUser() || $user->type === 'company') {
            return $user;
        }

        return $user->createdBy ?: $user;
    }

    private function resolveCompanyId(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        if ($user->isSuperAdminUser() || $user->type === 'company') {
            return (int) $user->id;
        }

        return (int) ($user->created_by ?: $user->id);
    }

    private function companyVersionKey(int $companyId): string
    {
        return self::COMPANY_VERSION_PREFIX . $companyId;
    }

    private function planVersionKey(int $planId): string
    {
        return self::PLAN_VERSION_PREFIX . $planId;
    }

    private function currentVersion(string $cacheKey): string
    {
        $version = Cache::get($cacheKey);

        if (! is_string($version) || $version === '') {
            $version = (string) Str::uuid();
            Cache::forever($cacheKey, $version);
        }

        return $version;
    }

    private function touchVersion(string $cacheKey): string
    {
        $version = (string) Str::uuid();
        Cache::forever($cacheKey, $version);

        return $version;
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('assistant_activation.cache.ttl_minutes', 15));
    }
}
