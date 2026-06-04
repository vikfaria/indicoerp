import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

type EquityComponent = {
    label: string;
    opening: number;
    movement: number;
    closing: number;
};

type EquityNotesSection = {
    title: string;
    items: Array<{
        label: string;
        value: string | number;
    }>;
};

type EquityChangesPageProps = {
    data: {
        title?: string;
        period?: { start: string; end: string };
        components?: Record<string, EquityComponent>;
        totals?: {
            opening?: number;
            movement?: number;
            closing?: number;
            difference?: number;
            is_balanced?: boolean;
            net_income?: number;
        };
        notes?: EquityNotesSection[];
    };
    startDate: string;
    endDate: string;
};

export default function EquityChanges() {
    const { t } = useTranslation();
    const { data, startDate, endDate } = usePage<EquityChangesPageProps>().props;
    const [sd, setSd] = useState(startDate);
    const [ed, setEd] = useState(endDate);
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);

    const refresh = () => router.get(route('sce.reports.equity-changes'), { start_date: sd, end_date: ed }, { preserveState: true });
    const components = Object.entries(data.components || {});
    const totals = data.totals || {};
    const notes = data.notes || [];

    const renderAmount = (value: number) => (
        <span className={`font-mono text-sm ${value < 0 ? 'text-red-600' : ''}`}>{fmt(value)}</span>
    );

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Relatórios') }, { label: t('Alterações no Capital Próprio') }]} pageTitle={t('Demonstração de Alterações no Capital Próprio')}>
            <Head title={t('Alterações no Capital Próprio')} />
            <AccountingSuiteNavigation section="reports" className="mb-4" />

            <div className="flex items-center gap-3 mb-6">
                <Input type="date" value={sd} onChange={e => setSd(e.target.value)} className="w-40" />
                <span className="text-muted-foreground">{t('a')}</span>
                <Input type="date" value={ed} onChange={e => setEd(e.target.value)} className="w-40" />
                <Button size="sm" onClick={refresh}>{t('Actualizar')}</Button>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-4 mb-6">
                <Card className="bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200">
                    <CardContent className="p-4">
                        <p className="text-xs uppercase tracking-wide text-slate-600">{t('Capital Inicial')}</p>
                        <p className="mt-2 text-xl font-semibold text-slate-900">{fmt(totals.opening || 0)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4">
                        <p className="text-xs uppercase tracking-wide text-blue-600">{t('Movimento do Período')}</p>
                        <p className={`mt-2 text-xl font-semibold ${(totals.movement || 0) < 0 ? 'text-red-700' : 'text-blue-900'}`}>{fmt(totals.movement || 0)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
                    <CardContent className="p-4">
                        <p className="text-xs uppercase tracking-wide text-emerald-600">{t('Capital Final')}</p>
                        <p className="mt-2 text-xl font-semibold text-emerald-900">{fmt(totals.closing || 0)}</p>
                    </CardContent>
                </Card>
                <Card className={`bg-gradient-to-br ${(totals.is_balanced ?? true) ? 'from-indigo-50 to-indigo-100 border-indigo-200' : 'from-red-50 to-red-100 border-red-200'}`}>
                    <CardContent className="p-4">
                        <p className={`text-xs uppercase tracking-wide ${(totals.is_balanced ?? true) ? 'text-indigo-600' : 'text-red-600'}`}>{t('Diferença')}</p>
                        <p className={`mt-2 text-xl font-semibold ${(totals.is_balanced ?? true) ? 'text-indigo-900' : 'text-red-800'}`}>{fmt(totals.difference || 0)}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="mb-6">
                <CardHeader className="border-b bg-gradient-to-r from-violet-50 to-indigo-50">
                    <CardTitle>{t('Componentes do Capital Próprio')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="grid grid-cols-4 gap-2 border-b px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <span>{t('Componente')}</span>
                        <span className="text-right">{t('Saldo Inicial')}</span>
                        <span className="text-right">{t('Movimento')}</span>
                        <span className="text-right">{t('Saldo Final')}</span>
                    </div>
                    {components.map(([key, component]) => (
                        <div key={key} className="grid grid-cols-4 gap-2 border-b px-4 py-3 hover:bg-muted/20 last:border-b-0">
                            <span className="text-sm font-medium">{component.label}</span>
                            <span className="text-right">{renderAmount(component.opening || 0)}</span>
                            <span className="text-right">{renderAmount(component.movement || 0)}</span>
                            <span className="text-right">{renderAmount(component.closing || 0)}</span>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
                {notes.map((section) => (
                    <Card key={section.title}>
                        <CardHeader className="border-b bg-muted/20">
                            <CardTitle className="text-base">{section.title}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 pt-4">
                            {section.items.length === 0 ? (
                                <p className="text-sm text-muted-foreground">{t('Sem movimentos materiais.')}</p>
                            ) : (
                                section.items.map((item) => (
                                    <div key={item.label} className="flex items-start justify-between gap-4 rounded-md bg-muted/10 px-3 py-2">
                                        <span className="text-sm text-muted-foreground">{item.label}</span>
                                        <span className={`text-right text-sm font-medium ${typeof item.value === 'number' && item.value < 0 ? 'text-red-600' : 'text-foreground'}`}>
                                            {typeof item.value === 'number' ? fmt(item.value) : item.value}
                                        </span>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
