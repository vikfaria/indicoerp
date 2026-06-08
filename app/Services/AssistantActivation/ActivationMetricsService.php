<?php

namespace App\Services\AssistantActivation;

use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ActivationMetricsService
{
    public function __construct(
        private readonly OnboardingProgressService $onboardingProgressService,
        private readonly OnboardingReadinessService $onboardingReadinessService
    ) {
    }

    /**
     * @param  Collection<int, User>  $companies
     * @return array<string, mixed>
     */
    public function calculate(Collection $companies): array
    {
        $summary = [
            'companies_total' => $companies->count(),
            'ready_companies_total' => 0,
            'completed_companies_total' => 0,
            'active_companies_total' => 0,
            'not_started_companies_total' => 0,
            'companies_with_blockers_total' => 0,
            'blocked_steps_total' => 0,
            'critical_blocks_total' => 0,
            'average_readiness_score' => 0.0,
            'average_progress_percent' => 0.0,
            'average_time_to_readiness_hours' => 0.0,
            'median_time_to_readiness_hours' => 0.0,
            'problematic_modules_total' => 0,
        ];

        $readinessScores = [];
        $progressPercents = [];
        $timeSamples = [];
        $slowCompanies = [];
        $blockedCompanies = [];
        $moduleStats = [];

        foreach ($companies as $company) {
            if (! $company instanceof User) {
                continue;
            }

            $planModules = Plan::getUserSubscriptionModules($company->id);
            $planLabel = $this->resolvePlanLabel($company);

            $progress = $this->onboardingProgressService->calculateForCompany($company, $planModules, $planLabel);
            $readiness = $this->onboardingReadinessService->calculateForCompany($company, $planModules, $planLabel);

            $session = (array) data_get($progress, 'session', []);
            $sessionStatus = (string) data_get($progress, 'meta.session_status', 'not_started');
            $readinessState = (string) data_get($readiness, 'summary.readiness_state', 'critical');
            $readinessScore = (float) data_get($readiness, 'summary.overall_score', 0);
            $progressPercent = (float) data_get($progress, 'summary.progress_percent', 0);
            $blockedStepsTotal = (int) data_get($progress, 'summary.blocked_steps_total', 0);
            $criticalBlocksTotal = (int) data_get($readiness, 'summary.critical_blocks_total', 0);

            $summary['blocked_steps_total'] += $blockedStepsTotal;
            $summary['critical_blocks_total'] += $criticalBlocksTotal;
            $readinessScores[] = $readinessScore;
            $progressPercents[] = $progressPercent;

            if ($readinessState === 'ready') {
                $summary['ready_companies_total']++;
            }

            if ($sessionStatus === 'completed') {
                $summary['completed_companies_total']++;
            } elseif ($sessionStatus === 'active') {
                $summary['active_companies_total']++;
            } elseif ($sessionStatus === 'not_started') {
                $summary['not_started_companies_total']++;
            }

            if ($blockedStepsTotal > 0 || $criticalBlocksTotal > 0) {
                $summary['companies_with_blockers_total']++;
                $blockedCompanies[] = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'blocked_steps_total' => $blockedStepsTotal,
                    'critical_blocks_total' => $criticalBlocksTotal,
                    'readiness_score' => $readinessScore,
                    'progress_percent' => $progressPercent,
                    'readiness_state' => $readinessState,
                ];
            }

            $hoursToReadiness = $this->durationHours(
                data_get($session, 'started_at'),
                data_get($session, 'completed_at')
            );

            if ($hoursToReadiness !== null) {
                $timeSamples[] = $hoursToReadiness;
                $slowCompanies[] = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'hours_to_readiness' => round($hoursToReadiness, 2),
                    'days_to_readiness' => round($hoursToReadiness / 24, 2),
                    'readiness_score' => $readinessScore,
                    'progress_percent' => $progressPercent,
                ];
            }

            foreach ((array) data_get($readiness, 'modules', []) as $module) {
                if (! (bool) ($module['available'] ?? false)) {
                    continue;
                }

                $moduleKey = trim((string) ($module['key'] ?? ''));

                if ($moduleKey === '') {
                    continue;
                }

                $blockedCount = (int) ($module['blocked_step_count'] ?? 0);
                $moduleProgress = (float) ($module['progress_percent'] ?? 0);

                if (! isset($moduleStats[$moduleKey])) {
                    $moduleStats[$moduleKey] = [
                        'key' => $moduleKey,
                        'label' => (string) ($module['label'] ?? $moduleKey),
                        'blocked_steps_total' => 0,
                        'affected_companies_total' => 0,
                        'companies_total' => 0,
                        'progress_percent_total' => 0.0,
                    ];
                }

                $moduleStats[$moduleKey]['companies_total']++;
                $moduleStats[$moduleKey]['progress_percent_total'] += $moduleProgress;
                $moduleStats[$moduleKey]['blocked_steps_total'] += $blockedCount;

                if ($blockedCount > 0) {
                    $moduleStats[$moduleKey]['affected_companies_total']++;
                }
            }
        }

        $summary['average_readiness_score'] = $this->average($readinessScores);
        $summary['average_progress_percent'] = $this->average($progressPercents);
        $summary['average_time_to_readiness_hours'] = $this->average($timeSamples);
        $summary['median_time_to_readiness_hours'] = $this->median($timeSamples);
        $summary['problematic_modules_total'] = collect($moduleStats)
            ->filter(static fn (array $module): bool => (int) ($module['blocked_steps_total'] ?? 0) > 0)
            ->count();

        $problematicModules = collect($moduleStats)
            ->map(function (array $module): array {
                $companiesTotal = (int) ($module['companies_total'] ?? 0);
                $blockedStepsTotal = (int) ($module['blocked_steps_total'] ?? 0);
                $affectedCompaniesTotal = (int) ($module['affected_companies_total'] ?? 0);

                return [
                    'key' => $module['key'],
                    'label' => $module['label'],
                    'blocked_steps_total' => $blockedStepsTotal,
                    'affected_companies_total' => $affectedCompaniesTotal,
                    'companies_total' => $companiesTotal,
                    'average_progress_percent' => $companiesTotal > 0
                        ? round(((float) ($module['progress_percent_total'] ?? 0.0)) / $companiesTotal, 2)
                        : 0.0,
                    'average_blocked_steps_per_company' => $companiesTotal > 0
                        ? round($blockedStepsTotal / $companiesTotal, 2)
                        : 0.0,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $blockedComparison = $right['blocked_steps_total'] <=> $left['blocked_steps_total'];

                if ($blockedComparison !== 0) {
                    return $blockedComparison;
                }

                $affectedComparison = $right['affected_companies_total'] <=> $left['affected_companies_total'];

                if ($affectedComparison !== 0) {
                    return $affectedComparison;
                }

                return strcmp($left['label'], $right['label']);
            })
            ->values()
            ->take(5)
            ->all();

        $slowestCompanies = collect($slowCompanies)
            ->sortByDesc('hours_to_readiness')
            ->values()
            ->take(5)
            ->all();

        $fastestCompanies = collect($slowCompanies)
            ->sortBy('hours_to_readiness')
            ->values()
            ->take(5)
            ->all();

        $blockedCompanies = collect($blockedCompanies)
            ->sort(function (array $left, array $right): int {
                $blockedComparison = $right['blocked_steps_total'] <=> $left['blocked_steps_total'];

                if ($blockedComparison !== 0) {
                    return $blockedComparison;
                }

                return $right['critical_blocks_total'] <=> $left['critical_blocks_total'];
            })
            ->values()
            ->take(5)
            ->all();

        return [
            'summary' => $summary,
            'time_to_readiness' => [
                'samples_total' => count($timeSamples),
                'average_hours' => $summary['average_time_to_readiness_hours'],
                'median_hours' => $summary['median_time_to_readiness_hours'],
                'slowest_companies' => $slowestCompanies,
                'fastest_companies' => $fastestCompanies,
            ],
            'problematic_modules' => $problematicModules,
            'blocked_companies' => $blockedCompanies,
        ];
    }

    private function resolvePlanLabel(?User $company): ?string
    {
        if (! $company?->active_plan) {
            return null;
        }

        return Plan::query()->find($company->active_plan)?->name;
    }

    private function durationHours(mixed $startedAt, mixed $completedAt): ?float
    {
        if (! is_string($startedAt) || trim($startedAt) === '') {
            return null;
        }

        if (! is_string($completedAt) || trim($completedAt) === '') {
            return null;
        }

        try {
            $started = CarbonImmutable::parse($startedAt);
            $completed = CarbonImmutable::parse($completedAt);
        } catch (\Throwable) {
            return null;
        }

        if ($completed->lessThan($started)) {
            return null;
        }

        return round($started->floatDiffInHours($completed), 2);
    }

    /**
     * @param array<int, float|int> $values
     */
    private function average(array $values): float
    {
        $values = array_values(array_filter($values, static fn ($value): bool => is_numeric($value)));

        if ($values === []) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 2);
    }

    /**
     * @param array<int, float|int> $values
     */
    private function median(array $values): float
    {
        $values = array_values(array_filter($values, static fn ($value): bool => is_numeric($value)));

        if ($values === []) {
            return 0.0;
        }

        sort($values);

        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return round((float) $values[$middle], 2);
        }

        return round(((float) $values[$middle - 1] + (float) $values[$middle]) / 2, 2);
    }
}
