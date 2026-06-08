<?php

namespace App\Services\AssistantActivation;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CompanyProgressOverviewService
{
    public function __construct(
        private readonly OnboardingDashboardService $onboardingDashboardService,
        private readonly ActivationMetricsService $activationMetricsService
    ) {
    }

    public function snapshot(User $viewer, array $filters = []): array
    {
        $this->authorize($viewer);

        $search = trim((string) Arr::get($filters, 'search', ''));
        $selectedCompanyId = (int) Arr::get($filters, 'company_id', 0);
        $perPage = max(5, min(24, (int) Arr::get($filters, 'per_page', 8)));
        $page = max(1, (int) Arr::get($filters, 'page', 1));

        $companyQuery = User::query()
            ->where('type', 'company');

        if ($search !== '') {
            $companyQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $metricsCompanies = (clone $companyQuery)
            ->orderBy('name')
            ->get();

        $paginator = (clone $companyQuery)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $companyCards = collect($paginator->items())
            ->map(fn (User $company): array => $this->presentCompany($company, $search))
            ->values()
            ->all();

        $selectedCompany = $this->resolveSelectedCompany($selectedCompanyId, $companyCards, $search);
        $selectedSnapshot = $selectedCompany ? $this->onboardingDashboardService->snapshot($selectedCompany) : null;

        if (! $selectedCompany && $companyCards !== []) {
            $fallbackCompany = User::query()->where('type', 'company')->find((int) $companyCards[0]['id']);
            if ($fallbackCompany) {
                $selectedCompany = $fallbackCompany;
                $selectedSnapshot = $this->onboardingDashboardService->snapshot($fallbackCompany);
            }
        }

        $companyMetrics = collect($companyCards);
        $averageReadiness = $companyMetrics->count() > 0
            ? round((float) $companyMetrics->avg('readiness_score'), 2)
            : 0.0;

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'viewer_id' => $viewer->id,
                'viewer_name' => $viewer->name,
                'search' => $search,
                'selected_company_id' => $selectedCompany?->id,
                'selected_company_name' => $selectedCompany?->name,
                'page' => $paginator->currentPage(),
            ],
            'summary' => [
                'companies_total' => $paginator->total(),
                'companies_in_view' => count($companyCards),
                'average_readiness' => $averageReadiness,
                'ready_companies' => $companyMetrics->where('readiness_state', 'ready')->count(),
                'blocked_companies' => $companyMetrics->where('critical_blocks_total', '>', 0)->count(),
                'new_companies' => $companyMetrics->where('is_new_company', true)->count(),
            ],
            'filters' => [
                'search' => $search,
                'company_id' => $selectedCompany?->id,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'companies' => $companyCards,
            'metrics' => $this->activationMetricsService->calculate($metricsCompanies),
            'selected_company' => $selectedCompany ? $this->presentSelectedCompany($selectedCompany, $selectedSnapshot) : null,
        ];
    }

    private function authorize(User $viewer): void
    {
        if (! $viewer->isSuperAdminUser() && ! $viewer->can('view-company-onboarding-progress')) {
            throw new InvalidArgumentException('Permission denied.');
        }
    }

    private function presentCompany(User $company, string $search): array
    {
        $snapshot = $this->onboardingDashboardService->snapshot($company);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'plan_label' => data_get($snapshot, 'meta.plan_label'),
            'session_status' => data_get($snapshot, 'meta.session_status'),
            'session_status_label' => data_get($snapshot, 'meta.session_status_label'),
            'readiness_state' => data_get($snapshot, 'summary.readiness_state'),
            'readiness_state_label' => data_get($snapshot, 'summary.readiness_state_label'),
            'readiness_score' => (float) data_get($snapshot, 'summary.overall_score', 0),
            'progress_percent' => (float) data_get($snapshot, 'summary.progress_percent', 0),
            'critical_blocks_total' => (int) data_get($snapshot, 'summary.critical_blocks_total', 0),
            'available_steps_total' => (int) data_get($snapshot, 'summary.available_steps_total', 0),
            'required_steps_total' => (int) data_get($snapshot, 'summary.required_steps_total', 0),
            'completed_required_steps_total' => (int) data_get($snapshot, 'summary.completed_required_steps_total', 0),
            'is_new_company' => (bool) data_get($snapshot, 'meta.is_new_company', false),
            'top_block' => data_get($snapshot, 'top_blocks.0'),
            'next_action' => data_get($snapshot, 'next_action'),
            'select_url' => route('assistant-activation.company-progress.index', array_filter([
                'company_id' => $company->id,
                'search' => $search !== '' ? $search : null,
            ], static fn ($value): bool => $value !== null && $value !== '')),
        ];
    }

    private function presentSelectedCompany(User $company, ?array $snapshot): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'snapshot' => $snapshot,
            'progress_percent' => (float) data_get($snapshot, 'summary.progress_percent', 0),
            'readiness_score' => (float) data_get($snapshot, 'summary.overall_score', 0),
            'critical_blocks_total' => (int) data_get($snapshot, 'summary.critical_blocks_total', 0),
            'top_blocks' => array_values((array) data_get($snapshot, 'top_blocks', [])),
            'module_snapshots' => array_values((array) data_get($snapshot, 'module_snapshots', [])),
            'next_action' => data_get($snapshot, 'next_action'),
            'session_status' => data_get($snapshot, 'meta.session_status'),
            'session_status_label' => data_get($snapshot, 'meta.session_status_label'),
            'plan_label' => data_get($snapshot, 'meta.plan_label'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $companyCards
     */
    private function resolveSelectedCompany(int $selectedCompanyId, array $companyCards, string $search): ?User
    {
        if ($selectedCompanyId > 0) {
            $company = User::query()
                ->where('type', 'company')
                ->find($selectedCompanyId);

            if ($company instanceof User) {
                return $company;
            }
        }

        if ($companyCards !== []) {
            $firstCompanyId = (int) data_get($companyCards, '0.id');

            if ($firstCompanyId > 0) {
                return User::query()
                    ->where('type', 'company')
                    ->find($firstCompanyId);
            }
        }

        return null;
    }
}
