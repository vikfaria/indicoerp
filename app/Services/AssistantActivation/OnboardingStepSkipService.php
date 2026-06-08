<?php

namespace App\Services\AssistantActivation;

use App\Models\OnboardingSession;
use App\Models\OnboardingStep;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OnboardingStepSkipService
{
    private const DECISION_STATES = ['allowed', 'blocked'];

    private const BLOCKER_CODES = [
        'missing_session',
        'missing_step',
        'session_completed',
        'session_abandoned',
        'step_required',
        'step_finalized',
        'missing_skip_reason',
    ];

    private const AUDIT_FIELDS = [
        'state',
        'skip_reason',
        'skipped_at',
        'metadata',
    ];

    public function __construct(
        private readonly OnboardingProgressService $onboardingProgressService,
        private readonly OnboardingStepRegistry $onboardingStepRegistry
    ) {
    }

    public function catalogVersion(): string
    {
        return $this->onboardingStepRegistry->catalogVersion();
    }

    public function buildReport(): array
    {
        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'calculation_basis' => 'Non-critical onboarding steps can be skipped with a required reason and preserved in the audit trail.',
                'decision_states_total' => count(self::DECISION_STATES),
                'blocker_codes_total' => count(self::BLOCKER_CODES),
                'validation_checks_total' => count(self::BLOCKER_CODES),
                'audit_fields_total' => count(self::AUDIT_FIELDS),
            ],
            'decision_states' => self::DECISION_STATES,
            'blocker_codes' => self::BLOCKER_CODES,
            'audit_fields' => self::AUDIT_FIELDS,
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, string> $planModules
     */
    public function skipForCompany(
        User|int $company,
        string $stepKey,
        string $reason,
        ?User $actor = null,
        array $metadata = [],
        ?array $planModules = null,
        ?string $planLabel = null
    ): array {
        $company = $company instanceof User ? $company : User::find($company);

        if (! $company) {
            throw new InvalidArgumentException('Company not found.');
        }

        $session = OnboardingSession::query()
            ->forCompany($company->id)
            ->active()
            ->latest('started_at')
            ->latest('id')
            ->first();

        if (! $session) {
            return $this->buildBlockedReport(
                null,
                null,
                'missing_session',
                'No active onboarding session exists for this company.',
                [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'step_key' => $stepKey,
                ],
                $planModules,
                $planLabel
            );
        }

        return $this->skipForSession($session, $stepKey, $reason, $actor, $metadata, $planModules, $planLabel);
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, string> $planModules
     */
    public function skipForSession(
        OnboardingSession $session,
        string $stepKey,
        string $reason,
        ?User $actor = null,
        array $metadata = [],
        ?array $planModules = null,
        ?string $planLabel = null
    ): array {
        $session->loadMissing(['company', 'steps.checklistItems']);

        if (! $session->company) {
            throw new InvalidArgumentException('Session company not found.');
        }

        $step = $session->steps->firstWhere('step_key', $stepKey) ?? $session->steps()->where('step_key', $stepKey)->first();
        $normalizedReason = trim($reason);

        if (! $step) {
            return $this->buildBlockedReport(
                $session,
                null,
                'missing_step',
                'The onboarding step does not exist in the current session.',
                [
                    'session_id' => $session->id,
                    'session_status' => $session->status,
                    'step_key' => $stepKey,
                ],
                $planModules,
                $planLabel
            );
        }

        if ($session->isCompleted()) {
            return $this->buildBlockedReport(
                $session,
                $step,
                'session_completed',
                'Completed onboarding sessions cannot be modified.',
                [
                    'session_id' => $session->id,
                    'session_status' => $session->status,
                    'step_key' => $stepKey,
                ],
                $planModules,
                $planLabel
            );
        }

        if ($session->isAbandoned()) {
            return $this->buildBlockedReport(
                $session,
                $step,
                'session_abandoned',
                'Abandoned onboarding sessions cannot be modified.',
                [
                    'session_id' => $session->id,
                    'session_status' => $session->status,
                    'step_key' => $stepKey,
                ],
                $planModules,
                $planLabel
            );
        }

        if ($step->isCompleted()) {
            return $this->buildBlockedReport(
                $session,
                $step,
                'step_finalized',
                'Completed steps cannot be skipped.',
                [
                    'session_id' => $session->id,
                    'step_id' => $step->id,
                    'step_key' => $step->step_key,
                    'step_state' => $step->state,
                ],
                $planModules,
                $planLabel
            );
        }

        if ($step->is_required) {
            return $this->buildBlockedReport(
                $session,
                $step,
                'step_required',
                'Required onboarding steps cannot be skipped.',
                [
                    'session_id' => $session->id,
                    'step_id' => $step->id,
                    'step_key' => $step->step_key,
                    'step_state' => $step->state,
                    'is_required' => true,
                ],
                $planModules,
                $planLabel
            );
        }

        if ($normalizedReason === '') {
            return $this->buildBlockedReport(
                $session,
                $step,
                'missing_skip_reason',
                'A skip reason is required for auditability.',
                [
                    'session_id' => $session->id,
                    'step_id' => $step->id,
                    'step_key' => $step->step_key,
                ],
                $planModules,
                $planLabel
            );
        }

        $actorId = $actor?->id ?? $session->created_by ?? $session->company_id;
        $now = now();

        return DB::transaction(function () use ($session, $step, $actorId, $actor, $now, $normalizedReason, $metadata, $planModules, $planLabel): array {
            $lockedSession = OnboardingSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedStep = OnboardingStep::query()
                ->whereKey($step->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStep->isCompleted()) {
                return $this->buildBlockedReport(
                    $lockedSession,
                    $lockedStep,
                    'step_finalized',
                    'Completed steps cannot be skipped.',
                    [
                        'session_id' => $lockedSession->id,
                        'step_id' => $lockedStep->id,
                        'step_key' => $lockedStep->step_key,
                        'step_state' => $lockedStep->state,
                    ],
                    $planModules,
                    $planLabel
                );
            }

            if ($lockedStep->is_required) {
                return $this->buildBlockedReport(
                    $lockedSession,
                    $lockedStep,
                    'step_required',
                    'Required onboarding steps cannot be skipped.',
                    [
                        'session_id' => $lockedSession->id,
                        'step_id' => $lockedStep->id,
                        'step_key' => $lockedStep->step_key,
                        'step_state' => $lockedStep->state,
                        'is_required' => true,
                    ],
                    $planModules,
                    $planLabel
                );
            }

            $auditMetadata = array_replace_recursive(
                (array) ($lockedStep->metadata ?? []),
                [
                    'skip' => [
                        'reason' => $normalizedReason,
                        'skipped_at' => $now->toIso8601String(),
                        'skipped_by' => $actorId,
                        'skipped_by_name' => $actor?->name,
                        'company_id' => $lockedSession->company_id,
                        'session_id' => $lockedSession->id,
                        'step_key' => $lockedStep->step_key,
                    ],
                ],
                $metadata
            );

            $lockedStep->forceFill([
                'state' => 'skipped',
                'skipped_at' => $now,
                'skip_reason' => $normalizedReason,
                'metadata' => $auditMetadata,
                'created_by' => $lockedStep->created_by ?? $actorId,
            ])->save();

            $progress = $this->onboardingProgressService->calculateForSession(
                $lockedSession->refresh()->loadMissing(['company', 'steps.checklistItems']),
                $planModules,
                $planLabel
            );

            $lockedSession->forceFill([
                'progress_percent' => (float) data_get($progress, 'summary.progress_percent', 0),
                'current_module_key' => $lockedStep->module_key,
                'current_step_key' => $lockedStep->step_key,
                'last_activity_at' => $now,
                'metadata' => array_replace_recursive(
                    (array) ($lockedSession->metadata ?? []),
                    [
                        'last_step_action' => [
                            'type' => 'skip',
                            'step_key' => $lockedStep->step_key,
                            'reason' => $normalizedReason,
                            'acted_at' => $now->toIso8601String(),
                            'acted_by' => $actorId,
                        ],
                    ]
                ),
            ])->save();
            app(AuditTrailService::class)->record('updated', $lockedStep);

            $lockedSession->refresh()->loadMissing(['company', 'steps.checklistItems']);
            $progress = $this->onboardingProgressService->calculateForSession($lockedSession, $planModules, $planLabel);

            return [
                'meta' => [
                    'catalog_version' => $this->catalogVersion(),
                    'generated_at' => now()->toIso8601String(),
                    'plan_label' => $planLabel ?? data_get($progress, 'meta.plan_label'),
                    'plan_modules' => $planModules ?? (array) data_get($progress, 'meta.plan_modules', []),
                    'company_id' => $lockedSession->company_id,
                    'company_name' => $lockedSession->company?->name,
                    'session_id' => $lockedSession->id,
                    'session_status' => $lockedSession->status,
                    'step_id' => $lockedStep->id,
                    'step_key' => $lockedStep->step_key,
                ],
                'summary' => [
                    'skip_state' => 'skipped',
                    'can_skip' => true,
                    'already_skipped' => false,
                    'step_required' => (bool) $lockedStep->is_required,
                    'step_state' => $lockedStep->state,
                    'skip_reason' => $normalizedReason,
                    'decision_state' => 'allowed',
                    'validation_checks_total' => count(self::BLOCKER_CODES),
                ],
                'step' => $this->serializeStep($lockedStep->refresh()),
                'session' => $this->serializeSession($lockedSession),
                'progress' => $progress,
                'blocker' => null,
            ];
        });
    }

    private function buildBlockedReport(
        ?OnboardingSession $session,
        ?OnboardingStep $step,
        string $code,
        string $message,
        array $details,
        ?array $planModules,
        ?string $planLabel
    ): array {
        $progress = null;

        if ($session && $session->company) {
            $progress = $this->onboardingProgressService->calculateForSession(
                $session->loadMissing(['company', 'steps.checklistItems']),
                $planModules,
                $planLabel
            );
        }

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
                'plan_label' => $planLabel ?? data_get($progress, 'meta.plan_label'),
                'plan_modules' => $planModules ?? (array) data_get($progress, 'meta.plan_modules', []),
                'company_id' => $session?->company_id,
                'company_name' => $session?->company?->name,
                'session_id' => $session?->id,
                'session_status' => $session?->status,
                'step_id' => $step?->id,
                'step_key' => $step?->step_key,
            ],
            'summary' => [
                'skip_state' => 'blocked',
                'can_skip' => false,
                'already_skipped' => $step?->isSkipped() ?? false,
                'step_required' => (bool) ($step?->is_required ?? false),
                'step_state' => $step?->state,
                'skip_reason' => null,
                'decision_state' => 'blocked',
                'validation_checks_total' => count(self::BLOCKER_CODES),
            ],
            'step' => $step ? $this->serializeStep($step) : null,
            'session' => $session ? $this->serializeSession($session) : null,
            'progress' => $progress,
            'blocker' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ];
    }

    private function serializeStep(OnboardingStep $step): array
    {
        return [
            'id' => $step->id,
            'company_id' => $step->company_id,
            'onboarding_session_id' => $step->onboarding_session_id,
            'module_key' => $step->module_key,
            'step_key' => $step->step_key,
            'step_label' => $step->step_label,
            'step_order' => $step->step_order,
            'is_required' => (bool) $step->is_required,
            'state' => $step->state,
            'started_at' => $step->started_at?->toIso8601String(),
            'completed_at' => $step->completed_at?->toIso8601String(),
            'skipped_at' => $step->skipped_at?->toIso8601String(),
            'blocked_at' => $step->blocked_at?->toIso8601String(),
            'skip_reason' => $step->skip_reason,
            'metadata' => $step->metadata ?? [],
        ];
    }

    private function serializeSession(OnboardingSession $session): array
    {
        return [
            'id' => $session->id,
            'company_id' => $session->company_id,
            'status' => $session->status,
            'current_module_key' => $session->current_module_key,
            'current_step_key' => $session->current_step_key,
            'progress_percent' => $session->progress_percent,
            'started_at' => $session->started_at?->toIso8601String(),
            'last_activity_at' => $session->last_activity_at?->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'abandoned_at' => $session->abandoned_at?->toIso8601String(),
            'completion_note' => $session->completion_note,
            'metadata' => $session->metadata ?? [],
        ];
    }
}
