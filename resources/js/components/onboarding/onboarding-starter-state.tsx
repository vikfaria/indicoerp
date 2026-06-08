import { Link } from "@inertiajs/react";
import { ArrowRight, Sparkles } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export interface OnboardingStarterStateItem {
    title: string;
    description: string;
    href?: string | null;
}

interface OnboardingStarterStateAction {
    label: string;
    href: string;
}

interface OnboardingStarterStateProps {
    eyebrow: string;
    title: string;
    description: string;
    primaryAction: OnboardingStarterStateAction;
    secondaryAction?: OnboardingStarterStateAction | null;
    items: OnboardingStarterStateItem[];
    footerNote?: string;
    className?: string;
}

export default function OnboardingStarterState({
    eyebrow,
    title,
    description,
    primaryAction,
    secondaryAction,
    items,
    footerNote,
    className,
}: OnboardingStarterStateProps) {
    return (
        <section
            className={cn(
                "relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-900 p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.28)]",
                className
            )}
            aria-label={title}
        >
            <div className="absolute -right-12 top-0 h-36 w-36 rounded-full bg-emerald-400/15 blur-3xl" />
            <div className="absolute bottom-0 left-1/3 h-32 w-32 rounded-full bg-cyan-400/10 blur-3xl" />

            <div className="relative grid gap-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                <div className="space-y-5">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="rounded-2xl border border-white/10 bg-white/10 p-3">
                            <Sparkles className="h-5 w-5 text-emerald-200" />
                        </div>
                        <Badge variant="outline" className="border-white/10 bg-white/10 text-white/90">
                            {eyebrow}
                        </Badge>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight text-white">{title}</h2>
                        <p className="max-w-2xl text-sm leading-6 text-slate-200">{description}</p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button asChild size="lg" className="bg-white text-slate-950 hover:bg-slate-100">
                            <Link href={primaryAction.href}>
                                {primaryAction.label}
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </Button>

                        {secondaryAction && (
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                            >
                                <Link href={secondaryAction.href}>{secondaryAction.label}</Link>
                            </Button>
                        )}
                    </div>

                    {footerNote && <p className="text-xs leading-5 text-slate-300">{footerNote}</p>}
                </div>

                <div className="grid gap-3">
                    {items.length > 0 ? (
                        items.map((item, index) => {
                            const content = (
                                <>
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-sm font-semibold text-white">
                                        {String(index + 1).padStart(2, "0")}
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <p className="text-sm font-medium text-white">{item.title}</p>
                                        <p className="text-sm leading-5 text-slate-300">{item.description}</p>
                                    </div>
                                    {item.href && <ArrowRight className="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5" />}
                                </>
                            );

                            const itemClassName = cn(
                                "group flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-white/20 hover:bg-white/10",
                                item.href && "cursor-pointer"
                            );

                            if (item.href) {
                                return (
                                    <Link key={`${item.title}-${index}`} href={item.href} className={itemClassName}>
                                        {content}
                                    </Link>
                                );
                            }

                            return (
                                <div key={`${item.title}-${index}`} className={itemClassName}>
                                    {content}
                                </div>
                            );
                        })
                    ) : (
                        <div className="rounded-2xl border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-200">
                            Ainda não existem passos de arranque visíveis para esta empresa.
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
