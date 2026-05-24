import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaxNavigation from '@/components/tax/tax-navigation';
import { Download, RefreshCw } from 'lucide-react';

interface Model20Line {
    model20_line: string;
    debit_total: number;
    credit_total: number;
    net_total: number;
    movements: number;
}

interface UnmappedAccount {
    account_id: number;
    account_code: string;
    account_name: string;
    debit_total: number;
    credit_total: number;
    movements: number;
}

interface Model20SupportData {
    fiscal_year: string;
    lines: Model20Line[];
    unmapped_accounts: UnmappedAccount[];
    totals: {
        debit: number;
        credit: number;
        net: number;
        mapped_movements: number;
        unmapped_movements: number;
    };
    warnings: string[];
}

export default function Modelo20SupportPage() {
    const { t } = useTranslation();
    const { year, support } = usePage<{ year: number; support: Model20SupportData }>().props;

    const [selectedYear, setSelectedYear] = useState(year);
    const years = [selectedYear - 2, selectedYear - 1, selectedYear, selectedYear + 1];
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);

    const refresh = () => {
        router.get(route('sce.tax.modelo20.page'), { year: selectedYear }, { preserveState: true, replace: true });
    };

    const exportCsv = () => {
        window.location.href = route('sce.tax.modelo20.export', { year: selectedYear });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Impostos') }, { label: t('Modelo 20') }]}
            pageTitle={t('Mapa de Apoio ao Modelo 20')}
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
            <Head title={t('Modelo 20')} />
            <TaxNavigation className="mb-4" />

            <Card className="mb-6">
                <CardContent className="p-4 flex items-center gap-3">
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
                </CardContent>
            </Card>

            {support.warnings.length > 0 && (
                <Card className="mb-6 border-amber-200 bg-amber-50/50">
                    <CardHeader className="py-3">
                        <CardTitle className="text-sm text-amber-800">{t('Avisos')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <ul className="space-y-1 text-sm text-amber-900">
                            {support.warnings.map((warning, index) => (
                                <li key={index}>• {warning}</li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}

            <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4 text-center">
                        <p className="text-xs text-blue-600">{t('Débito Mapeado')}</p>
                        <p className="text-lg font-bold text-blue-800">{fmt(support.totals.debit)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-indigo-50 to-indigo-100 border-indigo-200">
                    <CardContent className="p-4 text-center">
                        <p className="text-xs text-indigo-600">{t('Crédito Mapeado')}</p>
                        <p className="text-lg font-bold text-indigo-800">{fmt(support.totals.credit)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
                    <CardContent className="p-4 text-center">
                        <p className="text-xs text-purple-600">{t('Saldo Líquido')}</p>
                        <p className="text-lg font-bold text-purple-800">{fmt(support.totals.net)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
                    <CardContent className="p-4 text-center">
                        <p className="text-xs text-emerald-600">{t('Mov. Mapeados')}</p>
                        <p className="text-lg font-bold text-emerald-800">{support.totals.mapped_movements}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200">
                    <CardContent className="p-4 text-center">
                        <p className="text-xs text-amber-600">{t('Mov. Sem Mapa')}</p>
                        <p className="text-lg font-bold text-amber-800">{support.totals.unmapped_movements}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>{t('Linhas Mapeadas do Modelo 20')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Linha')}</th>
                                <th className="p-3 text-right">{t('Débito')}</th>
                                <th className="p-3 text-right">{t('Crédito')}</th>
                                <th className="p-3 text-right">{t('Líquido')}</th>
                                <th className="p-3 text-right">{t('Movimentos')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {support.lines.length === 0 ? (
                                <tr>
                                    <td className="p-8 text-center text-muted-foreground" colSpan={5}>
                                        {t('Sem movimentos mapeados para o exercício selecionado.')}
                                    </td>
                                </tr>
                            ) : (
                                support.lines.map((line) => (
                                    <tr key={line.model20_line} className="border-t">
                                        <td className="p-3 font-medium">{line.model20_line}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.debit_total)}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.credit_total)}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.net_total)}</td>
                                        <td className="p-3 text-right font-mono">{line.movements}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('Contas sem Mapeamento Modelo 20')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Conta')}</th>
                                <th className="p-3 text-right">{t('Débito')}</th>
                                <th className="p-3 text-right">{t('Crédito')}</th>
                                <th className="p-3 text-right">{t('Movimentos')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {support.unmapped_accounts.length === 0 ? (
                                <tr>
                                    <td className="p-8 text-center text-muted-foreground" colSpan={4}>
                                        {t('Nenhuma conta sem mapeamento.')}
                                    </td>
                                </tr>
                            ) : (
                                support.unmapped_accounts.map((account) => (
                                    <tr key={account.account_id} className="border-t">
                                        <td className="p-3">
                                            <span className="font-medium">{account.account_code}</span> - {account.account_name}
                                        </td>
                                        <td className="p-3 text-right font-mono">{fmt(account.debit_total)}</td>
                                        <td className="p-3 text-right font-mono">{fmt(account.credit_total)}</td>
                                        <td className="p-3 text-right font-mono">{account.movements}</td>
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
