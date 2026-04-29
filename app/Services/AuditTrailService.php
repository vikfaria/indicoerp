<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuditTrailService
{
    private const IGNORED_ATTRIBUTES = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
        'api_token',
    ];

    /**
     * Persist an immutable audit record for critical model changes.
     */
    public function record(string $event, Model $model): void
    {
        if (! in_array($event, ['created', 'updated', 'deleted'], true)) {
            return;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $payload = $this->buildPayload($event, $model);

        if ($payload === null) {
            return;
        }

        try {
            AuditTrail::query()->create($payload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function buildPayload(string $event, Model $model): ?array
    {
        $oldValues = null;
        $newValues = null;
        $changes = null;

        if ($event === 'created') {
            $newValues = $this->filterAttributes($model->getAttributes());
            $changes = $newValues;
        }

        if ($event === 'updated') {
            $changes = $this->filterAttributes($model->getChanges());

            if ($changes === []) {
                return null;
            }

            $original = $this->filterAttributes($model->getOriginal());
            $oldValues = [];

            foreach (array_keys($changes) as $attribute) {
                if (array_key_exists($attribute, $original)) {
                    $oldValues[$attribute] = $original[$attribute];
                }
            }

            $newValues = $changes;
        }

        if ($event === 'deleted') {
            $oldValues = $this->filterAttributes($model->getOriginal());
        }

        $request = request();
        $route = $request?->route();
        $userId = Auth::id();

        return [
            'company_id' => $this->resolveCompanyId($model),
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            'route' => $route?->getName(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes' => $changes,
        ];
    }

    private function resolveCompanyId(Model $model): ?int
    {
        foreach (['created_by', 'creator_id'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'creatorId')) {
            $creatorId = $user->creatorId();

            if (is_numeric($creatorId)) {
                return (int) $creatorId;
            }
        }

        if (isset($user->created_by) && is_numeric($user->created_by)) {
            return (int) $user->created_by;
        }

        return is_numeric($user->id) ? (int) $user->id : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filterAttributes(array $attributes): array
    {
        $filtered = [];

        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, self::IGNORED_ATTRIBUTES, true)) {
                continue;
            }

            $filtered[$attribute] = $this->normalizeValue($value);
        }

        return $filtered;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            if (strlen($value) > 2000) {
                return substr($value, 0, 2000) . '...[truncated]';
            }

            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeValue($item), $value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }
}
