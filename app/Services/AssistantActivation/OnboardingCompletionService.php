<?php

namespace App\Services\AssistantActivation;

use App\Models\OnboardingSession;
use App\Models\User;
use InvalidArgumentException;

class OnboardingCompletionService
{
    private const DECISION_STATES = ['complete', 'blocked'];

    private const BLOCKER_CODES = [
        'missing_session',
        'session_inactive',
        'session_abandoned',
        'required_steps_incomplete',
        'readiness_blocked',
    ];

    public function __construct(
        private readonly OnboardingProgressService $onboardingProgressService,
        private readonly OnboardingReadinessService $onboardingReadinessService
    ) {
    }

    public function catalogVersion(): string
    {
        return $this->onboardingReadinessService->catalogVersion();
    }

    public function buildReport(): array
    {
        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'calculation_basis' => 'Onboarding can be concluded when the session is active or already completed, required steps are resolved and readiness is ready.',
                'decision_states_total' => count(self::DECISION_STATES),
                'blocker_codes_total' => count(self::BLOCKER_CODES),
                'validation_checks_total' => count(self::BLOCKER_CODES),
            ],
            'decision_states' => self::DECISION_STATES,
            'blocker_codes' => self::BLOCKER_CODES,
        ];
    }

    /**
     * @param array<int, string> $planModules
     */
    public function calculateForCompany(User|int $company, ?array $planModules = null, ?string $planLabel = null): array
    {
        $company = $company instanceof User ? $company : User::find($company);

        if (! $company) {
            throw new InvalidArgumentException('Company not found.');
        }

        $progress = $this->onboardingProgressService->calculateForCompany($company, $planModules, $planLabel);
        $readiness = $this->onboardingReadinessService->calculateForCompany($company, $planModules, $planLabel);

        return $this->buildCalculatedReport($company, $progress, $readiness, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    public function calculateForSession(OnboardingSession $session, ?array $planModules = null, ?string $planLabel = null): array
    {
        $session->loadMissing('company');

        if (! $session->company) {
            throw new InvalidArgumentException('Session company not found.');
        }

        $progress = $this->onboardingProgressService->calculateForSession($session, $planModules, $planLabel);
        $readiness = $this->onboardingReadinessService->calculateForSession($session, $planModules, $planLabel);

        return $this->buildCalculatedReport($session->company, $progress, $readiness, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    private function buildCalculatedReport(User $company, array $progress, array $readiness, ?array $planModules, ?string $planLabel): array
    {
        $planModules = $this->normalizeList($planModules ?? (array) data_get($progress, 'meta.plan_modules', []));
        $planLabel = $planLabel ?? (string) data_get($progress, 'meta.plan_label');

        $sessionId = data_get($progress, 'meta.session_id');
        $sessionStatus = (string) data_get($progress, 'meta.session_status');
        $progressSummary = (array) data_get($progress, 'summary', []);
        $readinessSummary = (array) data_get($readiness, 'summary', []);
        $criticalBlocks = array_values((array) data_get($readiness, 'critical_blocks', []));
        $readinessState = (string) ($readinessSummary['readiness_state'] ?? 'critical');

        $requiredSteps = collect((array) data_get($progress, 'modules', []))
            ->flatMap(function (array $module): array {
                return array_map(
                    static fn (array $step) => $step + [
                        'module_key' => (string) ($module['key'] ?? ''),
                        'module_label' => (string) ($module['label'] ?? ''),
                    ],
                    (array) ($module['steps'] ?? [])
                );
            })
            ->filter(static function (array $step): bool {
                return (bool) ($step['available'] ?? false) && (bool) ($step['required'] ?? false);
            })
            ->values();

        $completedRequiredSteps = $requiredSteps->filter(static fn (array $step): bool => (float) ($step['progress_percent'] ?? 0) >= 100.0)->values();
        $incompleteRequiredSteps = $requiredSteps->filter(static fn (array $step): bool => (float) ($step['progress_percent'] ?? 0) < 100.0)->values();

        $blockers = [];
        $sessionCompleted = $sessionStatus === 'completed';

        if ($sessionId === null) {
            $blockers[] = $this->blocker(
                'missing_session',
                'No onboarding session exists.',
                [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ]
            );
        } elseif ($sessionStatus === 'abandoned') {
            $blockers[] = $this->blocker(
                'session_abandoned',
                'The onboarding session was abandoned.',
                [
                    'session_id' => $sessionId,
                    'session_status' => $sessionStatus,
                ]
            );
        } elseif ($sessionStatus !== 'active' && ! $sessionCompleted) {
            $blockers[] = $this->blocker(
                'session_inactive',
                'The onboarding session is not active.',
                [
                    'session_id' => $sessionId,
                    'session_status' => $sessionStatus ?: null,
                ]
            );
        }

        if ($sessionId !== null && ! $sessionCompleted && $incompleteRequiredSteps->isNotEmpty()) {
            $blockers[] = $this->blocker(
                'required_steps_incomplete',
                'Required onboarding steps remain incomplete.',
                [
                    'required_steps_total' => $requiredSteps->count(),
                    'completed_required_steps_total' => $completedRequiredSteps->count(),
                    'incomplete_required_steps_total' => $incompleteRequiredSteps->count(),
                    'steps' => $incompleteRequiredSteps->map(static fn (array $step): array => [
                        'module_key' => $step['module_key'],
                        'module_label' => $step['module_label'],
                        'key' => $step['key'],
                        'label' => $step['label'],
                        'state' => $step['state'] ?? 'pending',
                        'progress_percent' => (float) ($step['progress_percent'] ?? 0),
                    ])->values()->all(),
                ]
            );
        }

        if ($sessionId !== null && ! $sessionCompleted && ($readinessState !== 'ready' || $criticalBlocks !== [])) {
            $blockers[] = $this->blocker(
                'readiness_blocked',
                'Critical readiness checks are still blocking completion.',
                [
                    'readiness_state' => $readinessState,
                    'overall_score' => (float) ($readinessSummary['overall_score'] ?? 0),
                    'critical_blocks_total' => count($criticalBlocks),
                    'critical_blocks' => $criticalBlocks,
                ]
            );
        }

        $canComplete = $sessionCompleted
            || ($sessionId !== null
                && $sessionStatus === 'active'
                && $incompleteRequiredSteps->isEmpty()
                && $readinessState === 'ready'
                && $criticalBlocks === []);

        if ($sessionCompleted) {
            $blockers = [];
        }

        $completionState = $canComplete ? 'complete' : 'blocked';

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
                'plan_label' => $planLabel,
                'plan_modules' => $planModules,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'session_id' => $sessionId,
                'session_status' => $sessionStatus ?: null,
            ],
            'summary' => [
                'calculation_basis' => 'Onboarding can be concluded when the session is active or already completed, required steps are resolved and readiness is ready.',
                'completion_state' => $completionState,
                'can_complete' => $canComplete,
                'already_completed' => $sessionCompleted,
                'readiness_state' => $readinessState,
                'readiness_score' => (float) ($readinessSummary['overall_score'] ?? 0),
                'progress_percent' => (float) ($progressSummary['progress_percent'] ?? 0),
                'required_steps_total' => $requiredSteps->count(),
                'completed_required_steps_total' => $completedRequiredSteps->count(),
                'incomplete_required_steps_total' => $incompleteRequiredSteps->count(),
                'critical_blocks_total' => count($criticalBlocks),
                'blockers_total' => count($blockers),
                'validation_checks_total' => count(self::BLOCKER_CODES),
                'session_completed' => $sessionCompleted,
                'session_active' => $sessionStatus === 'active',
            ],
            'progress' => $progress,
            'readiness' => $readiness,
            'blockers' => $blockers,
            'required_steps' => $requiredSteps->values()->all(),
            'critical_blocks' => $criticalBlocks,
        ];
    }

    private function blocker(string $code, string $label, array $details): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'details' => $details,
        ];
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function normalizeList(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $values
        ))));
    }
}
