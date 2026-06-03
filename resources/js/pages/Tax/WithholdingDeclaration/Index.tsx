import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
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
    totals: {
        gross: number;
        withholding: number;
        net: number;
        withholding_paid: number;
        withholding_declared: number;
        withholding_pending: number;
    };
    summary: DeclarationSummaryLine[];
    status_summary: Array<{
        status: string;
        transactions: number;
        withholding_amount: number;
        gross_amount: number;
    }>;
    detailed_map: Array<{
        id: number;
        transaction_date: string | null;
        document_reference: string | null;
        beneficiary: string | null;
        beneficiary_tax_number: string | null;
        beneficiary_country: string | null;
        beneficiary_residency_status: string | null;
        income_type: string | null;
        withholding_treatment: string | null;
        rate: number;
        gross_amount: number;
        withholding_amount: number;
        net_amount: number;
        status: string;
        declaration_reference: string | null;
        declared_at: string | null;
        state_payment_reference: string | null;
        paid_at: string | null;
    }>;
    history_by_vendor: Array<{
        beneficiary: string | null;
        beneficiary_tax_number: string | null;
        beneficiary_country: string | null;
        beneficiary_residency_status: string | null;
        income_type: string | null;
        transactions: number;
        gross_amount: number;
        withholding_amount: number;
        net_amount: number;
    }>;
}

export default function WithholdingDeclarationPage() {
    const { t } = useTranslation();
    const { year, month, declaration, incomeTypes, filters, canManageTaxReports } = usePage<{
        year: number;
        month: number;
        declaration: WithholdingDeclarationData;
        incomeTypes: string[];
        canManageTaxReports: boolean;
        filters: {
            vendor_nuit?: string | null;
            income_type?: string | null;
            status?: string | null;
        };
    }>().props;

    const [selectedYear, setSelectedYear] = useState(year);
    const [selectedMonth, setSelectedMonth] = useState(month);
    const [selectedVendorNuit, setSelectedVendorNuit] = useState((filters?.vendor_nuit ?? '') as string);
    const [selectedIncomeType, setSelectedIncomeType] = useState((filters?.income_type ?? '') as string);
    const [selectedStatus, setSelectedStatus] = useState((filters?.status ?? '') as string);
    const [declarationReference, setDeclarationReference] = useState('');
    const [statePaymentReference, setStatePaymentReference] = useState('');

    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);
    const months = Array.from({ length: 12 }, (_, i) => ({
        value: i + 1,
        label: new Date(2000, i).toLocaleString('pt', { month: 'long' }),
    }));
    const years = [selectedYear - 2, selectedYear - 1, selectedYear, selectedYear + 1];

    const refresh = () => {
        router.get(
            route('sce.tax.withholding.declaration.page'),
            {
                year: selectedYear,
                month: selectedMonth,
                vendor_nuit: selectedVendorNuit || undefined,
                income_type: selectedIncomeType || undefined,
                status: selectedStatus || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const exportCsv = () => {
        window.location.href = route('sce.tax.withholding.declaration.export', {
            year: selectedYear,
            month: selectedMonth,
            vendor_nuit: selectedVendorNuit || undefined,
            income_type: selectedIncomeType || undefined,
            status: selectedStatus || undefined,
        });
    };

    const settleDeclaration = (action: 'mark_pending' | 'mark_declared' | 'mark_paid') => {
        router.post(
            route('sce.tax.withholding.declaration.settlement'),
            {
                year: selectedYear,
                month: selectedMonth,
                vendor_nuit: selectedVendorNuit || undefined,
                income_type: selectedIncomeType || undefined,
                status: selectedStatus || undefined,
                action,
                declaration_reference: declarationReference || undefined,
                state_payment_reference: statePaymentReference || undefined,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (action === 'mark_pending') {
                        setDeclarationReference('');
                        setStatePaymentReference('');
                    }
                },
            }
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Impostos') }, { label: t('Declaração de Retenções') }]}
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
            <AccountingSuiteNavigation section="tax" className="mb-4" />

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
                <CardContent className="px-4 pb-4 pt-0 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <Input
                        value={selectedVendorNuit}
                        onChange={(event) => setSelectedVendorNuit(event.target.value)}
                        placeholder={t('Filtrar por NUIT do beneficiário')}
                    />
                    <Select value={selectedIncomeType || 'all'} onValueChange={(value) => setSelectedIncomeType(value === 'all' ? '' : value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Tipo de rendimento')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t('Todos os tipos')}</SelectItem>
                            {incomeTypes.map((incomeType) => (
                                <SelectItem key={incomeType} value={incomeType}>
                                    {incomeType}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={selectedStatus || 'all'} onValueChange={(value) => setSelectedStatus(value === 'all' ? '' : value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Estado da retenção')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t('Todos os estados')}</SelectItem>
                            <SelectItem value="pending">{t('pending')}</SelectItem>
                            <SelectItem value="declared">{t('declared')}</SelectItem>
                            <SelectItem value="paid">{t('paid')}</SelectItem>
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            {canManageTaxReports && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>{t('Liquidação das Retenções ao Estado')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 lg:grid-cols-4 gap-3">
                        <Input
                            value={declarationReference}
                            onChange={(event) => setDeclarationReference(event.target.value)}
                            placeholder={t('Referência da declaração (obrigatória para declarado)')}
                        />
                        <Input
                            value={statePaymentReference}
                            onChange={(event) => setStatePaymentReference(event.target.value)}
                            placeholder={t('Referência pagamento Estado (obrigatória para pago)')}
                        />
                        <Button variant="outline" onClick={() => settleDeclaration('mark_declared')}>
                            {t('Marcar como Declarado')}
                        </Button>
                        <div className="flex gap-2">
                            <Button onClick={() => settleDeclaration('mark_paid')} className="flex-1">
                                {t('Marcar como Pago')}
                            </Button>
                            <Button variant="ghost" onClick={() => settleDeclaration('mark_pending')} className="flex-1">
                                {t('Voltar a Pendente')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
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
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-blue-600">{t('Retido Pago ao Estado')}</p>
                        <p className="text-2xl font-bold text-blue-800">{fmt(declaration.totals.withholding_paid)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-amber-700">{t('Retido em Aberto')}</p>
                        <p className="text-2xl font-bold text-amber-800">{fmt(declaration.totals.withholding_pending)}</p>
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

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>{t('Resumo de Liquidação ao Estado')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Estado')}</th>
                                <th className="p-3 text-right">{t('Transacções')}</th>
                                <th className="p-3 text-right">{t('Bruto')}</th>
                                <th className="p-3 text-right">{t('Retenção')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {declaration.status_summary.length === 0 ? (
                                <tr>
                                    <td className="p-6 text-center text-muted-foreground" colSpan={4}>
                                        {t('Sem dados de liquidação para o período selecionado.')}
                                    </td>
                                </tr>
                            ) : (
                                declaration.status_summary.map((line) => (
                                    <tr key={`status-${line.status}`} className="border-t">
                                        <td className="p-3">{line.status}</td>
                                        <td className="p-3 text-right font-mono">{line.transactions}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.gross_amount)}</td>
                                        <td className="p-3 text-right font-mono text-red-700">{fmt(line.withholding_amount)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>{t('Mapa Detalhado de Retenções')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0 overflow-x-auto">
                    <table className="w-full text-sm min-w-[1400px]">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Data')}</th>
                                <th className="p-3 text-left">{t('Beneficiário')}</th>
                                <th className="p-3 text-left">{t('NUIT')}</th>
                                <th className="p-3 text-left">{t('País')}</th>
                                <th className="p-3 text-left">{t('Tipo')}</th>
                                <th className="p-3 text-left">{t('Tratamento')}</th>
                                <th className="p-3 text-right">{t('Taxa')}</th>
                                <th className="p-3 text-right">{t('Bruto')}</th>
                                <th className="p-3 text-right">{t('Retenção')}</th>
                                <th className="p-3 text-right">{t('Líquido')}</th>
                                <th className="p-3 text-left">{t('Estado')}</th>
                                <th className="p-3 text-left">{t('Ref. Declaração')}</th>
                                <th className="p-3 text-left">{t('Ref. Pagamento Estado')}</th>
                                <th className="p-3 text-left">{t('Documento')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {declaration.detailed_map.length === 0 ? (
                                <tr>
                                    <td className="p-8 text-center text-muted-foreground" colSpan={14}>
                                        {t('Sem movimentos detalhados para o período/filtro selecionado.')}
                                    </td>
                                </tr>
                            ) : (
                                declaration.detailed_map.map((line) => (
                                    <tr key={line.id} className="border-t">
                                        <td className="p-3">{line.transaction_date ? new Date(line.transaction_date).toLocaleDateString('pt') : '-'}</td>
                                        <td className="p-3">{line.beneficiary || '-'}</td>
                                        <td className="p-3 font-mono">{line.beneficiary_tax_number || '-'}</td>
                                        <td className="p-3">{line.beneficiary_country || '-'}</td>
                                        <td className="p-3">{line.income_type || '-'}</td>
                                        <td className="p-3">{line.withholding_treatment || '-'}</td>
                                        <td className="p-3 text-right font-mono">{line.rate}%</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.gross_amount)}</td>
                                        <td className="p-3 text-right font-mono text-red-700">{fmt(line.withholding_amount)}</td>
                                        <td className="p-3 text-right font-mono text-green-700">{fmt(line.net_amount)}</td>
                                        <td className="p-3">{line.status}</td>
                                        <td className="p-3">{line.declaration_reference || '-'}</td>
                                        <td className="p-3">{line.state_payment_reference || '-'}</td>
                                        <td className="p-3">{line.document_reference || '-'}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>{t('Histórico por Beneficiário e Tipo de Rendimento')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0 overflow-x-auto">
                    <table className="w-full text-sm min-w-[900px]">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3 text-left">{t('Beneficiário')}</th>
                                <th className="p-3 text-left">{t('NUIT')}</th>
                                <th className="p-3 text-left">{t('País')}</th>
                                <th className="p-3 text-left">{t('Tipo')}</th>
                                <th className="p-3 text-right">{t('Transacções')}</th>
                                <th className="p-3 text-right">{t('Bruto')}</th>
                                <th className="p-3 text-right">{t('Retenção')}</th>
                                <th className="p-3 text-right">{t('Líquido')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {declaration.history_by_vendor.length === 0 ? (
                                <tr>
                                    <td className="p-8 text-center text-muted-foreground" colSpan={8}>
                                        {t('Sem histórico para o período/filtro selecionado.')}
                                    </td>
                                </tr>
                            ) : (
                                declaration.history_by_vendor.map((line, index) => (
                                    <tr key={`${line.beneficiary_tax_number}-${line.income_type}-${index}`} className="border-t">
                                        <td className="p-3">{line.beneficiary || '-'}</td>
                                        <td className="p-3 font-mono">{line.beneficiary_tax_number || '-'}</td>
                                        <td className="p-3">{line.beneficiary_country || '-'}</td>
                                        <td className="p-3">{line.income_type || '-'}</td>
                                        <td className="p-3 text-right font-mono">{line.transactions}</td>
                                        <td className="p-3 text-right font-mono">{fmt(line.gross_amount)}</td>
                                        <td className="p-3 text-right font-mono text-red-700">{fmt(line.withholding_amount)}</td>
                                        <td className="p-3 text-right font-mono text-green-700">{fmt(line.net_amount)}</td>
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
