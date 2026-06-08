<?php

namespace App\Services\AssistantActivation;

use App\Models\Plan;
use App\Models\User;
use InvalidArgumentException;

class OnboardingDashboardService
{
    public function __construct(
        private readonly OnboardingReadinessService $onboardingReadinessService,
        private readonly OnboardingCompletionService $onboardingCompletionService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    /**
     * @param array<int, string>|null $planModules
     */
    public function snapshot(User|int $company, ?array $planModules = null, ?string $planLabel = null): array
    {
        $company = $company instanceof User ? $company : User::find($company);

        if (! $company) {
            throw new InvalidArgumentException('Company not found.');
        }

        $planModules = $this->normalizeModules($planModules ?? Plan::getUserSubscriptionModules($company->id));
        $planLabel ??= $this->resolvePlanLabel($company);

        $readiness = $this->onboardingReadinessService->calculateForCompany($company, $planModules, $planLabel);
        $completion = $this->onboardingCompletionService->calculateForCompany($company, $planModules, $planLabel);
        $progress = (array) data_get($readiness, 'progress', []);
        $criticalBlocks = array_values((array) data_get($readiness, 'critical_blocks', []));
        $modules = array_values((array) data_get($readiness, 'modules', []));
        $sessionStatus = (string) ($progress['meta']['session_status'] ?? data_get($readiness, 'meta.session_status') ?? 'not_started');
        $progressPercent = (float) data_get($progress, 'summary.progress_percent', 0);
        $canComplete = (bool) data_get($completion, 'summary.can_complete', false);
        $contextualNextAction = $this->contextualCtaResolverService->forBlocks($criticalBlocks);

        return [
            'meta' => [
                'catalog_version' => (string) data_get($readiness, 'meta.catalog_version', $this->onboardingReadinessService->catalogVersion()),
                'generated_at' => now()->toIso8601String(),
                'company_id' => $company->id,
                'company_name' => $company->name,
                'plan_label' => $planLabel,
                'plan_modules' => $planModules,
                'session_id' => data_get($progress, 'meta.session_id') ?? data_get($readiness, 'meta.session_id'),
                'session_status' => $sessionStatus,
                'session_status_label' => $this->labelSessionStatus($sessionStatus),
                'is_new_company' => $sessionStatus === 'not_started',
            ],
            'summary' => [
                'progress_percent' => $progressPercent,
                'readiness_state' => data_get($readiness, 'summary.readiness_state'),
                'readiness_state_label' => $this->labelReadinessState((string) data_get($readiness, 'summary.readiness_state')),
                'overall_score' => (float) data_get($readiness, 'summary.overall_score', 0),
                'completion_state' => data_get($completion, 'summary.completion_state'),
                'completion_state_label' => $this->labelCompletionState((string) data_get($completion, 'summary.completion_state')),
                'can_complete' => $canComplete,
                'critical_blocks_total' => count($criticalBlocks),
                'config_blocks_total' => (int) data_get($readiness, 'summary.config_blocks_total', 0),
                'step_blocks_total' => (int) data_get($readiness, 'summary.step_blocks_total', 0),
                'available_steps_total' => (int) data_get($progress, 'summary.available_steps_total', 0),
                'completed_required_steps_total' => (int) data_get($progress, 'summary.completed_required_steps_total', 0),
                'required_steps_total' => (int) data_get($progress, 'summary.required_steps_total', 0),
            ],
            'next_action' => [
                'label' => $contextualNextAction['label'] ?? $this->resolveNextActionLabel($sessionStatus, $canComplete),
                'href' => $contextualNextAction['href'] ?? route('onboarding.index'),
                'message' => $contextualNextAction['message'] ?? $this->resolveNextActionMessage($criticalBlocks, (float) data_get($progress, 'summary.progress_percent', 0), $canComplete),
                'tone' => $contextualNextAction['tone'] ?? ($canComplete ? 'default' : 'secondary'),
            ],
            'top_blocks' => array_slice($criticalBlocks, 0, 3),
            'module_snapshots' => array_slice($modules, 0, 3),
        ];
    }

    /**
     * @param array<int, string> $modules
     * @return array<int, string>
     */
    private function normalizeModules(array $modules): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($module): string => trim((string) $module),
            $modules
        ))));
    }

    private function resolvePlanLabel(?User $company): ?string
    {
        if (! $company?->active_plan) {
            return null;
        }

        return Plan::query()->find($company->active_plan)?->name;
    }

    private function resolveNextActionLabel(string $sessionStatus, bool $canComplete): string
    {
        if ($canComplete) {
            return 'Open onboarding';
        }

        return match ($sessionStatus) {
            'active' => 'Continue onboarding',
            'completed' => 'Review onboarding',
            'abandoned' => 'Review onboarding',
            default => 'Start onboarding',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $criticalBlocks
     */
    private function resolveNextActionMessage(array $criticalBlocks, float $progressPercent, bool $canComplete): string
    {
        $criticalBlocksTotal = count($criticalBlocks);

        if ($criticalBlocksTotal > 0) {
            return sprintf(
                '%d critical pending item(s) still need attention before go-live.',
                $criticalBlocksTotal
            );
        }

        if ($canComplete) {
            return 'All mandatory checks are satisfied. Review the onboarding flow before completing it.';
        }

        if ($progressPercent > 0.0) {
            return 'Continue the remaining setup steps to improve readiness.';
        }

        return 'Start the onboarding flow to configure the company before going live.';
    }

    private function labelSessionStatus(string $status): string
    {
        return match ($status) {
            'active' => 'In progress',
            'completed' => 'Completed',
            'abandoned' => 'Abandoned',
            default => 'Not started',
        };
    }

    private function labelReadinessState(string $state): string
    {
        return match ($state) {
            'ready' => 'Ready',
            'warning' => 'Warning',
            'blocked' => 'Blocked',
            default => 'Critical',
        };
    }

    private function labelCompletionState(string $state): string
    {
        return match ($state) {
            'complete' => 'Completable',
            'blocked' => 'Blocked',
            default => 'Indefinite',
        };
    }
}
