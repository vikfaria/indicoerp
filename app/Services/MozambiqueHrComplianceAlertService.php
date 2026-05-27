<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Workdo\Hrm\Models\HrComplianceAlert;

class MozambiqueHrComplianceAlertService
{
    public function __construct(
        private readonly MozambiqueHrComplianceDashboardService $complianceDashboardService
    ) {}

    public function syncFromSnapshot(int $companyId, ?array $snapshot = null): array
    {
        $snapshot = $snapshot ?? $this->complianceDashboardService->snapshot($companyId);
        $items = collect((array) data_get($snapshot, 'items', []))
            ->map(fn (mixed $item): ?array => $this->normalizeItem($item))
            ->filter()
            ->values();

        $now = now();
        $keysInSnapshot = $items->pluck('key')->filter()->values()->all();

        DB::transaction(function () use ($companyId, $items, $keysInSnapshot, $now): void {
            $existingByKey = HrComplianceAlert::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->get()
                ->keyBy('alert_key');

            foreach ($items as $item) {
                $alert = $existingByKey->get($item['key']);

                if ($item['count'] > 0) {
                    if ($alert === null) {
                        HrComplianceAlert::query()->create([
                            'company_id' => $companyId,
                            'alert_key' => $item['key'],
                            'label' => $item['label'],
                            'severity' => $item['severity'],
                            'count' => $item['count'],
                            'status' => HrComplianceAlert::STATUS_OPEN,
                            'times_triggered' => 1,
                            'first_detected_at' => $now,
                            'last_detected_at' => $now,
                            'last_snapshot_at' => $now,
                            'resolved_at' => null,
                            'payload' => $item,
                        ]);
                        continue;
                    }

                    $wasResolved = $alert->status === HrComplianceAlert::STATUS_RESOLVED;
                    $timesTriggered = max(1, (int) $alert->times_triggered);
                    if ($wasResolved) {
                        $timesTriggered++;
                    }

                    $alert->fill([
                        'label' => $item['label'],
                        'severity' => $item['severity'],
                        'count' => $item['count'],
                        'status' => HrComplianceAlert::STATUS_OPEN,
                        'times_triggered' => $timesTriggered,
                        'first_detected_at' => $alert->first_detected_at ?? $now,
                        'last_detected_at' => $now,
                        'last_snapshot_at' => $now,
                        'resolved_at' => null,
                        'payload' => $item,
                    ])->save();

                    continue;
                }

                if ($alert !== null && $alert->status === HrComplianceAlert::STATUS_OPEN) {
                    $alert->fill([
                        'label' => $item['label'],
                        'severity' => $item['severity'],
                        'count' => 0,
                        'status' => HrComplianceAlert::STATUS_RESOLVED,
                        'resolved_at' => $now,
                        'last_snapshot_at' => $now,
                        'payload' => $item,
                    ])->save();
                }
            }

            $staleOpenAlerts = HrComplianceAlert::query()
                ->where('company_id', $companyId)
                ->where('status', HrComplianceAlert::STATUS_OPEN)
                ->when(
                    !empty($keysInSnapshot),
                    fn ($query) => $query->whereNotIn('alert_key', $keysInSnapshot)
                )
                ->get();

            foreach ($staleOpenAlerts as $staleAlert) {
                $staleAlert->fill([
                    'count' => 0,
                    'status' => HrComplianceAlert::STATUS_RESOLVED,
                    'resolved_at' => $now,
                    'last_snapshot_at' => $now,
                ])->save();
            }
        });

        return $this->buildState($companyId, $snapshot, $now->toIso8601String());
    }

    public function buildState(int $companyId, ?array $snapshot = null, ?string $syncedAt = null): array
    {
        $openAlerts = HrComplianceAlert::query()
            ->where('company_id', $companyId)
            ->where('status', HrComplianceAlert::STATUS_OPEN)
            ->orderByRaw("case when severity = 'high' then 0 else 1 end")
            ->orderByDesc('count')
            ->orderBy('alert_key')
            ->get();

        $recentlyResolved = HrComplianceAlert::query()
            ->where('company_id', $companyId)
            ->where('status', HrComplianceAlert::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->orderByDesc('resolved_at')
            ->limit(25)
            ->get();

        return [
            'synced_at' => $syncedAt ?? now()->toIso8601String(),
            'source_generated_at' => data_get($snapshot, 'generated_at'),
            'metrics' => [
                'open_alerts' => $openAlerts->count(),
                'open_high_alerts' => $openAlerts->where('severity', HrComplianceAlert::SEVERITY_HIGH)->count(),
                'open_medium_alerts' => $openAlerts->where('severity', HrComplianceAlert::SEVERITY_MEDIUM)->count(),
                'resolved_alerts' => HrComplianceAlert::query()
                    ->where('company_id', $companyId)
                    ->where('status', HrComplianceAlert::STATUS_RESOLVED)
                    ->count(),
            ],
            'open' => $openAlerts->map(fn (HrComplianceAlert $alert): array => $this->serializeAlert($alert))->all(),
            'recently_resolved' => $recentlyResolved->map(fn (HrComplianceAlert $alert): array => $this->serializeAlert($alert))->all(),
        ];
    }

    private function normalizeItem(mixed $item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $key = trim((string) ($item['key'] ?? ''));
        if ($key === '') {
            return null;
        }

        $severity = (string) ($item['severity'] ?? HrComplianceAlert::SEVERITY_MEDIUM);
        if (!in_array($severity, [HrComplianceAlert::SEVERITY_HIGH, HrComplianceAlert::SEVERITY_MEDIUM], true)) {
            $severity = HrComplianceAlert::SEVERITY_MEDIUM;
        }

        return [
            'key' => $key,
            'label' => trim((string) ($item['label'] ?? $key)),
            'count' => max(0, (int) ($item['count'] ?? 0)),
            'severity' => $severity,
        ];
    }

    private function serializeAlert(HrComplianceAlert $alert): array
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
        ];
    }
}

