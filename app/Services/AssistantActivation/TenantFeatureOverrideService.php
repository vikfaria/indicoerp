<?php

namespace App\Services\AssistantActivation;

use App\Models\TenantFeatureOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TenantFeatureOverrideService
{
    public const TYPE_FEATURE = 'feature';
    public const TYPE_LIMIT = 'limit';

    public function listForCompany(?int $companyId = null): Collection
    {
        return TenantFeatureOverride::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->with([
                'company:id,name,email',
                'creator:id,name,email',
                'updater:id,name,email',
            ])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findFeatureOverride(int $companyId, string $featureKey): ?TenantFeatureOverride
    {
        return $this->findOverride($companyId, self::TYPE_FEATURE, $featureKey);
    }

    public function findLimitOverride(int $companyId, string $limitKey): ?TenantFeatureOverride
    {
        return $this->findOverride($companyId, self::TYPE_LIMIT, $limitKey);
    }

    public function hasFeatureOverride(int $companyId, string $featureKey): bool
    {
        return $this->findFeatureOverride($companyId, $featureKey) !== null;
    }

    public function hasLimitOverride(int $companyId, string $limitKey): bool
    {
        return $this->findLimitOverride($companyId, $limitKey) !== null;
    }

    public function upsert(array $data, ?User $actor = null): TenantFeatureOverride
    {
        $companyId = (int) Arr::get($data, 'company_id');
        $overrideType = (string) Arr::get($data, 'override_type');
        $overrideKey = trim((string) Arr::get($data, 'override_key'));
        $limitValue = Arr::get($data, 'limit_value');
        $notes = trim((string) Arr::get($data, 'notes', ''));
        $overrideId = Arr::get($data, 'id');

        $attributes = [
            'company_id' => $companyId,
            'override_type' => $overrideType,
            'override_key' => $overrideKey,
        ];

        $values = [
            'limit_value' => $this->normalizeLimitValue($overrideType, $limitValue),
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $actor?->id,
        ];

        if ($overrideId) {
            $override = TenantFeatureOverride::query()
                ->where('id', $overrideId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $override->fill($attributes + $values);
            if ($override->created_by === null) {
                $override->created_by = $actor?->id;
            }
            $override->save();
            app(\App\Services\AuditTrailService::class)->record($override->wasRecentlyCreated ? 'created' : 'updated', $override);

            return $override;
        }

        return DB::transaction(function () use ($attributes, $values, $actor): TenantFeatureOverride {
            $override = TenantFeatureOverride::query()->firstOrNew($attributes);

            if (! $override->exists || $override->created_by === null) {
                $override->created_by = $actor?->id;
            }

            $override->fill($values);
            $override->save();
            app(\App\Services\AuditTrailService::class)->record($override->wasRecentlyCreated ? 'created' : 'updated', $override);

            return $override;
        });
    }

    public function delete(TenantFeatureOverride $override): void
    {
        $override->delete();
        app(\App\Services\AuditTrailService::class)->record('deleted', $override);
    }

    public function resolveFeatureOverride(User $tenantUser, string $featureKey): ?array
    {
        $override = $this->findFeatureOverride((int) $tenantUser->id, $featureKey);

        return $override ? $this->toArray($override) : null;
    }

    public function resolveLimitOverride(User $tenantUser, string $limitKey): ?array
    {
        $override = $this->findLimitOverride((int) $tenantUser->id, $limitKey);

        return $override ? $this->toArray($override) : null;
    }

    private function findOverride(int $companyId, string $type, string $key): ?TenantFeatureOverride
    {
        $override = TenantFeatureOverride::query()
            ->where('company_id', $companyId)
            ->where('override_type', $type)
            ->where('override_key', trim($key))
            ->first();

        return $override instanceof TenantFeatureOverride ? $override : null;
    }

    private function normalizeLimitValue(string $overrideType, mixed $limitValue): ?int
    {
        if ($overrideType !== self::TYPE_LIMIT) {
            return null;
        }

        if ($limitValue === null || $limitValue === '') {
            return null;
        }

        return (int) $limitValue;
    }

    private function toArray(TenantFeatureOverride $override): array
    {
        return [
            'id' => $override->id,
            'company_id' => $override->company_id,
            'override_type' => $override->override_type,
            'override_key' => $override->override_key,
            'limit_value' => $override->limit_value,
            'notes' => $override->notes,
            'created_by' => $override->created_by,
            'updated_by' => $override->updated_by,
            'created_at' => $override->created_at?->toIso8601String(),
            'updated_at' => $override->updated_at?->toIso8601String(),
            'company' => $override->relationLoaded('company') ? [
                'id' => $override->company?->id,
                'name' => $override->company?->name,
                'email' => $override->company?->email,
            ] : null,
            'creator' => $override->relationLoaded('creator') ? [
                'id' => $override->creator?->id,
                'name' => $override->creator?->name,
                'email' => $override->creator?->email,
            ] : null,
            'updater' => $override->relationLoaded('updater') ? [
                'id' => $override->updater?->id,
                'name' => $override->updater?->name,
                'email' => $override->updater?->email,
            ] : null,
        ];
    }
}
