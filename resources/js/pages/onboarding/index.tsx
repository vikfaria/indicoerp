import type { ReactNode } from "react";
import { Head, router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import OnboardingStarterState from "@/components/onboarding/onboarding-starter-state";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import OnboardingStepCard from "@/components/onboarding/onboarding-step-card";
import { BadgeCheck, Clock3, Layers3, ListChecks, RefreshCw, ShieldAlert, Sparkles, Target } from "lucide-react";
import { translateAssistantActivationLabel, translateAssistantActivationLabels } from "@/utils/assistant-activation-labels";

interface OnboardingPageProps {
    plan: {
        label: string | null;
        modules: string[];
        modules_total: number;
    };
    session: {
        id: number | null;
        status: string | null;
        current_module_key: string | null;
        current_step_key: string | null;
        progress_percent: number | string | null;
        started_at?: string | null;
        last_activity_at?: string | null;
        completed_at?: string | null;
        abandoned_at?: string | null;
    } | null;
    overview: {
        session_id: number | null;
        session_status: string;
        session_status_label: string;
        progress_percent: number;
        available_steps_total: number;
        completed_required_steps_total: number;
        required_steps_total: number;
        readiness_state: string;
        readiness_state_label: string;
        readiness_score: number;
        critical_blocks_total: number;
        completion_state: string;
        completion_state_label: string;
        can_complete: boolean;
        blockers_total: number;
        is_new_company: boolean;
    };
    modules: OnboardingModuleCard[];
    next_steps: OnboardingStepCardData[];
    critical_blocks: Array<Record<string, any>>;
    completion_blockers: Array<Record<string, any>>;
}

interface OnboardingStepAction {
    kind: string;
    label: string;
    href: string | null;
    tone: "default" | "secondary" | "outline" | "ghost";
    disabled: boolean;
    message?: string | null;
}

interface OnboardingStepBlock {
    code: string;
    label: string;
    message: string;
    details?: Record<string, any>;
}

interface OnboardingStepCardData {
    type?: string;
    module_key?: string | null;
    module_label?: string | null;
    key: string | null;
    label: string | null;
    state: string | null;
    state_label: string | null;
    progress_percent: number;
    checklist_key?: string | null;
    description: string | null;
    evidence?: string | null;
    required?: boolean;
    available?: boolean;
    items_total?: number;
    items_completed_total?: number;
    items_skipped_total?: number;
    items_blocked_total?: number;
    items_pending_total?: number;
    items_not_applicable_total?: number;
    action?: OnboardingStepAction;
    block?: OnboardingStepBlock;
}

interface OnboardingModuleCard {
    key: string;
    label: string;
    available: boolean;
    priority: number;
    progress_percent: number;
    available_step_count: number;
    required_available_step_count: number;
    completed_required_step_count: number;
    blocked_step_count: number;
    next_step: OnboardingStepCardData | null;
    steps: Array<{
        key: string;
        label: string;
        description: string;
        progress_percent: number;
        state: string;
        state_label: string;
        required: boolean;
        available: boolean;
        checklist_key?: string | null;
        items_total?: number;
        items_completed_total?: number;
        items_skipped_total?: number;
        items_blocked_total?: number;
        items_pending_total?: number;
        items_not_applicable_total?: number;
        evidence?: string | null;
        action?: OnboardingStepAction;
        block?: OnboardingStepBlock;
    }>;
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

function toneBarClass(percent: number): string {
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

function progressTrack(percent: number): JSX.Element {
    const safePercent = clampPercent(percent);

    return (
        <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div
                className={`h-full rounded-full transition-all ${toneBarClass(safePercent)}`}
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
        <div className="rounded-2xl border border-white/60 bg-white/70 p-4 shadow-sm backdrop-blur">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{label}</p>
                    <p className="mt-2 text-2xl font-semibold text-foreground">{value}</p>
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
    const translatedLabel = translateAssistantActivationLabel(label);
    const translatedState = translateAssistantActivationLabel(state);

    return (
        <Badge variant="outline" className={toneClass(state)}>
            {translatedLabel || translatedState || "n/a"}
        </Badge>
    );
}

export default function Index({
    plan,
    session,
    overview,
    modules,
    next_steps,
    critical_blocks,
    completion_blockers,
}: OnboardingPageProps) {
    const { t } = useTranslation();
    const topNextStep = next_steps[0] ?? null;
    const remainingNextSteps = next_steps.slice(1);
    const sessionProgress = clampPercent(session?.progress_percent ?? overview.progress_percent);
    const isNewCompany = Boolean(overview.is_new_company ?? (overview.session_status === "not_started" && sessionProgress <= 0));
    const planLabel = plan.label ?? t("No plan assigned");
    const starterItems = next_steps.slice(0, 3).map((step, index) => ({
        title: step.action?.label ?? step.label ?? t("Next step"),
        description: step.action?.message ?? step.description ?? t("Open the step to continue."),
        href: step.action?.href ?? undefined,
        key: `${step.key ?? step.checklist_key ?? "step"}-${index}`,
    }));

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t("Onboarding") }]}
            pageTitle={t("Onboarding")}
            backUrl={route("dashboard")}
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
            <Head title={t("Onboarding")} />

            <div className="space-y-6">
                <Card className="overflow-hidden border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50 shadow-sm">
                    <CardContent className="p-6 md:p-8">
                        <div className="grid gap-6 lg:grid-cols-[1.25fr_0.95fr] lg:items-start">
                            <div className="space-y-5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" className="border-sky-200 bg-sky-50 text-sky-700">
                                        {t("Plan")}: {planLabel}
                                    </Badge>
                                    <Badge variant="outline" className={toneClass(overview.session_status)}>
                                        {translateAssistantActivationLabel(overview.session_status_label) || overview.session_status_label}
                                    </Badge>
                                </div>

                                <div className="space-y-3">
                                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                                        {t("Company onboarding dashboard")}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                                        {t(
                                            "Use this page to track the initial company setup, see what is already resolved and identify the next operational step before going live."
                                        )}
                                    </p>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <StateBadge state={overview.readiness_state} label={overview.readiness_state_label} />
                                    <StateBadge state={overview.completion_state} label={overview.completion_state_label} />
                                    {overview.can_complete ? (
                                        <Badge variant="outline" className="border-emerald-200 bg-emerald-50 text-emerald-700">
                                            {t("Can be completed")}
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline" className="border-rose-200 bg-rose-50 text-rose-700">
                                            {t("Still blocked")}
                                        </Badge>
                                    )}
                                </div>

                                {isNewCompany ? (
                                    <OnboardingStarterState
                                        eyebrow={t("New company setup")}
                                        title={t("Start with the essential configuration")}
                                        description={t(
                                            "The onboarding flow shows the exact order to complete fiscal, accounting and operational setup before the company goes live."
                                        )}
                                        primaryAction={{
                                            label: topNextStep?.action?.label ?? t("Open onboarding"),
                                            href: topNextStep?.action?.href ?? route("onboarding.index"),
                                        }}
                                        secondaryAction={{
                                            label: t("Back to dashboard"),
                                            href: route("dashboard"),
                                        }}
                                        items={starterItems}
                                        footerNote={t(
                                            "Each item links to the exact screen needed to complete that step."
                                        )}
                                    />
                                ) : (
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <StatCard
                                            icon={<Target className="h-4 w-4" />}
                                            label={t("Progress")}
                                            value={formatPercent(overview.progress_percent)}
                                            note={t("Across all available steps")}
                                        />
                                        <StatCard
                                            icon={<BadgeCheck className="h-4 w-4" />}
                                            label={t("Readiness")}
                                            value={formatPercent(overview.readiness_score)}
                                            note={t("Combined score")}
                                        />
                                        <StatCard
                                            icon={<Clock3 className="h-4 w-4" />}
                                            label={t("Session")}
                                            value={session?.status ? session.status : t("Not started")}
                                            note={session?.id ? `#${session.id}` : t("No session created")}
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-2">
                                <StatCard
                                    icon={<Layers3 className="h-4 w-4" />}
                                    label={t("Active modules")}
                                    value={`${plan.modules_total}`}
                                    note={t("Modules in the active plan")}
                                />
                                <StatCard
                                    icon={<ListChecks className="h-4 w-4" />}
                                    label={t("Required steps")}
                                    value={`${overview.completed_required_steps_total}/${overview.required_steps_total}`}
                                    note={t("Completed required steps")}
                                />
                                <StatCard
                                    icon={<ShieldAlert className="h-4 w-4" />}
                                    label={t("Critical blocks")}
                                    value={`${overview.critical_blocks_total}`}
                                    note={t("Configuration and step blockers")}
                                />
                                <StatCard
                                    icon={<Sparkles className="h-4 w-4" />}
                                    label={t("Completion")}
                                    value={overview.can_complete ? t("Ready") : t("Pending")}
                                    note={t("Go-live readiness")}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="overview" className="space-y-6">
                    <TabsList className="grid w-full grid-cols-3 lg:w-fit">
                        <TabsTrigger value="overview">{t("Overview")}</TabsTrigger>
                        <TabsTrigger value="modules">{t("Modules")}</TabsTrigger>
                        <TabsTrigger value="details">{t("Details")}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-6">
                        <div className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                            <Card className="border-slate-200">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-xl">
                                        {t("Next recommended step")}
                                    </CardTitle>
                                    <CardDescription>
                                        {t("This is the next action that should be completed in the onboarding flow.")}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {topNextStep ? (
                                        <OnboardingStepCard step={topNextStep} variant="detailed" />
                                    ) : (
                                        <div className="rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-6 text-emerald-800">
                                            <p className="text-sm font-medium">{t("No pending onboarding steps")}</p>
                                            <p className="mt-2 text-sm">
                                                {t("All available steps are already resolved or the current plan does not expose any onboarding tasks.")}
                                            </p>
                                        </div>
                                    )}

                                    {remainingNextSteps.length > 0 && (
                                        <div className="space-y-3">
                                            <p className="text-sm font-medium text-slate-900">{t("Other recommended actions")}</p>
                                            <div className="space-y-3">
                                                {remainingNextSteps.map((step) => (
                                                    <OnboardingStepCard
                                                        key={`${step.type}-${step.key ?? step.checklist_key}`}
                                                        step={step}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="space-y-6">
                                <Card className="border-slate-200">
                                    <CardHeader>
                                        <CardTitle>{t("Critical blocks")}</CardTitle>
                                        <CardDescription>
                                            {t("These checks must be solved before the onboarding can be considered stable.")}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {critical_blocks.length > 0 ? (
                                            critical_blocks.map((block, index) => (
                                                <div
                                                    key={`${block.key ?? block.label ?? "critical"}-${index}`}
                                                    className="rounded-xl border border-rose-200 bg-rose-50 p-4"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="space-y-1">
                                                            <p className="font-medium text-rose-900">{translateAssistantActivationLabel(block.label) || t("Unnamed block")}</p>
                                                            <p className="text-sm text-rose-700">{translateAssistantActivationLabel(block.message) || translateAssistantActivationLabel(block.reason) || t("No description provided.")}</p>
                                                        </div>
                                                        <Badge variant="outline" className="border-rose-200 bg-white text-rose-700">
                                                            {translateAssistantActivationLabel(block.type) || t("Block")}
                                                        </Badge>
                                                    </div>
                                                    {Array.isArray(block.owner_modules) && block.owner_modules.length > 0 && (
                                                        <p className="mt-3 text-xs text-rose-700">
                                                            {t("Modules")}: {translateAssistantActivationLabels(block.owner_modules)}
                                                        </p>
                                                    )}
                                                </div>
                                            ))
                                        ) : (
                                            <div className="rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                                {t("No critical configuration blocks are currently detected.")}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card className="border-slate-200">
                                    <CardHeader>
                                        <CardTitle>{t("Completion blockers")}</CardTitle>
                                        <CardDescription>
                                            {t("These are the conditions that still prevent the onboarding from being fully complete.")}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {completion_blockers.length > 0 ? (
                                            completion_blockers.map((block, index) => (
                                                <div
                                                    key={`${block.code ?? block.label ?? "completion"}-${index}`}
                                                    className="rounded-xl border border-slate-200 bg-white p-4"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="space-y-1">
                                                            <p className="font-medium text-slate-900">{block.label ?? t("Unnamed blocker")}</p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {block.details?.reason ?? block.message ?? t("No blocker details available.")}
                                                            </p>
                                                        </div>
                                                        <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                                            {block.code ?? t("Blocker")}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                                {t("No completion blockers were found.")}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="modules" className="space-y-4">
                        <div className="grid gap-4 xl:grid-cols-2">
                            {modules.map((module) => (
                                <Card key={module.key} className="border-slate-200">
                                    <CardHeader className="space-y-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <CardTitle className="text-xl">{module.label}</CardTitle>
                                                <CardDescription>
                                                    {module.available ? t("Available in the current plan") : t("Unavailable in the current plan")}
                                                </CardDescription>
                                            </div>
                                            <StateBadge
                                                state={module.available ? "ready" : "blocked"}
                                                label={module.available ? t("Available") : t("Unavailable")}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">{t("Module progress")}</span>
                                                <span className="font-medium text-slate-900">{formatPercent(module.progress_percent)}</span>
                                            </div>
                                            {progressTrack(module.progress_percent)}
                                        </div>
                                    </CardHeader>

                                    <CardContent className="space-y-4">
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Available steps")}</p>
                                                <p className="mt-1 text-lg font-semibold text-slate-900">{module.available_step_count}</p>
                                            </div>
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Required available")}</p>
                                                <p className="mt-1 text-lg font-semibold text-slate-900">{module.required_available_step_count}</p>
                                            </div>
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Completed required")}</p>
                                                <p className="mt-1 text-lg font-semibold text-slate-900">{module.completed_required_step_count}</p>
                                            </div>
                                            <div className="rounded-xl bg-slate-50 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Blocked steps")}</p>
                                                <p className="mt-1 text-lg font-semibold text-slate-900">{module.blocked_step_count}</p>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            <p className="text-sm font-medium text-slate-900">{t("Next available step")}</p>
                                            {module.next_step ? (
                                                <OnboardingStepCard step={module.next_step} />
                                            ) : (
                                                <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-muted-foreground">
                                                    {t("No pending step for this module.")}
                                                </div>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <p className="text-sm font-medium text-slate-900">{t("Steps summary")}</p>
                                            <div className="space-y-2">
                                                {module.steps.slice(0, 3).map((step) => (
                                                    <div
                                                        key={step.key}
                                                        className="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                                                    >
                                                        <div className="space-y-1">
                                                            <p className="text-sm font-medium text-slate-900">{step.label}</p>
                                                            <p className="text-xs text-muted-foreground">{step.description}</p>
                                                        </div>
                                                        <div className="text-right">
                                                            <StateBadge state={step.state} label={step.state_label} />
                                                            <p className="mt-2 text-xs text-muted-foreground">{formatPercent(step.progress_percent)}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                                {module.steps.length > 3 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {t("And")} {module.steps.length - 3} {t("more steps")}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </TabsContent>

                    <TabsContent value="details" className="space-y-4">
                        {modules.map((module) => (
                            <Card key={`detail-${module.key}`} className="border-slate-200">
                                <CardHeader>
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <CardTitle className="text-xl">{module.label}</CardTitle>
                                            <CardDescription>
                                                {module.available
                                                    ? t("This module is available in the current plan and its steps can be completed now.")
                                                    : t("This module is not available for the current plan.")}
                                            </CardDescription>
                                        </div>
                                        <StateBadge
                                            state={module.available ? "ready" : "blocked"}
                                            label={module.available ? t("Available") : t("Unavailable")}
                                        />
                                    </div>
                                </CardHeader>

                                <CardContent className="space-y-4">
                                    <div className="grid gap-3 sm:grid-cols-4">
                                        <div className="rounded-xl bg-slate-50 p-3">
                                            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Progress")}</p>
                                            <p className="mt-1 text-lg font-semibold text-slate-900">{formatPercent(module.progress_percent)}</p>
                                        </div>
                                        <div className="rounded-xl bg-slate-50 p-3">
                                            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Available steps")}</p>
                                            <p className="mt-1 text-lg font-semibold text-slate-900">{module.available_step_count}</p>
                                        </div>
                                        <div className="rounded-xl bg-slate-50 p-3">
                                            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Required available")}</p>
                                            <p className="mt-1 text-lg font-semibold text-slate-900">{module.required_available_step_count}</p>
                                        </div>
                                        <div className="rounded-xl bg-slate-50 p-3">
                                            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{t("Completed required")}</p>
                                            <p className="mt-1 text-lg font-semibold text-slate-900">{module.completed_required_step_count}</p>
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        {module.steps.map((step) => (
                                            <OnboardingStepCard key={step.key} step={step} variant="detailed" />
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}
