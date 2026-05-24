import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaxNavigation from '@/components/tax/tax-navigation';
import { RefreshCw } from 'lucide-react';

interface AnnualDeclaration {
    fiscal_year: string;
    generated_at: string;
    vat: {
        output_vat: number;
        supported_vat: number;
        deductible_vat: number;
        non_deductible_vat: number;
        regularizations: number;
        vat_payable: number;
        vat_recoverable: number;
        net_position: number;
    };
    irpc: {
        accounting_result: number;
        taxable_income: number;
        irpc_rate: number;
        irpc_due: number;
        ppc_total: number;
        withholdings_suffered: number;
        net_payable: number;
    };
    withholding: {
        transaction_count: number;
        gross_amount: number;
        withholding_amount: number;
        net_amount: number;
    };
    model20: {
        lines: Array<unknown>;
        unmapped_accounts: Array<unknown>;
        totals: {
            mapped_movements: number;
            unmapped_movements: number;
        };
    };
}

export default function AnnualDeclarationPage() {
    const { t } = useTranslation();
    const { year, declaration } = usePage<{ year: number; declaration: AnnualDeclaration }>().props;
    const [selectedYear, setSelectedYear] = useState(year);

    const years = [selectedYear - 2, selectedYear - 1, selectedYear, selectedYear + 1];
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);

    const refresh = () => {
        router.get(route('sce.tax.annual-declaration.page'), { year: selectedYear }, { preserveState: true, replace: true });
    };

    const vatStatus = declaration.vat.net_position >= 0 ? t('IVA a pagar') : t('IVA a recuperar');
    const vatStatusValue = declaration.vat.net_position >= 0 ? declaration.vat.vat_payable : declaration.vat.vat_recoverable;
    const irpcStatus = declaration.irpc.net_payable >= 0 ? t('IRPC a pagar') : t('IRPC a recuperar');

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Impostos') }, { label: t('Declaração Anual') }]}
            pageTitle={t('Declaração Anual Fiscal')}
            pageActions={
                <Button size="sm" onClick={refresh}>
                    <RefreshCw className="h-4 w-4 mr-1" /> {t('Actualizar')}
                </Button>
            }
        >
            <Head title={t('Declaração Anual')} />
            <TaxNavigation className="mb-4" />

            <Card className="mb-6">
                <CardContent className="p-4 flex flex-wrap items-center gap-3">
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
                    <span className="text-sm text-muted-foreground">
                        {t('Gerado em')}: {new Date(declaration.generated_at).toLocaleString('pt')}
                    </span>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-blue-600">{vatStatus}</p>
                        <p className="text-2xl font-bold text-blue-800">{fmt(vatStatusValue)}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-purple-600">{irpcStatus}</p>
                        <p className="text-2xl font-bold text-purple-800">{fmt(Math.abs(declaration.irpc.net_payable))}</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
                    <CardContent className="p-5 text-center">
                        <p className="text-xs text-emerald-600">{t('Retenções no Ano')}</p>
                        <p className="text-2xl font-bold text-emerald-800">{fmt(declaration.withholding.withholding_amount)}</p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Resumo IVA')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <Row label={t('IVA Liquidado')} value={declaration.vat.output_vat} fmt={fmt} />
                        <Row label={t('IVA Suportado')} value={declaration.vat.supported_vat} fmt={fmt} />
                        <Row label={t('IVA Dedutível')} value={declaration.vat.deductible_vat} fmt={fmt} />
                        <Row label={t('IVA Não Dedutível')} value={declaration.vat.non_deductible_vat} fmt={fmt} />
                        <Row label={t('Regularizações')} value={declaration.vat.regularizations} fmt={fmt} />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Resumo IRPC')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <Row label={t('Resultado Contabilístico')} value={declaration.irpc.accounting_result} fmt={fmt} />
                        <Row label={t('Matéria Colectável')} value={declaration.irpc.taxable_income} fmt={fmt} />
                        <Row label={`${t('IRPC')} (${declaration.irpc.irpc_rate}%)`} value={declaration.irpc.irpc_due} fmt={fmt} />
                        <Row label={t('Pagamentos por Conta')} value={declaration.irpc.ppc_total} fmt={fmt} />
                        <Row label={t('Retenções Sofridas')} value={declaration.irpc.withholdings_suffered} fmt={fmt} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Retenções na Fonte')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <Row label={t('Transacções')} value={declaration.withholding.transaction_count} />
                        <Row label={t('Valor Bruto')} value={declaration.withholding.gross_amount} fmt={fmt} />
                        <Row label={t('Retenção')} value={declaration.withholding.withholding_amount} fmt={fmt} />
                        <Row label={t('Valor Líquido')} value={declaration.withholding.net_amount} fmt={fmt} />
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Estado Modelo 20')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <Row label={t('Linhas Mapeadas')} value={declaration.model20.lines.length} />
                        <Row label={t('Contas sem Mapeamento')} value={declaration.model20.unmapped_accounts.length} />
                        <Row label={t('Movimentos Mapeados')} value={declaration.model20.totals.mapped_movements} />
                        <Row label={t('Movimentos sem Mapeamento')} value={declaration.model20.totals.unmapped_movements} />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Row({
    label,
    value,
    fmt,
}: {
    label: string;
    value: number;
    fmt?: (value: number) => string;
}) {
    return (
        <div className="flex items-center justify-between border-b pb-2 last:border-b-0 last:pb-0">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-mono font-medium">{fmt ? fmt(value) : value}</span>
        </div>
    );
}
