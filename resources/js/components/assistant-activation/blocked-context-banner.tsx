import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, BadgeCheck, Package, Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { getPackageAlias } from '@/utils/helpers';

type BlockedContext = {
    label?: string | null;
    message?: string | null;
    summary?: string | null;
    block?: {
        code?: string | null;
        label?: string | null;
        details?: Record<string, any> | null;
    } | null;
    reasons?: string[] | null;
    recommendation?: {
        action?: string | null;
        label?: string | null;
        message?: string | null;
        reason_label?: string | null;
        reason_details?: Record<string, any> | null;
        recommended_plan?: {
            name?: string | null;
            family_label?: string | null;
            family?: string | null;
        } | null;
        recommended_addons?: Array<Record<string, any>>;
        recommended_permissions?: string[];
        recommended_config_keys?: string[];
        alternatives?: Array<Record<string, any>>;
    } | null;
    limit_key?: string | null;
    current_usage?: number | null;
    contracted_limit?: number | string | null;
    contracted_limit_display?: string | null;
    remaining?: number | null;
    usage_percent?: number | null;
    threshold_percent?: number | null;
    unlimited?: boolean | null;
    plan_family?: string | null;
    plan_family_label?: string | null;
    plan_name?: string | null;
    plan_id?: number | string | null;
    plan_expire_date?: string | null;
    trial_expire_date?: string | null;
    subscription_state?: string | null;
    state?: string | null;
    usage_breakdown?: Record<string, any> | null;
    suggestion?: {
        block?: {
            code?: string | null;
            label?: string | null;
            details?: Record<string, any> | null;
        } | null;
        recommendation?: {
            action?: string | null;
            label?: string | null;
            message?: string | null;
            reason_label?: string | null;
            reason_details?: Record<string, any> | null;
            recommended_plan?: {
                name?: string | null;
                family_label?: string | null;
                family?: string | null;
            } | null;
            recommended_addons?: Array<Record<string, any>>;
            recommended_permissions?: string[];
            recommended_config_keys?: string[];
            alternatives?: Array<Record<string, any>>;
        } | null;
    } | null;
    cta?: {
        action?: string | null;
        label?: string | null;
        href?: string | null;
        message?: string | null;
        tone?: string | null;
    } | null;
    moduleLabels?: string[] | null;
    moduleLabel?: string | null;
    moduleKeys?: string[] | null;
    moduleKey?: string | null;
    module_name?: string | null;
    blockCount?: number | null;
};

type NormalizedSuggestion = {
    kind: 'feature' | 'module' | 'limit' | 'subscription';
    title: string;
    reasonLabel: string;
    reasonCode: string;
    message: string;
    recommendation: NonNullable<BlockedContext['recommendation']>;
    cta: NonNullable<BlockedContext['cta']> | null;
    moduleLabels: string[];
    limitSummary: {
        usageLabel: string;
        planLabel: string;
        subscriptionLabel: string;
        remainingLabel: string | null;
        percentLabel: string | null;
    } | null;
    subscriptionSummary: {
        stateLabel: string;
        planLabel: string;
        familyLabel: string | null;
        expiresLabel: string | null;
        trialExpiresLabel: string | null;
    } | null;
};

function arrayify(values: unknown): string[] {
    if (!Array.isArray(values)) {
        return [];
    }

    return values
        .map((value) => String(value).trim())
        .filter((value) => value !== '');
}

function pickLabel(context: BlockedContext): string {
    return String(
        context.label
            ?? context.suggestion?.block?.label
            ?? context.block?.label
            ?? 'Bloqueio'
    );
}

function pickReasonLabel(context: BlockedContext): string {
    return String(
        context.suggestion?.recommendation?.reason_label
            ?? context.recommendation?.reason_label
            ?? context.suggestion?.block?.label
            ?? context.block?.label
            ?? context.summary
            ?? 'Razão indisponível'
    );
}

function pickReasonCode(context: BlockedContext): string {
    return String(
        context.suggestion?.block?.code
            ?? context.block?.code
            ?? context.reasons?.[0]
            ?? 'blocked'
    );
}

function pickMessage(context: BlockedContext): string {
    return String(
        context.message
            ?? context.summary
            ?? context.suggestion?.recommendation?.message
            ?? context.recommendation?.message
            ?? 'A operação está bloqueada.'
    );
}

function pickRecommendation(context: BlockedContext): NonNullable<BlockedContext['recommendation']> {
    return (
        context.suggestion?.recommendation
        ?? context.recommendation
        ?? {
            action: 'no_action',
            label: 'Sem acção',
            message: 'Não existe recomendação disponível.',
            recommended_addons: [],
            recommended_permissions: [],
            recommended_config_keys: [],
            alternatives: [],
        }
    );
}

function pickCta(context: BlockedContext): NonNullable<BlockedContext['cta']> | null {
    return context.cta ?? null;
}

function pickModuleLabels(context: BlockedContext): string[] {
    return Array.from(new Set([
        ...arrayify(context.moduleLabels),
        ...arrayify(context.moduleLabel ? [context.moduleLabel] : []),
        ...arrayify(context.module_name ? [context.module_name] : []),
        ...arrayify(context.suggestion?.recommendation?.recommended_addons?.map((addon) => addon?.label ?? addon?.reference ?? addon?.key)),
        ...arrayify(context.recommendation?.recommended_addons?.map((addon) => addon?.label ?? addon?.reference ?? addon?.key)),
    ]));
}

function hasLimitContext(context: BlockedContext): boolean {
    return Boolean(
        context.limit_key
        || context.current_usage !== undefined
        || context.contracted_limit !== undefined
        || context.usage_percent !== undefined
    );
}

function hasSubscriptionContext(context: BlockedContext): boolean {
    const reasonCode = String(
        context.suggestion?.block?.code
            ?? context.block?.code
            ?? context.reasons?.[0]
            ?? ''
    );

    return ['expired', 'inactive'].includes(String(context.subscription_state ?? '').toLowerCase())
        || ['subscription_expired', 'subscription_inactive'].includes(reasonCode);
}

function formatLimitValue(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return 'n/a';
    }

    return String(value);
}

function formatDateValue(value: string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return 'n/a';
    }

    return String(value);
}

function formatSubscriptionStateValue(value: string | null | undefined): string {
    switch (String(value ?? '').toLowerCase()) {
        case 'active':
            return 'Activo';
        case 'expired':
            return 'Expirada';
        case 'inactive':
            return 'Inactiva';
        case 'trial':
            return 'Trial';
        case 'superadmin':
            return 'Super admin';
        default:
            return value && String(value).trim() !== '' ? String(value) : 'n/a';
    }
}

function formatReasonCodeValue(reasonCode: string, t: (key: string) => string): string {
    switch (reasonCode) {
        case 'subscription_expired':
            return t('Subscription expired');
        case 'subscription_inactive':
            return t('Subscription inactive');
        case 'limit_exceeded':
            return t('Limit exceeded');
        case 'limit_near':
            return t('Near limit');
        case 'addon_required':
            return t('Add-on não activo');
        case 'module_unavailable':
            return t('Módulo não activo no plano');
        default:
            return t('Internal block');
    }
}

function buildLimitSummary(context: BlockedContext, t: (key: string) => string): NormalizedSuggestion['limitSummary'] {
    if (!hasLimitContext(context)) {
        return null;
    }

    const currentUsage = context.current_usage ?? 0;
    const contractedLimit = context.unlimited ? t('Ilimitado') : (context.contracted_limit_display ?? formatLimitValue(context.contracted_limit));
    const percentLabel = context.usage_percent === null || context.usage_percent === undefined
        ? null
        : `${context.usage_percent}%`;

    return {
        usageLabel: `${currentUsage} / ${contractedLimit}`,
        planLabel: context.plan_name ?? t('n/a'),
        subscriptionLabel: formatSubscriptionStateValue(context.subscription_state),
        remainingLabel: context.remaining === null || context.remaining === undefined
            ? null
            : String(context.remaining),
        percentLabel,
    };
}

function buildSubscriptionSummary(context: BlockedContext, t: (key: string) => string): NormalizedSuggestion['subscriptionSummary'] {
    if (! hasSubscriptionContext(context)) {
        return null;
    }

    return {
        stateLabel: formatSubscriptionStateValue(context.subscription_state ?? context.state),
        planLabel: context.plan_name ?? t('n/a'),
        familyLabel: context.plan_family_label ?? context.plan_family ?? null,
        expiresLabel: formatDateValue(context.plan_expire_date),
        trialExpiresLabel: formatDateValue(context.trial_expire_date),
    };
}

function normalizeContext(context: BlockedContext, t: (key: string) => string): NormalizedSuggestion {
    const recommendation = pickRecommendation(context);
    const cta = pickCta(context);
    const isSubscriptionContext = hasSubscriptionContext(context);
    const isLimitContext = hasLimitContext(context);
    const isModuleContext = Boolean(
        context.module_name
        || (context.moduleLabels && context.moduleLabels.length > 0)
        || context.moduleLabel
        || context.moduleKey
    );

    return {
        kind: isSubscriptionContext ? 'subscription' : (isLimitContext ? 'limit' : (isModuleContext ? 'module' : 'feature')),
        title: pickLabel(context),
        reasonLabel: pickReasonLabel(context),
        reasonCode: pickReasonCode(context),
        message: pickMessage(context),
        recommendation,
        cta,
        moduleLabels: arrayify(pickModuleLabels(context)),
        limitSummary: buildLimitSummary(context, t),
        subscriptionSummary: buildSubscriptionSummary(context, t),
    };
}

function toneClass(reasonCode: string): string {
    switch (reasonCode) {
        case 'subscription_expired':
        case 'module_unavailable':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        case 'subscription_inactive':
        case 'limit_exceeded':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'addon_required':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
}

function formatAddonLabel(addon: Record<string, any>): string {
    const rawLabel = String(addon?.label ?? addon?.reference ?? addon?.key ?? 'Add-on');
    return getPackageAlias(rawLabel) ?? rawLabel;
}

function resolveButtonVariant(tone?: string | null): 'default' | 'secondary' | 'outline' | 'ghost' {
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

export interface BlockedContextBannerProps {
    context: BlockedContext;
    className?: string;
}

export default function BlockedContextBanner({ context, className }: BlockedContextBannerProps) {
    const { t } = useTranslation();
    const data = normalizeContext(context, t);
    const recommendation = data.recommendation ?? null;
    const recommendedPlan = recommendation?.recommended_plan ?? null;
    const recommendedAddons = Array.isArray(recommendation?.recommended_addons) ? recommendation.recommended_addons : [];
    const recommendationMessage = String(recommendation?.message ?? data.message);
    const cta = data.cta;
    const kindLabel = data.kind === 'subscription'
        ? t('Bloqueio de subscrição')
        : data.kind === 'module'
            ? t('Pré-requisito do módulo')
            : data.kind === 'limit'
                ? t('Bloqueio de limite')
                : t('Bloqueio contextual');

    return (
        <Card className={cn('overflow-hidden border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-amber-50 shadow-sm', className)}>
            <CardContent className="p-5">
                <div className="grid gap-5 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline" className={toneClass(data.reasonCode)}>
                                <AlertTriangle className="mr-1 h-3.5 w-3.5" />
                                {kindLabel}
                            </Badge>
                            <Badge variant="outline" className="border-white/60 bg-white/80 text-slate-700">
                                {data.reasonLabel}
                            </Badge>
                            {data.moduleLabels.length > 0 && (
                                <Badge variant="outline" className="border-white/60 bg-white/80 text-slate-700">
                                    {data.moduleLabels.join(', ')}
                                </Badge>
                            )}
                        </div>

                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold text-slate-900">{data.title}</h3>
                            <p className="text-sm leading-6 text-slate-700">{recommendationMessage}</p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {recommendedPlan && (
                                <Badge variant="outline" className="border-emerald-200 bg-emerald-50 text-emerald-700">
                                    <BadgeCheck className="mr-1 h-3.5 w-3.5" />
                                    {t('Plano recomendado')}: {recommendedPlan.name ?? recommendedPlan.family_label ?? recommendedPlan.family ?? t('n/a')}
                                </Badge>
                            )}
                            {recommendedAddons.length > 0 && recommendedAddons.slice(0, 4).map((addon, index) => (
                                <Badge
                                    key={`${data.reasonCode}-addon-${index}`}
                                    variant="outline"
                                    className="border-sky-200 bg-sky-50 text-sky-700"
                                >
                                    <Package className="mr-1 h-3.5 w-3.5" />
                                    {formatAddonLabel(addon)}
                                </Badge>
                            ))}
                            {arrayify(recommendation?.recommended_config_keys).slice(0, 4).map((configKey, index) => (
                                <Badge
                                    key={`${data.reasonCode}-config-${index}`}
                                    variant="outline"
                                    className="border-amber-200 bg-amber-50 text-amber-700"
                                >
                                    <Sparkles className="mr-1 h-3.5 w-3.5" />
                                    {configKey}
                                </Badge>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                        <div className="space-y-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                    {t('Razão objectiva')}
                                </p>
                                <p className="mt-2 text-sm leading-6 text-slate-800">
                                    {data.reasonLabel}
                                </p>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                                {formatReasonCodeValue(data.reasonCode, t)}
                            </div>

                            {data.limitSummary && (
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                            {t('Utilização')}
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-slate-900">
                                            {data.limitSummary.usageLabel}
                                        </p>
                                        {data.limitSummary.percentLabel && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {t('Taxa')}: {data.limitSummary.percentLabel}
                                            </p>
                                        )}
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                            {t('Plano actual')}
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-slate-900">
                                            {data.limitSummary.planLabel}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {t('Subscrição')}: {data.limitSummary.subscriptionLabel}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {data.subscriptionSummary && (
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                            {t('Estado da subscrição')}
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-slate-900">
                                            {data.subscriptionSummary.stateLabel}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {t('Expira em')}: {data.subscriptionSummary.expiresLabel}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {t('Trial')}: {data.subscriptionSummary.trialExpiresLabel}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                            {t('Plano actual')}
                                        </p>
                                        <p className="mt-2 text-sm font-semibold text-slate-900">
                                            {data.subscriptionSummary.planLabel}
                                        </p>
                                        {data.subscriptionSummary.familyLabel && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {t('Família')}: {data.subscriptionSummary.familyLabel}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {cta?.href && (
                                <Button asChild variant={resolveButtonVariant(cta.tone)} className="w-full">
                                    <Link href={cta.href}>
                                        {cta.label ?? t('Abrir')}
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                            )}

                            {cta?.message && (
                                <p className="text-xs leading-5 text-muted-foreground">
                                    {cta.message}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
