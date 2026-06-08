import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    AlertTriangle,
    ArrowRight,
    BadgeCheck,
    BarChart3,
    ChevronRight,
    CreditCard,
    Gauge,
    HardDrive,
    Package,
    Sparkles,
    Users,
    Warehouse,
} from 'lucide-react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { formatAdminCurrency, formatDate, formatStorage } from '@/utils/helpers';

type StateTone = 'default' | 'secondary' | 'outline' | 'ghost';

interface MyPlanCardItem {
    key?: string;
    label?: string;
    reference?: string;
    type?: string;
    notes?: string | null;
    feature_count?: number;
    active?: boolean;
    included_in_plan?: boolean;
    state?: string;
    cta?: {
        href?: string | null;
        label?: string | null;
        message?: string | null;
        tone?: string | null;
        action?: string | null;
    } | null;
}

interface MyPlanPageProps {
    myPlan: {
        meta: {
            generated_at?: string;
            company_name?: string | null;
            plan_family_label?: string | null;
            feature_catalog_version?: string | null;
            limit_catalog_version?: string | null;
        };
        overview: {
            company_name?: string | null;
            plan_name?: string | null;
            plan_description?: string | null;
            plan_status?: string;
            plan_status_label?: string | null;
            billing_cycle?: string;
            billing_cycle_label?: string | null;
            expires_on?: string | null;
            trial_expires_on?: string | null;
            is_free?: boolean;
            monthly_price?: number;
            yearly_price?: number;
            users_limit?: number;
            storage_limit_kb?: number;
        };
        usage?: {
            summary?: {
                current_usage_total?: number;
            };
        };
        summary: {
            plan_modules_total?: number;
            active_modules_total?: number;
            addon_modules_total?: number;
            available_modules_total?: number;
            limit_dimensions_total?: number;
            limit_near_total?: number;
            limit_exceeded_total?: number;
            suggestions_total?: number;
        };
        modules: {
            included: MyPlanCardItem[];
            addons: MyPlanCardItem[];
        };
        limits: {
            summary: {
                dimensions_total?: number;
                near_limit_total?: number;
                exceeded_total?: number;
                current_usage_total?: number;
            };
            dimensions: Array<Record<string, any>>;
        };
        suggestions: Array<Record<string, any>>;
    };
}

const percentFormatter = new Intl.NumberFormat('pt-PT', {
    maximumFractionDigits: 1,
});

const numberFormatter = new Intl.NumberFormat('pt-PT');

function clampPercent(value: number | string | null | undefined): number {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric)) {
        return 0;
    }

    return Math.max(0, Math.min(100, numeric));
}

function formatPercent(value: number | string | null | undefined): string {
    return `${percentFormatter.format(clampPercent(value))}%`;
}

function toneClass(state: string | null | undefined): string {
    switch ((state ?? '').toLowerCase()) {
        case 'active':
        case 'ready':
        case 'within_limit':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'trial':
        case 'near_limit':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'expired':
        case 'exceeded':
        case 'blocked':
        case 'inactive':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        case 'addon':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'locked':
            return 'border-slate-200 bg-slate-50 text-slate-700';
        default:
            return 'border-slate-200 bg-white text-slate-700';
    }
}

function resolveBadgeTone(tone?: string | null): StateTone {
    switch ((tone ?? '').toLowerCase()) {
        case 'secondary':
            return 'secondary';
        case 'outline':
            return 'outline';
        case 'ghost':
            return 'ghost';
        default:
            return 'default';
    }
}

function labelState(state: string | null | undefined): string {
    switch ((state ?? '').toLowerCase()) {
        case 'active':
            return 'Activo';
        case 'addon':
            return 'Módulo extra';
        case 'locked':
            return 'Bloqueado';
        case 'inactive':
            return 'Inactivo';
        case 'trial':
            return 'Trial';
        case 'expired':
            return 'Expirado';
        case 'within_limit':
            return 'Dentro do limite';
        case 'near_limit':
            return 'Em risco';
        case 'exceeded':
            return 'Excedido';
        case 'hidden':
            return 'Oculto';
        default:
            return state ?? 'n/a';
    }
}

function formatDimensionValue(value: number | string | null | undefined, unit?: string | null, pageProps?: any): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const normalizedUnit = String(unit ?? '').toLowerCase();
    const numericValue = Number(value);

    if (Number.isNaN(numericValue)) {
        return String(value);
    }

    if (normalizedUnit.includes('kb') || normalizedUnit.includes('storage')) {
        return formatStorage(numericValue);
    }

    return numberFormatter.format(numericValue);
}

function sectionTone(kind?: string | null, state?: string | null, code?: string | null): string {
    const normalizedKind = String(kind ?? '').toLowerCase();
    const normalizedState = String(state ?? '').toLowerCase();
    const normalizedCode = String(code ?? '').toLowerCase();

    if (normalizedState === 'exceeded' || normalizedCode === 'subscription_expired') {
        return 'border-rose-200 bg-rose-50/80 text-rose-900';
    }

    if (normalizedState === 'near_limit' || normalizedCode === 'subscription_inactive') {
        return 'border-amber-200 bg-amber-50/80 text-amber-900';
    }

    if (normalizedKind === 'feature' && normalizedCode === 'addon_required') {
        return 'border-sky-200 bg-sky-50/80 text-sky-900';
    }

    return 'border-slate-200 bg-slate-50/80 text-slate-900';
}

function suggestionLabel(suggestion: Record<string, any>): string {
    return String(suggestion?.title ?? suggestion?.label ?? suggestion?.key ?? 'Sugestão');
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
        <div className="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
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

function ModuleRow({ module }: { module: MyPlanCardItem }) {
    const state = String(module.state ?? (module.active ? 'active' : 'locked'));

    return (
        <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div className="space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <p className="font-medium text-slate-900">{module.label ?? module.key}</p>
                    <Badge variant="outline" className={toneClass(state)}>
                        {labelState(state)}
                    </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                    {module.reference ? `${module.reference}` : module.type ?? 'core'}
                    {module.notes ? ` · ${module.notes}` : ''}
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                    {module.feature_count ?? 0} {module.feature_count === 1 ? 'funcionalidade' : 'funcionalidades'}
                </Badge>
            </div>
        </div>
    );
}

export default function MyPlanPage({ myPlan }: MyPlanPageProps) {
    const { t } = useTranslation();
    const { props } = usePage<any>();

    const meta = myPlan?.meta ?? {};
    const overview = myPlan?.overview ?? {};
    const summary = myPlan?.summary ?? {};
    const modules = myPlan?.modules ?? { included: [], addons: [] };
    const limits = myPlan?.limits ?? { summary: {}, dimensions: [] };
    const suggestions = myPlan?.suggestions ?? [];

    const planStatus = String(overview.plan_status ?? 'inactive');
    const planStatusLabel = overview.plan_status_label ?? planStatus;
    const billingCycleLabel = overview.billing_cycle_label ?? t('Mensal');
    const companyName = overview.company_name ?? meta.company_name ?? t('Empresa');
    const monthlyPrice = formatAdminCurrency(overview.monthly_price ?? 0, props);
    const yearlyPrice = formatAdminCurrency(overview.yearly_price ?? 0, props);
    const storageLimit = formatStorage(Number(overview.storage_limit_kb ?? 0));
    const expiresOn = overview.expires_on ? formatDate(overview.expires_on, props) : null;
    const trialExpiresOn = overview.trial_expires_on ? formatDate(overview.trial_expires_on, props) : null;
    const availableModulesTotal = Number(summary.available_modules_total ?? 0);
    const planModulesTotal = Number(summary.plan_modules_total ?? 0);
    const activeModulesTotal = Number(summary.active_modules_total ?? 0);
    const addonModulesTotal = Number(summary.addon_modules_total ?? 0);
    const limitDimensionsTotal = Number(summary.limit_dimensions_total ?? limits.summary?.dimensions_total ?? 0);
    const limitNearTotal = Number(summary.limit_near_total ?? limits.summary?.near_limit_total ?? 0);
    const limitExceededTotal = Number(summary.limit_exceeded_total ?? limits.summary?.exceeded_total ?? 0);
    const suggestionsTotal = Number(summary.suggestions_total ?? suggestions.length);
    const isExpiredOrInactive = planStatus === 'expired' || planStatus === 'inactive';

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Planos'), url: route('plans.index') },
                { label: t('Meu Plano') },
            ]}
            pageTitle={t('Meu Plano')}
            pageActions={
                <Button asChild variant="outline" size="sm">
                    <Link href={route('plans.index')}>
                        <CreditCard className="h-4 w-4" />
                        {t('Ver planos')}
                    </Link>
                </Button>
            }
        >
            <Head title={t('Meu Plano')} />

            <div className="space-y-6">
                <Card className="overflow-hidden border-slate-200 bg-gradient-to-br from-cyan-50 via-white to-emerald-50 shadow-sm">
                    <CardContent className="p-6 md:p-8">
                        <div className="grid gap-6 lg:grid-cols-[1.2fr_0.9fr] lg:items-start">
                            <div className="space-y-5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">
                                        {meta.plan_family_label ?? t('Plano')}
                                    </Badge>
                                    <Badge variant="outline" className={toneClass(planStatus)}>
                                        {planStatusLabel}
                                    </Badge>
                                    <Badge variant="outline" className="border-emerald-200 bg-emerald-50 text-emerald-700">
                                        {billingCycleLabel}
                                    </Badge>
                                    {overview.is_free && (
                                        <Badge variant="outline" className="border-indigo-200 bg-indigo-50 text-indigo-700">
                                            {t('Gratuito')}
                                        </Badge>
                                    )}
                                </div>

                                <div className="space-y-3">
                                    <p className="text-sm font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                        {companyName}
                                    </p>
                                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                                        {overview.plan_name ?? t('Sem plano activo')}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                                        {overview.plan_description
                                            ? overview.plan_description
                                            : t('Consulte aqui o estado da subscrição, módulos incluídos, add-ons activos, limites contratados e sugestões de evolução.')}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button asChild>
                                        <Link href={route('plans.index')}>
                                            <ArrowRight className="h-4 w-4" />
                                            {t('Ver planos e upgrades')}
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link href={route('dashboard')}>
                                            <BarChart3 className="h-4 w-4" />
                                            {t('Voltar ao dashboard')}
                                        </Link>
                                    </Button>
                                </div>

                                {isExpiredOrInactive && (
                                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                                        <div className="flex items-start gap-3">
                                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0" />
                                            <div className="space-y-1">
                                                <p className="font-medium">
                                                    {planStatus === 'expired'
                                                        ? t('A subscrição está expirada.')
                                                        : t('A empresa ainda não tem um plano activo.')}
                                                </p>
                                                <p className="text-sm text-amber-900/80">
                                                    {t('Esta página continua acessível para revisão, mas a operação normal pode estar limitada até renovar ou activar um plano.')}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <StatCard
                                    icon={<BadgeCheck className="h-4 w-4" />}
                                    label={t('Preço mensal')}
                                    value={monthlyPrice}
                                    note={overview.is_free ? t('Plano gratuito') : t('Valor base do plano')}
                                />
                                <StatCard
                                    icon={<CreditCard className="h-4 w-4" />}
                                    label={t('Preço anual')}
                                    value={yearlyPrice}
                                    note={overview.is_free ? t('Plano gratuito') : t('Valor anual estimado')}
                                />
                                <StatCard
                                    icon={<Users className="h-4 w-4" />}
                                    label={t('Utilizadores')}
                                    value={overview.users_limit === -1 ? t('Ilimitados') : numberFormatter.format(Number(overview.users_limit ?? 0))}
                                    note={t('Limite contratado')}
                                />
                                <StatCard
                                    icon={<HardDrive className="h-4 w-4" />}
                                    label={t('Armazenamento')}
                                    value={storageLimit}
                                    note={t('Limite contratado')}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={<Package className="h-4 w-4" />}
                        label={t('Módulos do plano')}
                        value={numberFormatter.format(planModulesTotal)}
                        note={availableModulesTotal > 0 ? t('{{count}} no catálogo', { count: availableModulesTotal }) : t('Catálogo indisponível')}
                    />
                    <StatCard
                        icon={<BadgeCheck className="h-4 w-4" />}
                        label={t('Módulos activos')}
                        value={numberFormatter.format(activeModulesTotal)}
                        note={t('Disponíveis para a empresa')}
                    />
                    <StatCard
                        icon={<Gauge className="h-4 w-4" />}
                        label={t('Limites monitorizados')}
                        value={numberFormatter.format(limitDimensionsTotal)}
                        note={t('{{count}} em risco', { count: limitNearTotal })}
                    />
                    <StatCard
                        icon={<AlertTriangle className="h-4 w-4" />}
                        label={t('Alertas de limite')}
                        value={numberFormatter.format(limitExceededTotal)}
                        note={suggestionsTotal > 0 ? t('{{count}} sugestões prontas', { count: suggestionsTotal }) : t('Sem alertas críticos')}
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Package className="h-5 w-5 text-sky-600" />
                                {t('Módulos incluídos no plano')}
                            </CardTitle>
                            <CardDescription>
                                {t('Estes módulos fazem parte da subscrição actual e devem estar activos para a empresa.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {modules.included.length > 0 ? (
                                modules.included.map((module, index) => (
                                    <ModuleRow key={`${module.key ?? module.label ?? 'module'}-${index}`} module={module} />
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-muted-foreground">
                                    {t('Ainda não existem módulos associados a este plano.')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Warehouse className="h-5 w-5 text-emerald-600" />
                                {t('Add-ons activos')}
                            </CardTitle>
                            <CardDescription>
                                {t('Estes módulos estão activos para a empresa mas não estão incluídos no núcleo do plano.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {modules.addons.length > 0 ? (
                                modules.addons.map((module, index) => (
                                    <ModuleRow key={`${module.key ?? module.label ?? 'addon'}-${index}`} module={module} />
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-muted-foreground">
                                    {t('Não existem add-ons activos para esta empresa.')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Gauge className="h-5 w-5 text-amber-600" />
                            {t('Limites e consumo')}
                        </CardTitle>
                        <CardDescription>
                            {t('{{dimensions}} dimensões monitorizadas · {{near}} em risco · {{exceeded}} excedidas', {
                                dimensions: Number(limits.summary?.dimensions_total ?? limitDimensionsTotal),
                                near: Number(limits.summary?.near_limit_total ?? limitNearTotal),
                                exceeded: Number(limits.summary?.exceeded_total ?? limitExceededTotal),
                            })}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {limits.dimensions.length > 0 ? (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {limits.dimensions.map((dimension, index) => {
                                    const state = String(dimension.state ?? 'hidden');
                                    const usagePercent = clampPercent(dimension.usage_percent ?? 0);
                                    const contractedValue = dimension.contracted_limit_display
                                        ?? formatDimensionValue(dimension.contracted_limit ?? null, dimension.unit, props);
                                    const currentValue = formatDimensionValue(dimension.current_usage ?? null, dimension.unit, props);
                                    const remainingValue = dimension.remaining === null || dimension.remaining === undefined
                                        ? null
                                        : formatDimensionValue(dimension.remaining, dimension.unit, props);

                                    return (
                                        <div key={`${dimension.key ?? 'dimension'}-${index}`} className={cn('rounded-2xl border p-4', sectionTone('limit', state, dimension.subscription_state as string | undefined))}>
                                            <div className="flex flex-col gap-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="space-y-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="font-medium">{dimension.label ?? dimension.key}</p>
                                                            <Badge variant="outline" className={toneClass(state)}>
                                                                {labelState(state)}
                                                            </Badge>
                                                        </div>
                                                        {dimension.description && (
                                                            <p className="text-sm text-muted-foreground">{dimension.description}</p>
                                                        )}
                                                    </div>
                                                    {dimension.cta?.href && (
                                                        <Button asChild size="sm" variant={resolveBadgeTone(dimension.cta?.tone)}>
                                                            <Link href={String(dimension.cta.href)}>
                                                                {dimension.cta.label ?? t('Abrir')}
                                                                <ChevronRight className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>

                                                <div className="grid gap-3 sm:grid-cols-3">
                                                    <div className="rounded-xl border border-white/70 bg-white/80 p-3">
                                                        <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Actual')}</p>
                                                        <p className="mt-2 text-base font-semibold">{currentValue}</p>
                                                    </div>
                                                    <div className="rounded-xl border border-white/70 bg-white/80 p-3">
                                                        <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Limite')}</p>
                                                        <p className="mt-2 text-base font-semibold">{String(contractedValue)}</p>
                                                    </div>
                                                    <div className="rounded-xl border border-white/70 bg-white/80 p-3">
                                                        <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t('Restante')}</p>
                                                        <p className="mt-2 text-base font-semibold">{remainingValue ?? t('Indisponível')}</p>
                                                    </div>
                                                </div>

                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                        <span>{t('Utilização')}</span>
                                                        <span>{formatPercent(dimension.usage_percent ?? 0)}</span>
                                                    </div>
                                                    <div className="h-2 w-full overflow-hidden rounded-full bg-white/80">
                                                        <div
                                                            className={cn(
                                                                'h-full rounded-full transition-all',
                                                                state === 'exceeded'
                                                                    ? 'bg-rose-500'
                                                                    : state === 'near_limit'
                                                                        ? 'bg-amber-500'
                                                                        : 'bg-emerald-500'
                                                            )}
                                                            style={{ width: `${usagePercent}%` }}
                                                        />
                                                    </div>
                                                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                        <span>{t('Enforcement')}: {String(dimension.enforcement ?? t('Manual'))}</span>
                                                        {dimension.unit && <span>• {dimension.unit}</span>}
                                                        {dimension.plan_name && <span>• {dimension.plan_name}</span>}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-muted-foreground">
                                {t('Ainda não existem dimensões de limite configuradas para este plano.')}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Sparkles className="h-5 w-5 text-violet-600" />
                            {t('Sugestões de configuração e upgrade')}
                        </CardTitle>
                        <CardDescription>
                            {t('A lista abaixo reúne os bloqueios e recomendações mais relevantes para esta empresa.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {suggestions.length > 0 ? (
                            <div className="space-y-3">
                                {suggestions.map((suggestion, index) => {
                                    const recommendation = suggestion.recommendation ?? {};
                                    const cta = suggestion.cta ?? null;
                                    const recommendedPlan = recommendation.recommended_plan?.name ?? recommendation.recommended_plan?.label ?? null;
                                    const recommendedAddons = Array.isArray(recommendation.recommended_addons)
                                        ? recommendation.recommended_addons
                                        : [];

                                    return (
                                        <div
                                            key={`${suggestion.kind ?? 'suggestion'}-${suggestion.key ?? index}`}
                                            className={cn('rounded-2xl border p-4 shadow-sm', sectionTone(suggestion.kind, suggestion.state, suggestion.reason_code))}
                                        >
                                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge variant="outline" className="border-white/60 bg-white/80 text-slate-700">
                                                            {String(suggestion.kind ?? t('Sugestão')) === 'limit' ? t('Limite') : t('Configuração')}
                                                        </Badge>
                                                        <Badge variant="outline" className={toneClass(suggestion.state)}>
                                                            {String(suggestion.reason_label ?? suggestion.reason_code ?? suggestion.state ?? 'n/a')}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-lg font-semibold text-slate-900">{suggestionLabel(suggestion)}</p>
                                                    <p className="text-sm leading-6 text-slate-700">
                                                        {suggestion.message ?? recommendation.message ?? t('Não existe descrição adicional.')}
                                                    </p>

                                                    <div className="flex flex-wrap gap-2">
                                                        {recommendedPlan && (
                                                            <Badge variant="outline" className="border-white/70 bg-white/80 text-slate-700">
                                                                {t('Plano recomendado')}: {recommendedPlan}
                                                            </Badge>
                                                        )}
                                                        {recommendedAddons.map((addon: any, addonIndex: number) => (
                                                            <Badge
                                                                key={`${suggestion.key ?? index}-addon-${addonIndex}`}
                                                                variant="outline"
                                                                className="border-white/70 bg-white/80 text-slate-700"
                                                            >
                                                                {String(addon?.label ?? addon?.key ?? addon)}
                                                            </Badge>
                                                        ))}
                                                    </div>

                                                    {cta?.message && (
                                                        <p className="text-xs text-slate-600">
                                                            {cta.message}
                                                        </p>
                                                    )}
                                                </div>

                                                {cta?.href && (
                                                    <Button asChild variant={resolveBadgeTone(cta.tone)} className="shrink-0">
                                                        <Link href={String(cta.href)}>
                                                            {cta.label ?? t('Abrir')}
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-muted-foreground">
                                {t('Sem sugestões adicionais no momento.')}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-slate-200 bg-slate-50 shadow-sm">
                    <CardContent className="flex flex-col gap-2 p-4 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-slate-900">
                                {t('Actualizado em {{date}}', {
                                    date: meta.generated_at ? formatDate(meta.generated_at, props) : t('n/a'),
                                })}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {t('Catálogo de features {{featureVersion}} · Catálogo de limites {{limitVersion}}', {
                                    featureVersion: meta.feature_catalog_version ?? t('n/a'),
                                    limitVersion: meta.limit_catalog_version ?? t('n/a'),
                                })}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
