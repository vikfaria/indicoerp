import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaxNavigation from '@/components/tax/tax-navigation';
import { Download, RefreshCw } from 'lucide-react';

interface DeclarationSummaryLine {
    rule_code: string;
    rule_name: string;
    rate: number;
    transaction_count: number;
    total_gross: number;
    total_withholding: number;
    total_net: number;
}

interface WithholdingDeclarationData {
    period: { year: string; month: number };
    due_date: string;
    payment_reference: string;
    totals: { gross: number; withholding: number; net: number };
    summary: DeclarationSummaryLine[];
}

export default function WithholdingDeclarationPage() {
    const { t } = useTranslation();
    const { year, month, declaration } = usePage<{
        year: number;
        month: number;
        declaration: WithholdingDeclarationData;
    }>().props;

    const [selectedYear, setSelectedYear] = useState(year);
    const [selectedMonth, setSelectedMonth] = useState(month);

    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);
    const months = Array.from({ length: 12 }, (_, i) => ({
        value: i + 1,
        label: new Date(2000, i).toLocaleString('pt', { month: 'long' }),
    }));
    const years = [selectedYear - 2, selectedYear - 1, selectedYear, selectedYear + 1];

    const refresh = () => {
        router.get(
            route('sce.tax.withholding.declaration.page'),
            { year: selectedYear, month: selectedMonth },
            { preserveState: true, replace: true }
        );
    };

    const exportCsv = () => {
        window.location.href = route('sce.tax.withholding.declaration.export', {
            year: selectedYear,
            month: selectedMonth,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Impostos') }, { label: t('Declaração de Retenções') }]}
            pageTitle={t('Declaração Mensal de Retenções na Fonte')}
            pageActions={
                <div className="flex gap-2">
                    <Button size="sm" variant="outline" onClick={refresh}>
                        <RefreshCw className="h-4 w-4 mr-1" /> {t('Actualizar')}
                    </Button>
                    <Button size="sm" onClick={exportCsv}>
                        <Download className="h-4 w-4 mr-1" /> {t('Exportar CSV')}
                    </Button>
                </div>
            }
        >
            <Head title={t('Declaração de Retenções')} />
            <TaxNavigation className="mb-4" />

            <Card className="mb-6">
                <CardContent className="p-4 flex flex-wrap items-center gap-3">
                    <Select value={String(selectedMonth)} onValueChange={(v) => setSelectedMonth(Number(v))}>
                        <SelectTrigger className="w-44">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {months.map((item) => (
                                <SelectItem key={item.value} value={String(item.value)}>
                                    {item.label.charAt(0).toUpperCase() + item.label.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={String(selectedYear)} onValueChange={(v) => setSelectedYear(Number(v))}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((item) => (
                                <SelectItem key={item} value={String(item)}>
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Badge variant="outline">{t('Vencimento')}: {new Date(declaration.due_date).toLocaleDateString('pt')}</Badge>
                    <Badge variant="outline">{t('Ref.')}: {declaration.payment_reference}</Badge>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-slate-600">{t('Total Bruto')}</p>
                        <p className="text-2xl font-bold text-slate-800">{fmt(declaration.totals.gross)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-red-600">{t('Total Retido')}</p>
                        <p className="text-2xl font-bold text-red-800">{fmt(declaration.totals.withholding)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-green-600">{t('Total Líquido')}</p>
                        <p className="text-2xl font-bold text-green-800">{fmt(declaration.totals.net)}</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{t('Resumo por Regra')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Regra')}</th>
                                <th className="p-3 text-right">{t('Taxa')}</th>
                                <th className="p-3 text-right">{t('Transacções')}</th>
                                <th className="p-3 text-right">{t('Bruto')}</th>
                                <th className="p-3 text-right">{t('Retenção')}</th>
                                <th className="p-3 text-right">{t('Líquido')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {declaration.summary.length === 0 ? (
                                <tr>
                                    <td className="p-8 text-center text-muted-foreground" colSpan={6}>
                                        {t('Sem retenções para o período selecionado.')}
                                    </td>
                                </tr>
                            ) : (
                                declaration.summary.map((line) => (
                                    <tr key={`${line.rule_code}-${line.rate}`} className="border-t">
                                        <td className="p-3">
                                            <div className="font-medium">{line.rule_code}</div>
                                            <div className="text-xs text-muted-foreground">{line.rule_name}</div>
                                        </td>
                                        <td className="p-3 text-right font-mono">{line.rate}%</td>
                                        <td className="p-3 text-right font-mono">{line.transaction_count}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.total_gross)}</td>
                                        <td className="p-3 text-right font-mono text-red-700">{fmt(line.total_withholding)}</td>
                                        <td className="p-3 text-right font-mono text-green-700">{fmt(line.total_net)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
