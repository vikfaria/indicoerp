import type { ReactNode } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import { ArrowRight, BarChart3, BadgeCheck, Clock3, Layers3, ListChecks, RefreshCw, ShieldAlert, Sparkles, Target } from "lucide-react";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import OnboardingStarterState from "@/components/onboarding/onboarding-starter-state";
import { cn } from "@/lib/utils";
import { translateAssistantActivationLabel, translateAssistantActivationLabels } from "@/utils/assistant-activation-labels";

interface DashboardOnboardingSnapshot {
    meta: Record<string, any>;
    summary: Record<string, any>;
    next_action: {
        label: string;
        href: string;
        message: string;
        tone?: string;
    };
    top_blocks: Array<Record<string, any>>;
    module_snapshots: Array<Record<string, any>>;
}

interface DashboardProps {
    onboarding: DashboardOnboardingSnapshot | null;
}

const percentFormatter = new Intl.NumberFormat("pt-PT", {
    maximumFractionDigits: 2,
});

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
    switch ((state ?? "").toLowerCase()) {
        case "ready":
        case "complete":
        case "completed":
        case "active":
            return "border-emerald-200 bg-emerald-50 text-emerald-700";
        case "warning":
        case "in_progress":
            return "border-amber-200 bg-amber-50 text-amber-700";
        case "blocked":
        case "critical":
        case "abandoned":
            return "border-rose-200 bg-rose-50 text-rose-700";
        case "skipped":
            return "border-indigo-200 bg-indigo-50 text-indigo-700";
        case "not_started":
        case "pending":
            return "border-slate-200 bg-slate-50 text-slate-700";
        default:
            return "border-slate-200 bg-white text-slate-700";
    }
}

function blockToneClass(type: string | null | undefined): string {
    switch ((type ?? "").toLowerCase()) {
        case "config_missing":
            return "border-rose-200 bg-rose-50 text-rose-900";
        case "step_incomplete":
            return "border-amber-200 bg-amber-50 text-amber-900";
        default:
            return "border-slate-200 bg-slate-50 text-slate-900";
    }
}

function progressToneClass(percent: number): string {
    if (percent >= 100) {
        return "bg-emerald-500";
    }

    if (percent >= 60) {
        return "bg-cyan-500";
    }

    if (percent > 0) {
        return "bg-amber-500";
    }

    return "bg-slate-300";
}

function progressTrack(percent: number): ReactNode {
    const safePercent = clampPercent(percent);

    return (
        <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div
                className={cn("h-full rounded-full transition-all", progressToneClass(safePercent))}
                style={{ width: `${safePercent}%` }}
            />
        </div>
    );
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
        <div className="rounded-2xl border border-white/60 bg-white/75 p-4 shadow-sm backdrop-blur">
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

function StateBadge({
    state,
    label,
}: {
    state?: string | null;
    label?: string | null;
}) {
    const { t } = useTranslation();
    const translatedLabel = translateAssistantActivationLabel(label);
    const translatedState = translateAssistantActivationLabel(state);

    return (
        <Badge variant="outline" className={toneClass(state)}>
            {translatedLabel || translatedState || t("n/a")}
        </Badge>
    );
}

function resolveActionVariant(tone?: string | null): "default" | "secondary" | "outline" | "ghost" {
    switch ((tone ?? "").toLowerCase()) {
        case "secondary":
            return "secondary";
        case "outline":
            return "outline";
        case "ghost":
            return "ghost";
        default:
            return "default";
    }
}

export default function Dashboard({ onboarding }: DashboardProps) {
    const { t } = useTranslation();
    const snapshot = onboarding ?? {
        meta: {},
        summary: {},
        next_action: {
            label: t("Open onboarding"),
            href: route("onboarding.index"),
            message: t("Open onboarding to review company readiness."),
        },
        top_blocks: [],
        module_snapshots: [],
    };

    const summary = snapshot.summary ?? {};
    const meta = snapshot.meta ?? {};
    const progressPercent = Number(summary.progress_percent ?? 0);
    const readinessPercent = Number(summary.overall_score ?? 0);
    const criticalBlocksTotal = Number(summary.critical_blocks_total ?? 0);
    const availableStepsTotal = Number(summary.available_steps_total ?? 0);
    const requiredStepsTotal = Number(summary.required_steps_total ?? 0);
    const completedRequiredStepsTotal = Number(summary.completed_required_steps_total ?? 0);
    const readinessState = (summary.readiness_state ?? null) as string | null;
    const completionState = (summary.completion_state ?? null) as string | null;
    const sessionStatus = (meta.session_status ?? null) as string | null;
    const isNewCompany = Boolean(meta.is_new_company ?? (sessionStatus === "not_started" && progressPercent <= 0));
    const planLabel = meta.plan_label ?? t("No active plan");
    const companyName = meta.company_name ?? t("Company");
    const topBlocks = snapshot.top_blocks ?? [];
    const moduleSnapshots = snapshot.module_snapshots ?? [];
    const hasCriticalBlocks = criticalBlocksTotal > 0;
    const starterItems = topBlocks.slice(0, 3).map((block, index) => ({
        title: translateAssistantActivationLabel(block.label) || t("Pending item"),
        description: translateAssistantActivationLabel(block.message) || translateAssistantActivationLabel(block.reason) || t("No description provided."),
        href: undefined,
        key: `${block.key ?? block.type ?? "block"}-${index}`,
    }));
    const sessionStatusLabel = translateAssistantActivationLabel(meta.session_status_label) || t("Not started");
    const readinessStateLabel = translateAssistantActivationLabel(summary.readiness_state_label) || t("Critical");
    const completionStateLabel = translateAssistantActivationLabel(summary.completion_state_label) || t("Blocked");

    const readinessMessage = hasCriticalBlocks
        ? t("There are {{count}} critical pending item(s) before go-live.", { count: criticalBlocksTotal })
        : summary.can_complete
            ? t("All mandatory checks are satisfied. Review the onboarding flow before completing it.")
            : progressPercent > 0
                ? t("Continue the remaining setup steps to improve readiness.")
                : t("Start the onboarding flow to configure the company before going live.");

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t("Dashboard") }]}
            pageTitle={t("Dashboard")}
            pageActions={
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => router.reload()}
                >
                    <RefreshCw className="h-4 w-4" />
                    {t("Refresh")}
                </Button>
            }
        >
            <Head title={t("Dashboard")} />

            <div className="space-y-6">
                <Card className="overflow-hidden border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50 shadow-sm">
                    <CardContent className="p-6 md:p-8">
                        <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
                            <div className="space-y-5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">
                                        {planLabel}
                                    </Badge>
                                    <Badge variant="outline" className={toneClass(sessionStatus)}>
                                        {sessionStatusLabel}
                                    </Badge>
                                    <StateBadge state={readinessState} label={summary.readiness_state_label} />
                                    <StateBadge state={completionState} label={summary.completion_state_label} />
                                </div>

                                <div className="space-y-3">
                                    <p className="text-sm font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                        {companyName}
                                    </p>
                                    {isNewCompany ? (
                                        <>
                                            <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                                                {t("Start the company setup")}
                                            </h1>
                                            <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                                                {t(
                                                    "The company is active, but the operational baseline is still empty. Use the guided steps below to prepare fiscal, accounting and treasury data before issuing documents."
                                                )}
                                            </p>
                                        </>
                                    ) : (
                                        <>
                                            <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                                                {t("Company readiness dashboard")}
                                            </h1>
                                            <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                                                {t(
                                                    "Use this page to see the current readiness score, identify the main pending items and move the company closer to go-live."
                                                )}
                                            </p>
                                        </>
                                    )}
                                </div>

                                {isNewCompany ? (
                                    <OnboardingStarterState
                                        eyebrow={t("New company setup")}
                                        title={t("Start here to prepare the company for go-live")}
                                        description={t(
                                            "Complete the first operational steps in sequence so fiscal, accounting and treasury data stay aligned from day one."
                                        )}
                                        primaryAction={{
                                            label: translateAssistantActivationLabel(snapshot.next_action?.label) || t("Open onboarding"),
                                            href: snapshot.next_action?.href ?? route("onboarding.index"),
                                        }}
                                        secondaryAction={{
                                            label: t("Review onboarding"),
                                            href: route("onboarding.index"),
                                        }}
                                        items={starterItems}
                                        footerNote={t(
                                            "The suggested items below highlight the most important pending configuration points for a fresh company."
                                        )}
                                    />
                                ) : (
                                    <div className="grid gap-4 sm:grid-cols-[auto_1fr] sm:items-center">
                                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                                {t("Readiness score")}
                                            </p>
                                            <div className="mt-2 flex items-end gap-3">
                                                <p className="text-4xl font-semibold text-slate-900">
                                                    {formatPercent(readinessPercent)}
                                                </p>
                                                <Badge
                                                    variant="outline"
                                                    className={toneClass(readinessState)}
                                                >
                                                    {readinessStateLabel}
                                                </Badge>
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">{t("Operational progress")}</span>
                                                <span className="font-medium text-slate-900">{formatPercent(progressPercent)}</span>
                                            </div>
                                            {progressTrack(progressPercent)}
                                            <p className="text-sm leading-6 text-muted-foreground">{readinessMessage}</p>
                                        </div>
                                    </div>
                                )}

                                <div className="flex flex-wrap gap-3">
                                    <Button asChild className="gap-2">
                                        <Link href={route("onboarding.index")}>
                                            {translateAssistantActivationLabel(snapshot.next_action?.label) || t("Open onboarding")}
                                            <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" className="gap-2">
                                        <Link href={route("onboarding.index")}>
                                            {t("Review blockers")}
                                            <ShieldAlert className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <StatCard
                                    icon={<Target className="h-4 w-4" />}
                                    label={t("Progress")}
                                    value={formatPercent(progressPercent)}
                                    note={t("Across all available steps")}
                                />
                                <StatCard
                                    icon={<BadgeCheck className="h-4 w-4" />}
                                    label={t("Readiness")}
                                    value={formatPercent(readinessPercent)}
                                    note={readinessStateLabel || t("Pending")}
                                />
                                <StatCard
                                    icon={<ShieldAlert className="h-4 w-4" />}
                                    label={t("Critical blocks")}
                                    value={`${criticalBlocksTotal}`}
                                    note={t("Pending configuration and setup items")}
                                />
                                <StatCard
                                    icon={<ListChecks className="h-4 w-4" />}
                                    label={t("Required steps")}
                                    value={`${completedRequiredStepsTotal}/${requiredStepsTotal}`}
                                    note={t("Completed required steps")}
                                />
                                <StatCard
                                    icon={<Layers3 className="h-4 w-4" />}
                                    label={t("Available steps")}
                                    value={`${availableStepsTotal}`}
                                    note={t("Steps active in the current plan")}
                                />
                                <StatCard
                                    icon={<Sparkles className="h-4 w-4" />}
                                    label={t("Completion")}
                                    value={summary.can_complete ? t("Ready") : t("Pending")}
                                    note={completionStateLabel || t("Blocked")}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-slate-200">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <ShieldAlert className="h-5 w-5 text-rose-600" />
                                {t("Main pending items")}
                            </CardTitle>
                            <CardDescription>
                                {t("These are the items that currently block or slow down go-live readiness.")}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {topBlocks.length > 0 ? (
                                topBlocks.map((block, index) => (
                                    <div
                                        key={`${block.type ?? block.key ?? block.label ?? "block"}-${index}`}
                                        className={cn("rounded-xl border p-4", blockToneClass(block.type))}
                                    >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="space-y-1">
                                                    <p className="font-medium">
                                                    {translateAssistantActivationLabel(block.label) || t("Unnamed pending item")}
                                                    </p>
                                                    <p className="text-sm leading-6 opacity-80">
                                                    {translateAssistantActivationLabel(block.message) || translateAssistantActivationLabel(block.reason) || t("No description provided.")}
                                                    </p>
                                                </div>
                                            <Badge variant="outline" className="border-white/60 bg-white/80">
                                                {translateAssistantActivationLabel(block.type) || t("Block")}
                                            </Badge>
                                        </div>

                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {block.module_label && (
                                                <Badge variant="outline" className="border-white/60 bg-white/80">
                                                    {translateAssistantActivationLabel(block.module_label)}
                                                </Badge>
                                            )}
                                            {Array.isArray(block.owner_modules) && block.owner_modules.length > 0 && (
                                                <Badge variant="outline" className="border-white/60 bg-white/80">
                                                    {translateAssistantActivationLabels(block.owner_modules)}
                                                </Badge>
                                            )}
                                            {block.reason && (
                                                <Badge variant="outline" className="border-white/60 bg-white/80">
                                                    {translateAssistantActivationLabel(block.reason)}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                    {t("No critical blocks are currently detected.")}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card className="border-slate-200">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-xl">
                                    <BarChart3 className="h-5 w-5 text-sky-600" />
                                    {t("Module snapshot")}
                                </CardTitle>
                                <CardDescription>
                                    {t("A quick look at the first modules in the current plan.")}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {moduleSnapshots.length > 0 ? (
                                    moduleSnapshots.map((module) => (
                                        <div
                                            key={module.key}
                                            className="rounded-xl border border-slate-200 bg-slate-50 p-4"
                                        >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="space-y-1">
                                                    <p className="font-medium text-slate-900">{translateAssistantActivationLabel(module.label) || module.label}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {module.available ? t("Available in the current plan") : t("Unavailable in the current plan")}
                                                    </p>
                                                </div>
                                                <Badge
                                                    variant="outline"
                                                    className={module.available ? "border-emerald-200 bg-emerald-50 text-emerald-700" : "border-slate-200 bg-white text-slate-700"}
                                                >
                                                    {formatPercent(module.progress_percent)}
                                                </Badge>
                                            </div>

                                            <div className="mt-3 space-y-2">
                                                {progressTrack(module.progress_percent)}
                                                <div className="grid gap-2 sm:grid-cols-3">
                                                    <div className="rounded-lg bg-white p-2">
                                                        <p className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground">
                                                            {t("Available")}
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-slate-900">
                                                            {module.available_step_count ?? 0}
                                                        </p>
                                                    </div>
                                                    <div className="rounded-lg bg-white p-2">
                                                        <p className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground">
                                                            {t("Required done")}
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-slate-900">
                                                            {module.completed_required_step_count ?? 0}
                                                        </p>
                                                    </div>
                                                    <div className="rounded-lg bg-white p-2">
                                                        <p className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground">
                                                            {t("Blocked")}
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-slate-900">
                                                            {module.blocked_step_count ?? 0}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-muted-foreground">
                                        {t("No module snapshot is available for this company.")}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-slate-200">
                            <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-xl">
                                    <Clock3 className="h-5 w-5 text-amber-600" />
                                    {t("Next action")}
                                </CardTitle>
                                <CardDescription>
                                    {translateAssistantActivationLabel(snapshot.next_action?.message) || t("Open the onboarding flow to continue.")}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    asChild
                                    variant={resolveActionVariant(snapshot.next_action?.tone)}
                                    className="w-full justify-between"
                                >
                                    <Link href={snapshot.next_action?.href ?? route("onboarding.index")}>
                                        {translateAssistantActivationLabel(snapshot.next_action?.label) || t("Open onboarding")}
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
