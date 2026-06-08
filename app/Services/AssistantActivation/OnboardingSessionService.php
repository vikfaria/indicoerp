<?php

namespace App\Services\AssistantActivation;

use App\Models\OnboardingSession;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;

class OnboardingSessionService
{
    public function activeForCompany(int $companyId): ?OnboardingSession
    {
        return OnboardingSession::query()
            ->forCompany($companyId)
            ->active()
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    public function startForCompany(int $companyId, array $attributes = []): OnboardingSession
    {
        return DB::transaction(function () use ($companyId, $attributes): OnboardingSession {
            $active = OnboardingSession::query()
                ->forCompany($companyId)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($active) {
                $active->forceFill([
                    'last_activity_at' => now(),
                    'current_module_key' => $attributes['current_module_key'] ?? $active->current_module_key,
                    'current_step_key' => $attributes['current_step_key'] ?? $active->current_step_key,
                    'progress_percent' => $attributes['progress_percent'] ?? $active->progress_percent,
                    'metadata' => $this->mergeMetadata($active->metadata ?? [], $attributes['metadata'] ?? []),
                ])->save();

                return $active->refresh();
            }

            $session = OnboardingSession::query()->create($this->buildPayload($companyId, $attributes, 'active'));

            return $session->refresh();
        });
    }

    public function completeForCompany(int $companyId, array $attributes = []): OnboardingSession
    {
        return DB::transaction(function () use ($companyId, $attributes): OnboardingSession {
            $session = OnboardingSession::query()
                ->forCompany($companyId)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $session) {
                $session = OnboardingSession::query()->create($this->buildPayload($companyId, $attributes, 'active'));
            }

            $session->forceFill([
                'status' => 'completed',
                'current_module_key' => $attributes['current_module_key'] ?? $session->current_module_key,
                'current_step_key' => $attributes['current_step_key'] ?? $session->current_step_key,
                'progress_percent' => $attributes['progress_percent'] ?? 100,
                'completed_at' => $attributes['completed_at'] ?? now(),
                'last_activity_at' => $attributes['last_activity_at'] ?? now(),
                'completion_note' => $attributes['completion_note'] ?? $session->completion_note,
                'metadata' => $this->mergeMetadata($session->metadata ?? [], $attributes['metadata'] ?? []),
            ])->save();
            app(AuditTrailService::class)->record('updated', $session);

            return $session->refresh();
        });
    }

    public function abandonForCompany(int $companyId, array $attributes = []): OnboardingSession
    {
        return DB::transaction(function () use ($companyId, $attributes): OnboardingSession {
            $session = OnboardingSession::query()
                ->forCompany($companyId)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $session) {
                return OnboardingSession::query()->create($this->buildPayload($companyId, $attributes, 'abandoned'));
            }

            $session->forceFill([
                'status' => 'abandoned',
                'abandoned_at' => $attributes['abandoned_at'] ?? now(),
                'last_activity_at' => $attributes['last_activity_at'] ?? now(),
                'completion_note' => $attributes['completion_note'] ?? $session->completion_note,
                'metadata' => $this->mergeMetadata($session->metadata ?? [], $attributes['metadata'] ?? []),
            ])->save();

            return $session->refresh();
        });
    }

    public function restartForCompany(int $companyId, array $attributes = []): OnboardingSession
    {
        return DB::transaction(function () use ($companyId, $attributes): OnboardingSession {
            $active = OnboardingSession::query()
                ->forCompany($companyId)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($active) {
                $active->forceFill([
                    'status' => 'abandoned',
                    'abandoned_at' => now(),
                    'last_activity_at' => now(),
                    'metadata' => $this->mergeMetadata($active->metadata ?? [], $attributes['metadata'] ?? []),
                ])->save();
            }

            $session = OnboardingSession::query()->create($this->buildPayload($companyId, $attributes, 'active'));

            return $session->refresh();
        });
    }

    private function buildPayload(int $companyId, array $attributes, string $status): array
    {
        $now = now();
        $metadata = $this->mergeMetadata([], $attributes['metadata'] ?? []);

        $payload = [
            'company_id' => $companyId,
            'status' => $status,
            'current_module_key' => $attributes['current_module_key'] ?? null,
            'current_step_key' => $attributes['current_step_key'] ?? null,
            'progress_percent' => $attributes['progress_percent'] ?? ($status === 'completed' ? 100 : 0),
            'started_at' => $attributes['started_at'] ?? $now,
            'last_activity_at' => $attributes['last_activity_at'] ?? $now,
            'completed_at' => $status === 'completed' ? ($attributes['completed_at'] ?? $now) : ($attributes['completed_at'] ?? null),
            'abandoned_at' => $status === 'abandoned' ? ($attributes['abandoned_at'] ?? $now) : ($attributes['abandoned_at'] ?? null),
            'completion_note' => $attributes['completion_note'] ?? null,
            'metadata' => $metadata ?: null,
            'created_by' => $attributes['created_by'] ?? $companyId,
        ];

        return array_filter($payload, static fn ($value): bool => $value !== null);
    }

    private function mergeMetadata(array $current, array $incoming): array
    {
        return array_replace_recursive($current, $incoming);
    }
}
