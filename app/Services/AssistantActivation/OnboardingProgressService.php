<?php

namespace App\Services\AssistantActivation;

use App\Models\OnboardingChecklistItem;
use App\Models\OnboardingSession;
use App\Models\OnboardingStep;
use App\Models\Plan;
use App\Models\User;
use InvalidArgumentException;

class OnboardingProgressService
{
    private const STEP_RESOLVED_STATES = ['completed', 'skipped', 'not_applicable'];

    private const ITEM_RESOLVED_STATES = ['completed', 'skipped', 'not_applicable'];

    public function __construct(
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
                'calculation_basis' => 'available steps only; checklist items provide partial progress',
                'calculation_scope' => 'company session or explicit session',
                'step_states_total' => count($this->stepStates()),
                'checklist_states_total' => count($this->checklistStates()),
            ],
            'step_states' => $this->stepStates(),
            'checklist_states' => $this->checklistStates(),
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

        $session = OnboardingSession::query()
            ->forCompany($company->id)
            ->active()
            ->latest('started_at')
            ->latest('id')
            ->first()
            ?? OnboardingSession::query()
                ->forCompany($company->id)
                ->latest('started_at')
                ->latest('id')
                ->first();

        $planModules ??= Plan::getUserSubscriptionModules($company->id);
        $planLabel ??= $this->resolvePlanLabel($company);

        return $this->calculateFromContext($session, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    public function calculateForSession(OnboardingSession $session, ?array $planModules = null, ?string $planLabel = null): array
    {
        $session->loadMissing(['company', 'steps.checklistItems']);

        $planModules ??= $session->company ? Plan::getUserSubscriptionModules($session->company->id) : [];
        $planLabel ??= $this->resolvePlanLabel($session->company);

        return $this->calculateFromContext($session, $planModules, $planLabel);
    }

    /**
     * @param array<int, string> $planModules
     */
    private function calculateFromContext(?OnboardingSession $session, array $planModules, ?string $planLabel): array
    {
        $planModules = $this->normalizeModules($planModules);
        $planReport = $this->onboardingStepRegistry->buildPlanReport($planModules, $planLabel);

        $session?->loadMissing(['company', 'steps.checklistItems']);

        $stepsByKey = collect($session?->steps ?? [])->keyBy('step_key');

        $moduleBreakdown = [];
        $summary = [
            'modules_total' => count($planReport['modules']),
            'available_modules_total' => 0,
            'unavailable_modules_total' => 0,
            'steps_total' => 0,
            'available_steps_total' => 0,
            'unavailable_steps_total' => 0,
            'completed_steps_total' => 0,
            'skipped_steps_total' => 0,
            'blocked_steps_total' => 0,
            'pending_steps_total' => 0,
            'in_progress_steps_total' => 0,
            'not_applicable_steps_total' => 0,
            'partial_steps_total' => 0,
            'resolved_steps_total' => 0,
            'required_steps_total' => 0,
            'completed_required_steps_total' => 0,
            'progress_percent' => 0.0,
        ];

        $globalProgressTotal = 0.0;

        foreach ($planReport['modules'] as $module) {
            $moduleProgressTotal = 0.0;
            $moduleAvailableStepCount = 0;
            $moduleUnavailableStepCount = 0;
            $moduleCounts = $this->initialStateCounters();
            $moduleRequiredStepsTotal = 0;
            $moduleCompletedRequiredStepsTotal = 0;
            $moduleSteps = [];

            foreach ($module['steps'] as $expectedStep) {
                $actualStep = $stepsByKey->get($expectedStep['key']);
                $stepProgress = $this->calculateStepProgress($expectedStep, $actualStep);
                $stepState = (string) ($actualStep?->state ?? 'pending');
                $isAvailable = (bool) ($expectedStep['available'] ?? false);

                $stepReport = $this->buildStepReport($expectedStep, $actualStep, $stepProgress, $stepState);
                $moduleSteps[] = $stepReport;

                $summary['steps_total']++;

                if (! $isAvailable) {
                    $moduleUnavailableStepCount++;
                    $summary['unavailable_steps_total']++;
                    continue;
                }

                $moduleAvailableStepCount++;
                $summary['available_steps_total']++;

                $moduleProgressTotal += $stepProgress;
                $globalProgressTotal += $stepProgress;

                $stateKey = $this->normalizeStateKey($stepState);
                $moduleCounts[$stateKey] = ($moduleCounts[$stateKey] ?? 0) + 1;
                $summary[$stateKey . '_steps_total'] = ($summary[$stateKey . '_steps_total'] ?? 0) + 1;

                if ($stepReport['progress_percent'] >= 100.0) {
                    $moduleCounts['resolved']++;
                    $summary['resolved_steps_total']++;
                } elseif ($stepReport['progress_percent'] > 0.0) {
                    $moduleCounts['partial']++;
                    $summary['partial_steps_total']++;
                }

                if ($stepReport['required']) {
                    $moduleRequiredStepsTotal++;
                    $summary['required_steps_total']++;

                    if ($stepReport['progress_percent'] >= 100.0) {
                        $moduleCompletedRequiredStepsTotal++;
                        $summary['completed_required_steps_total']++;
                    }
                }
            }

            $moduleProgressPercent = $moduleAvailableStepCount > 0
                ? round($moduleProgressTotal / $moduleAvailableStepCount, 2)
                : 100.0;

            if ($moduleAvailableStepCount > 0) {
                $summary['available_modules_total']++;
            } else {
                $summary['unavailable_modules_total']++;
            }

            $moduleBreakdown[] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'available' => $moduleAvailableStepCount > 0,
                'priority' => $module['priority'],
                'technical_modules' => $module['technical_modules'],
                'step_count' => $module['step_count'],
                'available_step_count' => $moduleAvailableStepCount,
                'unavailable_step_count' => $moduleUnavailableStepCount,
                'required_step_count' => $module['required_step_count'],
                'required_available_step_count' => $moduleRequiredStepsTotal,
                'completed_required_step_count' => $moduleCompletedRequiredStepsTotal,
                'completed_step_count' => collect($moduleSteps)
                    ->where('available', true)
                    ->filter(fn (array $step): bool => $step['progress_percent'] >= 100.0)
                    ->count(),
                'partial_step_count' => collect($moduleSteps)
                    ->where('available', true)
                    ->filter(fn (array $step): bool => $step['progress_percent'] > 0.0 && $step['progress_percent'] < 100.0)
                    ->count(),
                'pending_step_count' => collect($moduleSteps)
                    ->where('available', true)
                    ->filter(fn (array $step): bool => $step['progress_percent'] === 0.0)
                    ->count(),
                'blocked_step_count' => collect($moduleSteps)
                    ->where('available', true)
                    ->filter(fn (array $step): bool => $step['state'] === 'blocked')
                    ->count(),
                'progress_percent' => $moduleProgressPercent,
                'steps' => $moduleSteps,
            ];
        }

        $globalProgressPercent = $summary['available_steps_total'] > 0
            ? round($globalProgressTotal / $summary['available_steps_total'], 2)
            : 100.0;

        $summary['progress_percent'] = $globalProgressPercent;

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
                'plan_label' => $planLabel,
                'plan_modules' => $planModules,
                'session_id' => $session?->id,
                'session_status' => $session?->status,
            ],
            'summary' => $summary,
            'modules' => $moduleBreakdown,
            'session' => $this->buildSessionMeta($session),
        ];
    }

    private function buildStepReport(array $expectedStep, ?OnboardingStep $actualStep, float $stepProgress, string $stepState): array
    {
        $checklistSummary = $this->buildChecklistSummary($actualStep);

        return array_merge($expectedStep, [
            'session_step_id' => $actualStep?->id,
            'state' => $stepState,
            'state_label' => $this->labelizeState($stepState),
            'progress_percent' => $stepProgress,
            'items_total' => $checklistSummary['total'],
            'items_completed_total' => $checklistSummary['completed'],
            'items_skipped_total' => $checklistSummary['skipped'],
            'items_blocked_total' => $checklistSummary['blocked'],
            'items_pending_total' => $checklistSummary['pending'],
            'items_not_applicable_total' => $checklistSummary['not_applicable'],
            'item_progress_percent' => $checklistSummary['progress_percent'],
            'resolved' => $stepProgress >= 100.0,
            'started_at' => $actualStep?->started_at?->toIso8601String(),
            'completed_at' => $actualStep?->completed_at?->toIso8601String(),
            'skipped_at' => $actualStep?->skipped_at?->toIso8601String(),
            'blocked_at' => $actualStep?->blocked_at?->toIso8601String(),
            'skip_reason' => $actualStep?->skip_reason,
            'metadata' => $actualStep?->metadata ?? [],
        ]);
    }

    private function calculateStepProgress(array $expectedStep, ?OnboardingStep $actualStep): float
    {
        $state = (string) ($actualStep?->state ?? 'pending');

        if (in_array($state, self::STEP_RESOLVED_STATES, true)) {
            return 100.0;
        }

        $checklistSummary = $this->buildChecklistSummary($actualStep);

        return (float) ($checklistSummary['progress_percent'] ?? 0.0);
    }

    private function buildChecklistSummary(?OnboardingStep $actualStep): array
    {
        if (! $actualStep) {
            return [
                'total' => 0,
                'completed' => 0,
                'skipped' => 0,
                'blocked' => 0,
                'pending' => 0,
                'not_applicable' => 0,
                'progress_percent' => 0.0,
            ];
        }

        $items = $actualStep->relationLoaded('checklistItems')
            ? $actualStep->checklistItems
            : $actualStep->checklistItems()->get();

        $total = $items->count();
        $completed = $items->where('state', 'completed')->count();
        $skipped = $items->where('state', 'skipped')->count();
        $blocked = $items->where('state', 'blocked')->count();
        $pending = $items->where('state', 'pending')->count();
        $notApplicable = $items->where('state', 'not_applicable')->count();
        $resolved = $items->filter(fn (OnboardingChecklistItem $item): bool => in_array($item->state, self::ITEM_RESOLVED_STATES, true))->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'skipped' => $skipped,
            'blocked' => $blocked,
            'pending' => $pending,
            'not_applicable' => $notApplicable,
            'progress_percent' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0.0,
        ];
    }

    private function buildSessionMeta(?OnboardingSession $session): array
    {
        if (! $session) {
            return [
                'id' => null,
                'status' => null,
                'current_module_key' => null,
                'current_step_key' => null,
                'progress_percent' => null,
            ];
        }

        return [
            'id' => $session->id,
            'status' => $session->status,
            'current_module_key' => $session->current_module_key,
            'current_step_key' => $session->current_step_key,
            'progress_percent' => $session->progress_percent,
            'started_at' => $session->started_at?->toIso8601String(),
            'last_activity_at' => $session->last_activity_at?->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'abandoned_at' => $session->abandoned_at?->toIso8601String(),
        ];
    }

    private function resolvePlanLabel(?User $company): ?string
    {
        if (! $company?->active_plan) {
            return null;
        }

        return Plan::query()->find($company->active_plan)?->name;
    }

    /**
     * @param array<int, string> $modules
     * @return array<int, string>
     */
    private function normalizeModules(array $modules): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($module) => trim((string) $module),
            $modules
        ))));
    }

    private function labelizeState(string $state): string
    {
        return match ($state) {
            'in_progress' => 'Em progresso',
            'completed' => 'Concluído',
            'skipped' => 'Ignorado',
            'blocked' => 'Bloqueado',
            'not_applicable' => 'Não aplicável',
            default => 'Pendente',
        };
    }

    /**
     * @return array<int, string>
     */
    private function stepStates(): array
    {
        return ['pending', 'in_progress', 'completed', 'blocked', 'skipped', 'not_applicable'];
    }

    /**
     * @return array<int, string>
     */
    private function checklistStates(): array
    {
        return ['pending', 'in_progress', 'completed', 'blocked', 'skipped', 'not_applicable'];
    }

    private function normalizeStateKey(string $state): string
    {
        return match ($state) {
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'skipped' => 'skipped',
            'blocked' => 'blocked',
            'not_applicable' => 'not_applicable',
            default => 'pending',
        };
    }

    /**
     * @return array<string, int>
     */
    private function initialStateCounters(): array
    {
        return [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'blocked' => 0,
            'skipped' => 0,
            'not_applicable' => 0,
            'partial' => 0,
            'resolved' => 0,
        ];
    }
}
