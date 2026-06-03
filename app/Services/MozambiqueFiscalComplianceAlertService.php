<?php

namespace App\Services;

use App\Models\FiscalComplianceAlert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Services\ReportService;

class MozambiqueFiscalComplianceAlertService
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function snapshot(int $companyId, array $filters = []): array
    {
        return $this->runInCompanyContext($companyId, function () use ($filters): array {
            $snapshot = $this->reportService->getMozambiqueFiscalComplianceAlerts($filters);

            if (!array_key_exists('generated_at', $snapshot)) {
                $snapshot['generated_at'] = now()->toIso8601String();
            }

            return $snapshot;
        });
    }

    public function syncFromReport(int $companyId, array $filters = []): array
    {
        return $this->syncFromSnapshot($companyId, $this->snapshot($companyId, $filters));
    }

    public function syncFromSnapshot(int $companyId, ?array $snapshot = null): array
    {
        $snapshot ??= $this->snapshot($companyId);
        $items = collect((array) data_get($snapshot, 'alerts', data_get($snapshot, 'items', [])))
            ->map(fn (mixed $item): ?array => $this->normalizeItem($item))
            ->filter()
            ->values();

        $now = now();
        $keysInSnapshot = $items->pluck('key')->filter()->values()->all();

        DB::transaction(function () use ($companyId, $items, $keysInSnapshot, $now): void {
            $existingByKey = FiscalComplianceAlert::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->get()
                ->keyBy('alert_key');

            foreach ($items as $item) {
                $alert = $existingByKey->get($item['key']);

                if ($item['count'] > 0) {
                    if ($alert === null) {
                        FiscalComplianceAlert::query()->create([
                            'company_id' => $companyId,
                            'alert_key' => $item['key'],
                            'label' => $item['label'],
                            'severity' => $item['severity'],
                            'count' => $item['count'],
                            'status' => FiscalComplianceAlert::STATUS_OPEN,
                            'times_triggered' => 1,
                            'first_detected_at' => $now,
                            'last_detected_at' => $now,
                            'last_snapshot_at' => $now,
                            'resolved_at' => null,
                            'payload' => $item['payload'],
                        ]);
                        continue;
                    }

                    $wasResolved = $alert->status === FiscalComplianceAlert::STATUS_RESOLVED;
                    $timesTriggered = max(1, (int) $alert->times_triggered);
                    if ($wasResolved) {
                        $timesTriggered++;
                    }

                    $alert->fill([
                        'label' => $item['label'],
                        'severity' => $item['severity'],
                        'count' => $item['count'],
                        'status' => FiscalComplianceAlert::STATUS_OPEN,
                        'times_triggered' => $timesTriggered,
                        'first_detected_at' => $alert->first_detected_at ?? $now,
                        'last_detected_at' => $now,
                        'last_snapshot_at' => $now,
                        'resolved_at' => null,
                        'payload' => $item['payload'],
                    ])->save();

                    continue;
                }

                if ($alert !== null && $alert->status === FiscalComplianceAlert::STATUS_OPEN) {
                    $alert->fill([
                        'label' => $item['label'],
                        'severity' => $item['severity'],
                        'count' => 0,
                        'status' => FiscalComplianceAlert::STATUS_RESOLVED,
                        'resolved_at' => $now,
                        'last_snapshot_at' => $now,
                        'payload' => $item['payload'],
                    ])->save();
                }
            }

            $staleOpenAlerts = FiscalComplianceAlert::query()
                ->where('company_id', $companyId)
                ->where('status', FiscalComplianceAlert::STATUS_OPEN)
                ->when(
                    !empty($keysInSnapshot),
                    fn ($query) => $query->whereNotIn('alert_key', $keysInSnapshot)
                )
                ->get();

            foreach ($staleOpenAlerts as $staleAlert) {
                $staleAlert->fill([
                    'count' => 0,
                    'status' => FiscalComplianceAlert::STATUS_RESOLVED,
                    'resolved_at' => $now,
                    'last_snapshot_at' => $now,
                ])->save();
            }
        });

        return $this->buildState($companyId, $snapshot, $now->toIso8601String());
    }

    public function buildState(int $companyId, ?array $snapshot = null, ?string $syncedAt = null): array
    {
        $openAlerts = FiscalComplianceAlert::query()
            ->where('company_id', $companyId)
            ->where('status', FiscalComplianceAlert::STATUS_OPEN)
            ->orderByRaw("case when severity = 'critical' then 0 when severity = 'high' then 1 when severity = 'medium' then 2 else 3 end")
            ->orderByDesc('count')
            ->orderBy('alert_key')
            ->get();

        $recentlyResolved = FiscalComplianceAlert::query()
            ->where('company_id', $companyId)
            ->where('status', FiscalComplianceAlert::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->orderByDesc('resolved_at')
            ->limit(25)
            ->get();

        return [
            'synced_at' => $syncedAt ?? now()->toIso8601String(),
            'source_generated_at' => data_get($snapshot, 'generated_at'),
            'window' => [
                'from_date' => data_get($snapshot, 'from_date'),
                'to_date' => data_get($snapshot, 'to_date'),
                'due_soon_days' => data_get($snapshot, 'due_soon_days'),
            ],
            'summary' => data_get($snapshot, 'summary', []),
            'metrics' => [
                'open_alerts' => $openAlerts->count(),
                'open_critical_alerts' => $openAlerts->where('severity', FiscalComplianceAlert::SEVERITY_CRITICAL)->count(),
                'open_high_alerts' => $openAlerts->where('severity', FiscalComplianceAlert::SEVERITY_HIGH)->count(),
                'open_medium_alerts' => $openAlerts->where('severity', FiscalComplianceAlert::SEVERITY_MEDIUM)->count(),
                'resolved_alerts' => FiscalComplianceAlert::query()
                    ->where('company_id', $companyId)
                    ->where('status', FiscalComplianceAlert::STATUS_RESOLVED)
                    ->count(),
            ],
            'open' => $openAlerts->map(fn (FiscalComplianceAlert $alert): array => $this->serializeAlert($alert))->all(),
            'recently_resolved' => $recentlyResolved->map(fn (FiscalComplianceAlert $alert): array => $this->serializeAlert($alert))->all(),
        ];
    }

    private function runInCompanyContext(int $companyId, callable $callback): mixed
    {
        $companyUser = User::query()->select(['id', 'type', 'created_by'])->find($companyId);
        if (! $companyUser) {
            throw new \RuntimeException(sprintf('Company user #%d not found.', $companyId));
        }

        if (!in_array($companyUser->type, ['company', 'superadmin'], true)) {
            throw new \RuntimeException(sprintf('User #%d is not a company context.', $companyId));
        }

        $guard = Auth::guard();
        $previousUser = $guard->user();
        $guard->setUser($companyUser);

        try {
            return $callback();
        } finally {
            if ($previousUser instanceof User) {
                $guard->setUser($previousUser);
            }
        }
    }

    private function normalizeItem(mixed $item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $key = trim((string) ($item['code'] ?? $item['key'] ?? ''));
        if ($key === '') {
            return null;
        }

        $severity = (string) ($item['severity'] ?? FiscalComplianceAlert::SEVERITY_MEDIUM);
        if (!in_array($severity, [FiscalComplianceAlert::SEVERITY_CRITICAL, FiscalComplianceAlert::SEVERITY_HIGH, FiscalComplianceAlert::SEVERITY_MEDIUM, FiscalComplianceAlert::SEVERITY_LOW], true)) {
            $severity = FiscalComplianceAlert::SEVERITY_MEDIUM;
        }

        $count = max(0, (int) ($item['count'] ?? 0));
        $message = trim((string) ($item['message'] ?? $item['label'] ?? $key));

        return [
            'key' => $key,
            'label' => $message,
            'count' => $count,
            'severity' => $severity,
            'payload' => [
                'code' => $key,
                'rf' => trim((string) ($item['rf'] ?? '')),
                'severity' => $severity,
                'category' => trim((string) ($item['category'] ?? '')),
                'count' => $count,
                'message' => $message,
                'samples' => array_values((array) ($item['samples'] ?? [])),
                'metadata' => (array) ($item['metadata'] ?? []),
            ],
        ];
    }

    private function serializeAlert(FiscalComplianceAlert $alert): array
    {
        return [
            'key' => (string) $alert->alert_key,
            'label' => (string) $alert->label,
            'severity' => (string) $alert->severity,
            'count' => (int) $alert->count,
            'status' => (string) $alert->status,
            'times_triggered' => (int) $alert->times_triggered,
            'first_detected_at' => $alert->first_detected_at?->toIso8601String(),
            'last_detected_at' => $alert->last_detected_at?->toIso8601String(),
            'resolved_at' => $alert->resolved_at?->toIso8601String(),
            'last_snapshot_at' => $alert->last_snapshot_at?->toIso8601String(),
            'payload' => $alert->payload,
        ];
    }
}
