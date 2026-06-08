import { Head, router } from '@inertiajs/react';
import { type ReactNode, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    AlertTriangle,
    ArrowRight,
    BadgeCheck,
    BarChart3,
    Building2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Gauge,
    RefreshCw,
    Search,
    Sparkles,
    Target,
} from 'lucide-react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

interface CompanyProgressCard {
    id: number;
    name: string;
    email?: string | null;
    plan_label?: string | null;
    session_status?: string | null;
    session_status_label?: string | null;
    readiness_state?: string | null;
    readiness_state_label?: string | null;
    readiness_score?: number;
    progress_percent?: number;
    critical_blocks_total?: number;
    available_steps_total?: number;
    required_steps_total?: number;
    completed_required_steps_total?: number;
    is_new_company?: boolean;
    top_block?: Record<string, any> | null;
    next_action?: Record<string, any> | null;
    select_url?: string;
}

interface ActivationMetricModule {
    key: string;
    label?: string | null;
    blocked_steps_total?: number;
    affected_companies_total?: number;
    companies_total?: number;
    average_progress_percent?: number;
    average_blocked_steps_per_company?: number;
}

interface ActivationMetricCompany {
    company_id: number;
    company_name?: string | null;
    blocked_steps_total?: number;
    critical_blocks_total?: number;
    readiness_score?: number;
    progress_percent?: number;
    hours_to_readiness?: number;
    days_to_readiness?: number;
}

interface ActivationMetrics {
    summary: {
        companies_total?: number;
        ready_companies_total?: number;
        completed_companies_total?: number;
        active_companies_total?: number;
        not_started_companies_total?: number;
        companies_with_blockers_total?: number;
        blocked_steps_total?: number;
        critical_blocks_total?: number;
        average_readiness_score?: number;
        average_progress_percent?: number;
        average_time_to_readiness_hours?: number;
        median_time_to_readiness_hours?: number;
        problematic_modules_total?: number;
    };
    time_to_readiness?: {
        samples_total?: number;
        average_hours?: number;
        median_hours?: number;
        slowest_companies?: ActivationMetricCompany[];
        fastest_companies?: ActivationMetricCompany[];
    };
    problematic_modules?: ActivationMetricModule[];
    blocked_companies?: ActivationMetricCompany[];
}

interface CompanyProgressOverview {
    meta: {
        generated_at?: string;
        viewer_name?: string | null;
        search?: string;
        selected_company_id?: number | null;
        selected_company_name?: string | null;
    };
    summary: {
        companies_total?: number;
        companies_in_view?: number;
        average_readiness?: number;
        ready_companies?: number;
        blocked_companies?: number;
        new_companies?: number;
    };
    filters: {
        search?: string;
        company_id?: number | null;
        per_page?: number;
    };
    pagination: {
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        from?: number | null;
        to?: number | null;
    };
    companies: CompanyProgressCard[];
    metrics?: ActivationMetrics;
    selected_company?: {
        id: number;
        name: string;
        email?: string | null;
        plan_label?: string | null;
        session_status?: string | null;
        session_status_label?: string | null;
        progress_percent?: number;
        readiness_score?: number;
        critical_blocks_total?: number;
        top_blocks?: Array<Record<string, any>>;
        module_snapshots?: Array<Record<string, any>>;
        next_action?: Record<string, any> | null;
        snapshot?: Record<string, any> | null;
    } | null;
}

interface Props {
    overview: CompanyProgressOverview;
}

const percentFormatter = new Intl.NumberFormat('pt-PT', {
    maximumFractionDigits: 1,
});

function clampPercent(value: number | string | null | undefined): number {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric)) {
        return 0;
    }

    return Math.max(0, Math.min(100, numeric));
}

function formatPercentValue(value: number | string | null | undefined): string {
    return `${percentFormatter.format(clampPercent(value))}%`;
}

function formatHoursValue(value: number | string | null | undefined): string {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric)) {
        return '0h';
    }

    if (numeric >= 24) {
        return `${percentFormatter.format(numeric / 24)}d`;
    }

    return `${percentFormatter.format(numeric)}h`;
}

function toneClass(state: string | null | undefined): string {
    switch ((state ?? '').toLowerCase()) {
        case 'ready':
        case 'completed':
        case 'active':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'warning':
        case 'in_progress':
        case 'trial':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'blocked':
        case 'critical':
        case 'expired':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
}

function StatCard({
    icon,
    label,
    value,
    note,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    note?: string;
}) {
    return (
        <div className="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-sm backdrop-blur">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{label}</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900">{value}</p>
                    {note && <p className="mt-1 text-xs text-muted-foreground">{note}</p>}
                </div>
                <div className="rounded-xl bg-slate-900/5 p-2 text-slate-700">{icon}</div>
            </div>
        </div>
    );
}

function ProgressBar({ value }: { value: number | string | null | undefined }) {
    const percent = clampPercent(value);

    return (
        <div className="h-2 w-full rounded-full bg-slate-100">
            <div
                className={cn('h-2 rounded-full transition-all', percent >= 100 ? 'bg-emerald-500' : percent >= 60 ? 'bg-cyan-500' : percent > 0 ? 'bg-amber-500' : 'bg-slate-300')}
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}

export default function CompanyProgress({ overview }: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(overview.filters.search ?? '');
    const [perPage, setPerPage] = useState(String(overview.filters.per_page ?? 8));
    const selectedCompanyId = overview.filters.company_id ? String(overview.filters.company_id) : '';
    const companies = overview.companies ?? [];
    const metrics: ActivationMetrics = overview.metrics ?? {
        summary: {},
        time_to_readiness: {},
        problematic_modules: [],
        blocked_companies: [],
    };
    const selected = overview.selected_company ?? null;

    useEffect(() => {
        setSearch(overview.filters.search ?? '');
        setPerPage(String(overview.filters.per_page ?? 8));
    }, [overview.filters.search, overview.filters.per_page]);

    const paginationLabel = useMemo(() => {
        const from = overview.pagination.from ?? 0;
        const to = overview.pagination.to ?? 0;
        const total = overview.pagination.total ?? 0;

        if (total === 0) {
            return t('No companies found');
        }

        return t('{{from}}-{{to}} of {{total}} companies', { from, to, total });
    }, [overview.pagination.from, overview.pagination.to, overview.pagination.total, t]);

    const submitFilters = (nextSearch = search, nextPerPage = perPage, nextPage = 1): void => {
        router.get(route('assistant-activation.company-progress.index'), {
            search: nextSearch || undefined,
            company_id: selectedCompanyId || undefined,
            per_page: Number(nextPerPage),
            page: nextPage,
        }, {
            preserveScroll: true,
            preserveState: false,
            replace: true,
        });
    };

    const selectCompany = (companyId: number): void => {
        router.get(route('assistant-activation.company-progress.index'), {
            search: search || undefined,
            company_id: companyId,
            per_page: Number(perPage),
            page: overview.pagination.current_page ?? 1,
        }, {
            preserveScroll: true,
            preserveState: false,
            replace: true,
        });
    };

    const goToPage = (page: number): void => {
        if (page < 1 || page > (overview.pagination.last_page ?? 1)) {
            return;
        }

        router.get(route('assistant-activation.company-progress.index'), {
            search: search || undefined,
            company_id: selectedCompanyId || undefined,
            per_page: Number(perPage),
            page,
        }, {
            preserveScroll: true,
            preserveState: false,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Company progress') }]}
            pageTitle={t('Company progress')}
                pageActions={(
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => router.reload()}
                >
                    <RefreshCw className="h-4 w-4" />
                    {t('Refresh')}
                </Button>
            )}
        >
            <Head title={t('Company progress')} />

            <div className="space-y-6">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={<Building2 className="h-5 w-5" />}
                        label={t('Companies in scope')}
                        value={String(overview.summary.companies_total ?? 0)}
                        note={t('{{count}} currently visible on the current page', { count: overview.summary.companies_in_view ?? 0 })}
                    />
                    <StatCard
                        icon={<Gauge className="h-5 w-5" />}
                        label={t('Average readiness')}
                        value={formatPercentValue(overview.summary.average_readiness ?? 0)}
                        note={t('Current page average')}
                    />
                    <StatCard
                        icon={<BadgeCheck className="h-5 w-5" />}
                        label={t('Ready companies')}
                        value={String(overview.summary.ready_companies ?? 0)}
                        note={t('Companies with no critical blocks')}
                    />
                    <StatCard
                        icon={<AlertTriangle className="h-5 w-5" />}
                        label={t('Blocked companies')}
                        value={String(overview.summary.blocked_companies ?? 0)}
                        note={t('Companies with pending critical items')}
                    />
                </div>

                <div className="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-cyan-50/60 p-5 shadow-sm">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{t('Activation metrics')}</p>
                            <h2 className="mt-2 text-lg font-semibold text-slate-900">{t('Time to readiness, blocked steps and problematic modules')}</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('Aggregated across all companies in the current filter scope.')}
                            </p>
                        </div>
                        <Badge variant="outline" className="border-slate-200 bg-white text-slate-700">
                            {t('{{count}} time samples', { count: metrics.time_to_readiness?.samples_total ?? 0 })}
                        </Badge>
                    </div>

                    <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            icon={<Clock3 className="h-5 w-5" />}
                            label={t('Average time to readiness')}
                            value={formatHoursValue(metrics.summary?.average_time_to_readiness_hours ?? 0)}
                            note={t('Median {{value}}', { value: formatHoursValue(metrics.summary?.median_time_to_readiness_hours ?? 0) })}
                        />
                        <StatCard
                            icon={<AlertTriangle className="h-5 w-5" />}
                            label={t('Blocked steps')}
                            value={String(metrics.summary?.blocked_steps_total ?? 0)}
                            note={t('{{count}} companies with blockers', { count: metrics.summary?.companies_with_blockers_total ?? 0 })}
                        />
                        <StatCard
                            icon={<BarChart3 className="h-5 w-5" />}
                            label={t('Problematic modules')}
                            value={String(metrics.summary?.problematic_modules_total ?? 0)}
                            note={t('Modules with blocked steps')}
                        />
                        <StatCard
                            icon={<Gauge className="h-5 w-5" />}
                            label={t('Average readiness')}
                            value={formatPercentValue(metrics.summary?.average_readiness_score ?? 0)}
                            note={t('All companies in scope')}
                        />
                    </div>

                    <div className="mt-6 grid gap-6 xl:grid-cols-2">
                        <Card className="border-slate-200 bg-white shadow-sm">
                            <CardHeader>
                                <CardTitle>{t('Most problematic modules')}</CardTitle>
                                <CardDescription>{t('Modules sorted by blocked steps and impacted companies.')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {(metrics.problematic_modules ?? []).length === 0 ? (
                                    <p className="text-sm text-muted-foreground">{t('No blocked modules were detected in the current scope.')}</p>
                                ) : (
                                    metrics.problematic_modules!.map((module) => (
                                        <div key={module.key} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-900">{module.label ?? module.key}</p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {t('{{count}} impacted company(ies)', { count: module.affected_companies_total ?? 0 })}
                                                    </p>
                                                </div>
                                                <Badge variant="outline" className="border-rose-200 bg-rose-50 text-rose-700">
                                                    {t('{{count}} blocked step(s)', { count: module.blocked_steps_total ?? 0 })}
                                                </Badge>
                                            </div>
                                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Average progress')}</p>
                                                    <p className="mt-1 text-sm font-semibold text-slate-900">{formatPercentValue(module.average_progress_percent ?? 0)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Blocked per company')}</p>
                                                    <p className="mt-1 text-sm font-semibold text-slate-900">
                                                        {percentFormatter.format(Number(module.average_blocked_steps_per_company ?? 0))}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-slate-200 bg-white shadow-sm">
                            <CardHeader>
                                <CardTitle>{t('Slowest companies to readiness')}</CardTitle>
                                <CardDescription>{t('Companies that took the longest to complete onboarding.')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {(metrics.time_to_readiness?.slowest_companies ?? []).length === 0 ? (
                                    <p className="text-sm text-muted-foreground">{t('No completed readiness samples yet.')}</p>
                                ) : (
                                    metrics.time_to_readiness!.slowest_companies!.map((company) => (
                                        <div key={company.company_id} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-900">{company.company_name ?? t('Company')}</p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {t('{{days}} day(s)', { days: percentFormatter.format(Number(company.days_to_readiness ?? 0)) })}
                                                    </p>
                                                </div>
                                                <Badge variant="outline" className="border-slate-200 bg-white text-slate-700">
                                                    {formatHoursValue(company.hours_to_readiness ?? 0)}
                                                </Badge>
                                            </div>
                                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Readiness')}</p>
                                                    <p className="mt-1 text-sm font-semibold text-slate-900">{formatPercentValue(company.readiness_score ?? 0)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Blocked steps')}</p>
                                                    <p className="mt-1 text-sm font-semibold text-slate-900">{company.blocked_steps_total ?? 0}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="space-y-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <CardTitle>{t('Companies')}</CardTitle>
                                    <CardDescription>{paginationLabel}</CardDescription>
                                </div>
                                <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                    {t('Page {{page}} of {{total}}', { page: overview.pagination.current_page ?? 1, total: overview.pagination.last_page ?? 1 })}
                                </Badge>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder={t('Search company by name or email')}
                                        className="pl-9"
                                        onKeyDown={(event) => {
                                            if (event.key === 'Enter') {
                                                event.preventDefault();
                                                submitFilters();
                                            }
                                        }}
                                    />
                                </div>
                                <Select value={perPage} onValueChange={(value) => {
                                    setPerPage(value);
                                    submitFilters(search, value, 1);
                                }}>
                                    <SelectTrigger className="w-full sm:w-[120px]">
                                        <SelectValue placeholder={t('Per page')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {['5', '8', '12', '24'].map((value) => (
                                            <SelectItem key={value} value={value}>
                                                {value}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button
                                    variant="default"
                                    onClick={() => submitFilters()}
                                >
                                    {t('Search')}
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-3">
                            {companies.length === 0 ? (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                        <Sparkles className="h-5 w-5" />
                                    </div>
                                    <p className="mt-4 text-sm font-medium text-slate-900">{t('No companies found')}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">{t('Try adjusting the search term or page size.')}</p>
                                </div>
                            ) : (
                                companies.map((company) => (
                                    <button
                                        key={company.id}
                                        type="button"
                                        onClick={() => selectCompany(company.id)}
                                        className={cn(
                                            'w-full rounded-2xl border p-4 text-left transition hover:border-primary hover:bg-slate-50',
                                            selected?.id === company.id ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : 'border-slate-200 bg-white'
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="truncate text-sm font-semibold text-slate-900">{company.name}</p>
                                                    <Badge variant="outline" className={toneClass(company.session_status)}>
                                                        {company.session_status_label ?? company.session_status ?? t('Unknown')}
                                                    </Badge>
                                                    {company.is_new_company && (
                                                        <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">
                                                            {t('New company')}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">{company.email ?? t('No email')}</p>
                                            </div>
                                            <ChevronRight className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                        </div>

                                        <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Readiness')}</p>
                                                <div className="mt-2 flex items-center gap-2">
                                                    <span className="text-sm font-semibold text-slate-900">{formatPercentValue(company.readiness_score ?? 0)}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Progress')}</p>
                                                <div className="mt-2 flex items-center gap-2">
                                                    <span className="text-sm font-semibold text-slate-900">{formatPercentValue(company.progress_percent ?? 0)}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Critical')}</p>
                                                <p className="mt-2 text-sm font-semibold text-slate-900">{company.critical_blocks_total ?? 0}</p>
                                            </div>
                                        </div>

                                        <div className="mt-4 space-y-2">
                                            <ProgressBar value={company.progress_percent ?? 0} />
                                            <div className="flex items-center justify-between text-[11px] text-muted-foreground">
                                                <span>{company.plan_label ?? t('No active plan')}</span>
                                                <span>{company.next_action?.label ?? t('Review')}</span>
                                            </div>
                                        </div>
                                    </button>
                                ))
                            )}

                            <div className="flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                                <p className="text-xs text-muted-foreground">
                                    {t('Showing {{from}} to {{to}} of {{total}}', {
                                        from: overview.pagination.from ?? 0,
                                        to: overview.pagination.to ?? 0,
                                        total: overview.pagination.total ?? 0,
                                    })}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => goToPage((overview.pagination.current_page ?? 1) - 1)}
                                        disabled={(overview.pagination.current_page ?? 1) <= 1}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        {t('Previous')}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => goToPage((overview.pagination.current_page ?? 1) + 1)}
                                        disabled={(overview.pagination.current_page ?? 1) >= (overview.pagination.last_page ?? 1)}
                                    >
                                        {t('Next')}
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle>{t('Selected company')}</CardTitle>
                                <CardDescription>{t('Inspect readiness, pending items and module coverage.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {selected ? (
                                    <div className="space-y-5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="outline" className={toneClass(selected.session_status)}>
                                                {selected.session_status_label ?? selected.session_status ?? t('Unknown')}
                                            </Badge>
                                            <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">
                                                {selected.plan_label ?? t('No active plan')}
                                            </Badge>
                                            <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                                {formatPercentValue(selected.readiness_score ?? 0)}
                                            </Badge>
                                        </div>

                                        <div>
                                            <h2 className="text-2xl font-semibold text-slate-900">{selected.name}</h2>
                                            <p className="mt-1 text-sm text-muted-foreground">{selected.email ?? t('No email')}</p>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Progress')}</p>
                                                <p className="mt-2 text-2xl font-semibold text-slate-900">{formatPercentValue(selected.progress_percent ?? 0)}</p>
                                                <div className="mt-3">
                                                    <ProgressBar value={selected.progress_percent ?? 0} />
                                                </div>
                                            </div>
                                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">{t('Critical pending')}</p>
                                                <p className="mt-2 text-2xl font-semibold text-slate-900">{selected.critical_blocks_total ?? 0}</p>
                                                <p className="mt-2 text-sm text-muted-foreground">{t('Pending items that require attention before go-live.')}</p>
                                            </div>
                                        </div>

                                        {selected.next_action && (
                                            <div className="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div className="flex items-start gap-3">
                                                    <div className="rounded-xl bg-slate-900/5 p-2 text-slate-700">
                                                        <Target className="h-4 w-4" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{t('Recommended next action')}</p>
                                                        <p className="mt-2 text-sm font-semibold text-slate-900">{selected.next_action.label ?? t('Review')}</p>
                                                        <p className="mt-1 text-sm text-muted-foreground">{selected.next_action.message ?? t('No additional details.')}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        <div className="grid gap-4 lg:grid-cols-2">
                                            <div className="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div className="flex items-center gap-2">
                                                    <AlertTriangle className="h-4 w-4 text-rose-500" />
                                                    <h3 className="text-sm font-semibold text-slate-900">{t('Top pending items')}</h3>
                                                </div>
                                                <div className="mt-4 space-y-3">
                                                    {(selected.top_blocks ?? []).length === 0 ? (
                                                        <p className="text-sm text-muted-foreground">{t('No critical blocks detected.')}</p>
                                                    ) : (
                                                        selected.top_blocks!.map((block, index) => (
                                                            <div key={`${block.key ?? block.code ?? index}`} className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                <div className="flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <p className="text-sm font-medium text-slate-900">{block.label ?? t('Pending item')}</p>
                                                                        <p className="mt-1 text-xs text-muted-foreground">{block.message ?? block.reason ?? t('No description provided.')}</p>
                                                                    </div>
                                                                    <Badge variant="outline" className={toneClass(block.state)}>
                                                                        {block.state_label ?? block.code ?? block.type ?? t('Block')}
                                                                    </Badge>
                                                                </div>
                                                            </div>
                                                        ))
                                                    )}
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div className="flex items-center gap-2">
                                                    <BarChart3 className="h-4 w-4 text-cyan-600" />
                                                    <h3 className="text-sm font-semibold text-slate-900">{t('Module coverage')}</h3>
                                                </div>
                                                <div className="mt-4 space-y-3">
                                                    {(selected.module_snapshots ?? []).length === 0 ? (
                                                        <p className="text-sm text-muted-foreground">{t('No module snapshot available.')}</p>
                                                    ) : (
                                                        selected.module_snapshots!.map((module, index) => (
                                                            <div key={`${module.key ?? index}`} className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                                <div className="flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <p className="text-sm font-medium text-slate-900">{module.label ?? t('Module')}</p>
                                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                                            {t('{{count}} available step(s)', { count: module.available_step_count ?? 0 })}
                                                                        </p>
                                                                    </div>
                                                                    <span className="text-sm font-semibold text-slate-900">
                                                                        {formatPercentValue(module.progress_percent ?? 0)}
                                                                    </span>
                                                                </div>
                                                                <div className="mt-3">
                                                                    <ProgressBar value={module.progress_percent ?? 0} />
                                                                </div>
                                                            </div>
                                                        ))
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                                        <Clock3 className="mx-auto h-8 w-8 text-slate-400" />
                                        <p className="mt-4 text-sm font-medium text-slate-900">{t('Select a company')}</p>
                                        <p className="mt-1 text-sm text-muted-foreground">{t('Choose a company from the list to inspect its readiness.')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
