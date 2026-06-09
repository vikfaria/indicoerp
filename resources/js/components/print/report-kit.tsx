import React, { ReactNode } from 'react';

type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

const toneClasses: Record<Tone, string> = {
    neutral: 'border-slate-200 bg-white text-slate-900',
    info: 'border-sky-200 bg-sky-50/60 text-slate-900',
    success: 'border-emerald-200 bg-emerald-50/70 text-slate-900',
    warning: 'border-amber-200 bg-amber-50/70 text-slate-900',
    danger: 'border-rose-200 bg-rose-50/70 text-slate-900',
};

const pillToneClasses: Record<Tone, string> = {
    neutral: 'border-slate-200 bg-slate-100 text-slate-700',
    info: 'border-sky-200 bg-sky-50 text-sky-700',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    danger: 'border-rose-200 bg-rose-50 text-rose-700',
};

export interface ReportPillProps {
    children: ReactNode;
    tone?: Tone;
    className?: string;
}

export function ReportPill({ children, tone = 'neutral', className = '' }: ReportPillProps) {
    return (
        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ${pillToneClasses[tone]} ${className}`.trim()}>
            {children}
        </span>
    );
}

export interface ReportShellProps {
    children: ReactNode;
    className?: string;
}

export function ReportShell({ children, className = '' }: ReportShellProps) {
    return (
        <div className={`min-h-screen bg-slate-50 text-slate-900 ${className}`.trim()}>
            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                {children}
            </div>
            <style>{`
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                }

                @page {
                    margin: 0.35in;
                    size: A4;
                }

                .report-page-break-inside-avoid {
                    page-break-inside: avoid;
                    break-inside: avoid;
                }

                @media print {
                    body {
                        background: white;
                    }
                }
            `}</style>
        </div>
    );
}

export interface ReportCardProps {
    title?: ReactNode;
    subtitle?: ReactNode;
    action?: ReactNode;
    tone?: Tone;
    className?: string;
    children: ReactNode;
}

export function ReportCard({ title, subtitle, action, tone = 'neutral', className = '', children }: ReportCardProps) {
    return (
        <section className={`overflow-hidden rounded-3xl border shadow-sm ${toneClasses[tone]} ${className}`.trim()}>
            {(title || subtitle || action) && (
                <header className="flex items-start justify-between gap-4 border-b border-slate-200/70 px-5 py-4">
                    <div className="min-w-0">
                        {title && <h3 className="text-sm font-semibold uppercase tracking-[0.22em] text-slate-600">{title}</h3>}
                        {subtitle && <div className="mt-1 text-sm text-slate-500">{subtitle}</div>}
                    </div>
                    {action && <div className="shrink-0">{action}</div>}
                </header>
            )}
            <div className="px-5 py-5">
                {children}
            </div>
        </section>
    );
}

export interface ReportKeyValue {
    label: ReactNode;
    value: ReactNode;
    mono?: boolean;
    span?: number;
}

export interface ReportKeyValueGridProps {
    items: ReportKeyValue[];
    columns?: 1 | 2 | 3 | 4;
    className?: string;
}

export function ReportKeyValueGrid({ items, columns = 2, className = '' }: ReportKeyValueGridProps) {
    const columnClass = {
        1: 'md:grid-cols-1',
        2: 'md:grid-cols-2',
        3: 'md:grid-cols-3',
        4: 'md:grid-cols-4',
    }[columns];

    return (
        <div className={`grid grid-cols-1 gap-4 ${columnClass} ${className}`.trim()}>
            {items.map((item, index) => (
                <div
                    key={index}
                    className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
                    style={item.span ? { gridColumn: `span ${item.span} / span ${item.span}` } : undefined}
                >
                    <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                        {item.label}
                    </div>
                    <div className={`mt-2 text-sm leading-6 ${item.mono ? 'font-mono text-slate-700' : 'font-medium text-slate-900'}`}>
                        {item.value}
                    </div>
                </div>
            ))}
        </div>
    );
}

export interface ReportSummaryRow {
    label: ReactNode;
    value: ReactNode;
    tone?: Tone;
    emphasis?: boolean;
}

export interface ReportSummaryCardProps {
    title?: ReactNode;
    subtitle?: ReactNode;
    rows: ReportSummaryRow[];
    className?: string;
}

export function ReportSummaryCard({ title, subtitle, rows, className = '' }: ReportSummaryCardProps) {
    return (
        <ReportCard title={title} subtitle={subtitle} className={className}>
            <div className="space-y-3">
                {rows.map((row, index) => (
                    <div
                        key={index}
                        className={`flex items-start justify-between gap-4 rounded-2xl border px-4 py-3 ${
                            row.emphasis ? 'border-slate-300 bg-slate-50' : 'border-slate-200 bg-white'
                        }`}
                    >
                        <div className="text-sm text-slate-600">{row.label}</div>
                        <div className={`text-right ${row.emphasis ? 'text-lg font-bold text-slate-900' : 'text-sm font-semibold text-slate-900'}`}>
                            {row.value}
                        </div>
                    </div>
                ))}
            </div>
        </ReportCard>
    );
}

export interface ReportTableProps {
    headers: ReactNode[];
    children: ReactNode;
    className?: string;
    headerClassName?: string;
}

export function ReportTable({ headers, children, className = '', headerClassName = '' }: ReportTableProps) {
    return (
        <div className={`overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm ${className}`.trim()}>
            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-sm">
                    <thead className={`bg-slate-50 ${headerClassName}`.trim()}>
                        <tr>
                            {headers.map((header, index) => (
                                <th
                                    key={index}
                                    className={`px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 ${index === headers.length - 1 ? 'text-right' : ''}`.trim()}
                                >
                                    {header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="[&>tr:not(:last-child)]:border-b [&>tr:not(:last-child)]:border-slate-100">
                        {children}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export interface ReportHeroProps {
    title: ReactNode;
    subtitle?: ReactNode;
    issuerTitle: ReactNode;
    issuerLines: ReactNode[];
    documentLabel: ReactNode;
    documentNumber: ReactNode;
    statusPills?: Array<{ label: ReactNode; tone?: Tone }>;
    meta?: ReportKeyValue[];
    note?: ReactNode;
}

export function ReportHero({
    title,
    subtitle,
    issuerTitle,
    issuerLines,
    documentLabel,
    documentNumber,
    statusPills = [],
    meta = [],
    note,
}: ReportHeroProps) {
    return (
        <div className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div className="bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 px-6 py-6 text-white md:px-8">
                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.9fr)]">
                    <div>
                        <div className="flex flex-wrap gap-2">
                            {statusPills.map((pill, index) => (
                                <ReportPill key={index} tone={pill.tone ?? 'info'} className="border-white/20 bg-white/10 text-white">
                                    {pill.label}
                                </ReportPill>
                            ))}
                        </div>
                        <div className="mt-5 text-sm font-semibold uppercase tracking-[0.28em] text-white/60">
                            {title}
                        </div>
                        {subtitle && <div className="mt-2 text-lg text-white/80">{subtitle}</div>}
                        <div className="mt-6">
                            <div className="text-xs font-semibold uppercase tracking-[0.3em] text-white/55">
                                {issuerTitle}
                            </div>
                            <div className="mt-3 space-y-1 text-sm text-white/85">
                                {issuerLines.map((line, index) => (
                                    <div key={index}>{line}</div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-[1.75rem] border border-white/10 bg-white/6 p-5 backdrop-blur">
                        <div className="text-xs font-semibold uppercase tracking-[0.3em] text-white/55">
                            {documentLabel}
                        </div>
                        <div className="mt-2 text-3xl font-black tracking-tight text-white">
                            {documentNumber}
                        </div>
                        {meta.length > 0 && (
                            <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                {meta.map((item, index) => (
                                    <div key={index} className="rounded-2xl border border-white/10 bg-white/8 px-4 py-3">
                                        <div className="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/55">
                                            {item.label}
                                        </div>
                                        <div className="mt-1 text-sm font-semibold text-white">
                                            {item.value}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        {note && (
                            <div className="mt-5 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                                {note}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
