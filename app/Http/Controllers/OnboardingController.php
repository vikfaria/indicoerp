<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Services\AssistantActivation\OnboardingCompletionService;
use App\Services\AssistantActivation\OnboardingProgressService;
use App\Services\AssistantActivation\OnboardingReadinessService;
use App\Services\AssistantActivation\OnboardingStepPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(
        Request $request,
        OnboardingProgressService $progressService,
        OnboardingReadinessService $readinessService,
        OnboardingCompletionService $completionService,
        OnboardingStepPresenter $stepPresenter
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User && $user->type === 'company', 403);

        $planModules = Plan::getUserSubscriptionModules($user->id);
        $planLabel = $this->resolvePlanLabel($user);

        $progress = $progressService->calculateForCompany($user, $planModules, $planLabel);
        $readiness = $readinessService->calculateForCompany($user, $planModules, $planLabel);
        $completion = $completionService->calculateForCompany($user, $planModules, $planLabel);
        $progressPercent = (float) data_get($progress, 'summary.progress_percent', 0);
        $sessionStatus = data_get($progress, 'meta.session_status') ?: 'not_started';

        $moduleCards = $this->buildModuleCards($progress, $stepPresenter, $user);
        $nextSteps = $this->buildNextSteps($progress, $readiness, $completion, $stepPresenter, $user);

        return Inertia::render('onboarding/index', [
            'plan' => [
                'label' => $planLabel,
                'modules' => $planModules,
                'modules_total' => count($planModules),
            ],
            'session' => data_get($progress, 'session'),
            'overview' => [
                'session_id' => data_get($progress, 'meta.session_id'),
                'session_status' => $sessionStatus,
                'session_status_label' => $this->labelSessionStatus((string) data_get($progress, 'meta.session_status')),
                'progress_percent' => $progressPercent,
                'is_new_company' => $sessionStatus === 'not_started',
                'available_steps_total' => (int) data_get($progress, 'summary.available_steps_total', 0),
                'completed_required_steps_total' => (int) data_get($progress, 'summary.completed_required_steps_total', 0),
                'required_steps_total' => (int) data_get($progress, 'summary.required_steps_total', 0),
                'readiness_state' => data_get($readiness, 'summary.readiness_state'),
                'readiness_state_label' => $this->labelReadinessState((string) data_get($readiness, 'summary.readiness_state')),
                'readiness_score' => (float) data_get($readiness, 'summary.overall_score', 0),
                'critical_blocks_total' => (int) data_get($readiness, 'summary.critical_blocks_total', 0),
                'completion_state' => data_get($completion, 'summary.completion_state'),
                'completion_state_label' => $this->labelCompletionState((string) data_get($completion, 'summary.completion_state')),
                'can_complete' => (bool) data_get($completion, 'summary.can_complete', false),
                'blockers_total' => (int) data_get($completion, 'summary.blockers_total', 0),
            ],
            'progress' => $progress,
            'readiness' => $readiness,
            'completion' => $completion,
            'modules' => $moduleCards,
            'next_steps' => $nextSteps,
            'critical_blocks' => array_values((array) data_get($readiness, 'critical_blocks', [])),
            'completion_blockers' => array_values((array) data_get($completion, 'blockers', [])),
        ]);
    }

    private function resolvePlanLabel(User $company): ?string
    {
        if (! $company->active_plan) {
            return null;
        }

        return Plan::query()->find($company->active_plan)?->name;
    }

    /**
     * @param array<string, mixed> $progress
     * @return array<int, array<string, mixed>>
     */
    private function buildModuleCards(array $progress, OnboardingStepPresenter $stepPresenter, User $user): array
    {
        return collect((array) data_get($progress, 'modules', []))
            ->map(function (array $module) use ($stepPresenter, $user): array {
                $steps = collect((array) ($module['steps'] ?? []))
                    ->map(fn (array $step): array => $stepPresenter->presentModuleStep($module, $step, $user))
                    ->values()
                    ->all();

                $nextStep = collect($steps)
                    ->first(function (array $step): bool {
                        return (bool) ($step['available'] ?? false)
                            && (bool) ($step['required'] ?? false)
                            && (float) ($step['progress_percent'] ?? 0) < 100.0;
                    });

                return [
                    'key' => $module['key'],
                    'label' => $module['label'],
                    'available' => (bool) ($module['available'] ?? false),
                    'priority' => (int) ($module['priority'] ?? 0),
                    'progress_percent' => (float) ($module['progress_percent'] ?? 0),
                    'available_step_count' => (int) ($module['available_step_count'] ?? 0),
                    'required_available_step_count' => (int) ($module['required_available_step_count'] ?? 0),
                    'completed_required_step_count' => (int) ($module['completed_required_step_count'] ?? 0),
                    'blocked_step_count' => (int) ($module['blocked_step_count'] ?? 0),
                    'next_step' => $nextStep,
                    'steps' => $steps,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $progress
     * @param array<string, mixed> $readiness
     * @param array<string, mixed> $completion
     * @return array<int, array<string, mixed>>
     */
    private function buildNextSteps(array $progress, array $readiness, array $completion, OnboardingStepPresenter $stepPresenter, User $user): array
    {
        $requiredSteps = collect((array) data_get($progress, 'modules', []))
            ->flatMap(function (array $module) use ($stepPresenter, $user): array {
                return collect((array) ($module['steps'] ?? []))
                    ->filter(function (array $step): bool {
                        return (bool) ($step['available'] ?? false)
                            && (bool) ($step['required'] ?? false)
                            && (float) ($step['progress_percent'] ?? 0) < 100.0;
                    })
                    ->map(fn (array $step): array => $stepPresenter->presentModuleStep($module, $step, $user))
                    ->values()
                    ->all();
            })
            ->sortBy(fn (array $step): array => [
                (int) data_get($this->moduleByKey($progress, $step['module_key']), 'priority', 0),
                (int) (($step['progress_percent'] ?? 0) >= 100 ? 1 : 0),
            ])
            ->values();

        if ($requiredSteps->isNotEmpty()) {
            return $requiredSteps->take(6)->values()->all();
        }

        $configBlocks = collect((array) data_get($readiness, 'critical_blocks', []))
            ->filter(fn (array $block): bool => (string) ($block['type'] ?? '') === 'config_missing')
            ->map(fn (array $block): array => $stepPresenter->presentConfigBlock($block));

        if ($configBlocks->isNotEmpty()) {
            return $configBlocks->take(6)->values()->all();
        }

        $completionBlocks = collect((array) data_get($completion, 'blockers', []))
            ->map(fn (array $block): array => $stepPresenter->presentCompletionBlock($block));

        return $completionBlocks->take(6)->values()->all();
    }

    /**
     * @param array<string, mixed> $progress
     */
    private function moduleByKey(array $progress, ?string $moduleKey): ?array
    {
        if ($moduleKey === null || $moduleKey === '') {
            return null;
        }

        return collect((array) data_get($progress, 'modules', []))->firstWhere('key', $moduleKey);
    }

    private function labelSessionStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Em curso',
            'completed' => 'Concluída',
            'abandoned' => 'Abandonada',
            default => 'Sem sessão',
        };
    }

    private function labelReadinessState(string $state): string
    {
        return match ($state) {
            'ready' => 'Pronta',
            'warning' => 'Atenção',
            'blocked' => 'Bloqueada',
            default => 'Crítica',
        };
    }

    private function labelCompletionState(string $state): string
    {
        return match ($state) {
            'complete' => 'Concluível',
            'blocked' => 'Bloqueada',
            default => 'Indefinida',
        };
    }
}
