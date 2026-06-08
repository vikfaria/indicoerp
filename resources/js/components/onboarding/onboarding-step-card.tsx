import { Link } from "@inertiajs/react";
import { ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

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

export interface OnboardingStepCardData {
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
    permissions?: string[];
    granted_permissions?: string[];
    missing_permissions?: string[];
    permission_state?: string;
    action?: OnboardingStepAction;
    block?: OnboardingStepBlock;
}

interface OnboardingStepCardProps {
    step: OnboardingStepCardData;
    variant?: "summary" | "detailed";
    className?: string;
}

function clampPercent(value: number | string | null | undefined): number {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric)) {
        return 0;
    }

    return Math.max(0, Math.min(100, numeric));
}

function percentLabel(value: number | string | null | undefined): string {
    return `${clampPercent(value).toFixed(2).replace(/\.00$/, "")}%`;
}

function stateToneClass(state: string | null | undefined): string {
    switch ((state ?? "").toLowerCase()) {
        case "ready":
        case "complete":
        case "completed":
        case "active":
            return "border-emerald-200 bg-emerald-50 text-emerald-700";
        case "warning":
        case "in_progress":
        case "partial":
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

function blockToneClass(code: string | null | undefined): string {
    switch ((code ?? "").toLowerCase()) {
        case "completed":
            return "border-emerald-200 bg-emerald-50 text-emerald-900";
        case "addon_required":
        case "upgrade_plan":
            return "border-amber-200 bg-amber-50 text-amber-900";
        case "blocked_step":
        case "config_missing":
        case "permission_missing":
        case "completion_blocker":
            return "border-rose-200 bg-rose-50 text-rose-900";
        case "in_progress":
        case "ready_to_start":
            return "border-slate-200 bg-slate-50 text-slate-900";
        default:
            return "border-slate-200 bg-white text-slate-900";
    }
}

function actionVariant(kind: string): "default" | "secondary" | "outline" | "ghost" {
    switch (kind) {
        case "review":
            return "ghost";
        case "resolve_blocker":
            return "outline";
        case "grant_permission":
            return "outline";
        case "upgrade_plan":
        case "activate_addon":
            return "secondary";
        default:
            return "default";
    }
}

function metricBox({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-xl bg-slate-50 p-3">
            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">{label}</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">{value}</p>
        </div>
    );
}

export default function OnboardingStepCard({ step, variant = "summary", className }: OnboardingStepCardProps) {
    const progress = clampPercent(step.progress_percent);
    const action: OnboardingStepAction = step.action ?? {
        kind: "continue",
        label: "Continuar",
        href: null,
        tone: "default",
        disabled: true,
        message: "Sem acção disponível.",
    };
    const block: OnboardingStepBlock = step.block ?? {
        code: "ready_to_start",
        label: "Sem bloqueio",
        message: "Sem informação de bloqueio disponível.",
        details: {},
    };
    const hasActionLink = Boolean(action.href && !action.disabled);
    const details = block.details ?? {};
    const blockedItems = Number(details.items_blocked_total ?? step.items_blocked_total ?? 0);
    const pendingItems = Number(details.items_pending_total ?? step.items_pending_total ?? 0);
    const missingPermissions = Array.isArray(details.missing_permissions) ? details.missing_permissions : (step.missing_permissions ?? []);

    return (
        <article className={cn("rounded-2xl border border-slate-200 bg-white p-4 shadow-sm", className)}>
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className={stateToneClass(step.state)}>
                            {step.state_label ?? step.state ?? "n/a"}
                        </Badge>
                        {step.module_label ? (
                            <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                {step.module_label}
                            </Badge>
                        ) : step.type ? (
                            <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                {step.type}
                            </Badge>
                        ) : null}
                        {step.checklist_key && (
                            <Badge variant="outline" className="border-slate-200 bg-slate-50 text-slate-700">
                                {step.checklist_key}
                            </Badge>
                        )}
                    </div>

                    <div className="space-y-2">
                        <h3 className={cn("font-semibold tracking-tight text-slate-900", variant === "detailed" ? "text-xl" : "text-base")}>
                            {step.label ?? "Unnamed step"}
                        </h3>
                        <p className="text-sm leading-6 text-muted-foreground">
                            {step.description ?? "No additional guidance provided for this step."}
                        </p>
                    </div>
                </div>

                <div className="min-w-[170px] space-y-2 text-right">
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Progress</p>
                    <p className="text-lg font-semibold text-slate-900">{percentLabel(progress)}</p>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            className={cn(
                                "h-full rounded-full transition-all",
                                progress >= 100 ? "bg-emerald-500" : progress >= 60 ? "bg-cyan-500" : progress > 0 ? "bg-amber-500" : "bg-slate-300"
                            )}
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            </div>

            <div className={cn("mt-4 rounded-xl border p-4", blockToneClass(block.code))}>
                <p className="text-xs font-medium uppercase tracking-[0.18em] opacity-75">{block.label}</p>
                <p className="mt-1 text-sm leading-6">{block.message}</p>
                {block.code === "permission_missing" && missingPermissions.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                        {missingPermissions.map((permission) => (
                            <Badge key={permission} variant="outline" className="border-white/70 bg-white/80 text-rose-800">
                                {permission}
                            </Badge>
                        ))}
                    </div>
                )}
            </div>

            {variant === "detailed" && (
                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {metricBox({
                        label: "Required",
                        value: step.required ? "Yes" : "No",
                    })}
                    {metricBox({
                        label: "Available",
                        value: step.available ? "Yes" : "No",
                    })}
                    {metricBox({
                        label: "Checklist items",
                        value: String(step.items_total ?? 0),
                    })}
                    {metricBox({
                        label: "Evidence",
                        value: step.evidence || "Not defined",
                    })}
                </div>
            )}

            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-1">
                    <p className="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">Action</p>
                    <p className="text-sm font-medium text-slate-900">
                        {action.message ?? "No additional action information."}
                    </p>
                    {(blockedItems > 0 || pendingItems > 0) && (
                        <p className="text-xs text-muted-foreground">
                            {blockedItems > 0 ? `${blockedItems} blocked item(s)` : `${pendingItems} pending item(s)`}
                        </p>
                    )}
                </div>

                {hasActionLink ? (
                    <Button asChild variant={actionVariant(action.kind)} className="shrink-0">
                        <Link href={action.href as string}>
                            {action.label}
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button variant={actionVariant(action.kind)} disabled className="shrink-0">
                        {action.label}
                    </Button>
                )}
            </div>

            {step.evidence && variant !== "detailed" && (
                <p className="mt-3 text-xs text-muted-foreground">{step.evidence}</p>
            )}
        </article>
    );
}
