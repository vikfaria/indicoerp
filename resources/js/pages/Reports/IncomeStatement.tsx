import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export default function IncomeStatement() {
    const { t } = useTranslation();
    const { data, startDate, endDate } = usePage<{ data: any; startDate: string; endDate: string }>().props;
    const [sd, setSd] = useState(startDate);
    const [ed, setEd] = useState(endDate);
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);
    const refresh = () => router.get(route('sce.reports.income-statement'), { start_date: sd, end_date: ed }, { preserveState: true });

    const r = data.rendimentos || {};
    const g = data.gastos || {};
    const totalRendimentos = Object.values(r).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const totalGastos = Object.values(g).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const resultadoOperacional = totalRendimentos - totalGastos;
    const resultadoLiquido = resultadoOperacional - (data.imposto_rendimento || 0);

    const Row = ({ label, value, bold, indent }: { label: string; value: number; bold?: boolean; indent?: boolean }) => (
        <div className={`flex justify-between py-1.5 px-2 ${bold ? 'font-bold border-t-2 bg-muted/30' : 'hover:bg-muted/20'} ${indent ? 'pl-6' : ''}`}>
            <span className="text-sm">{label}</span><span className={`font-mono text-sm ${value < 0 ? 'text-red-600' : ''}`}>{fmt(value)}</span>
        </div>
    );

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Relatórios') }, { label: t('Demonstração Resultados') }]} pageTitle={t('Demonstração de Resultados por Natureza')}>
            <Head title={t('Demonstração de Resultados')} />
            <AccountingSuiteNavigation section="reports" className="mb-4" />
            <div className="flex items-center gap-3 mb-6">
                <Input type="date" value={sd} onChange={e => setSd(e.target.value)} className="w-40" />
                <span className="text-muted-foreground">{t('a')}</span>
                <Input type="date" value={ed} onChange={e => setEd(e.target.value)} className="w-40" />
                <Button size="sm" onClick={refresh}>{t('Actualizar')}</Button>
            </div>

            <Card>
                <CardHeader className="bg-gradient-to-r from-green-50 to-emerald-50 border-b"><CardTitle className="text-green-800">{t('Rendimentos')}</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <Row label={t('Vendas')} value={r.vendas} indent />
                    <Row label={t('Prestações de Serviços')} value={r.prestacoes_servicos} indent />
                    <Row label={t('Variação da Produção')} value={r.variacao_producao} indent />
                    <Row label={t('Trabalhos para a Própria Entidade')} value={r.trabalhos_propria_entidade} indent />
                    <Row label={t('Subsídios')} value={r.subsidios} indent />
                    <Row label={t('Outros Rendimentos')} value={r.outros_rendimentos} indent />
                    <Row label={t('Rendimentos Financeiros')} value={r.rendimentos_financeiros} indent />
                    <Row label={t('TOTAL RENDIMENTOS')} value={totalRendimentos} bold />
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardHeader className="bg-gradient-to-r from-red-50 to-orange-50 border-b"><CardTitle className="text-red-800">{t('Gastos')}</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <Row label={t('CMVMC')} value={g.cmvmc} indent />
                    <Row label={t('Fornecimentos e Serviços Externos')} value={g.fornecimentos_servicos} indent />
                    <Row label={t('Gastos com Pessoal')} value={g.gastos_pessoal} indent />
                    <Row label={t('Depreciações e Amortizações')} value={g.depreciacao_amortizacao} indent />
                    <Row label={t('Perdas por Imparidade')} value={g.perdas_imparidade} indent />
                    <Row label={t('Provisões')} value={g.provisoes} indent />
                    <Row label={t('Outros Gastos')} value={g.outros_gastos} indent />
                    <Row label={t('Gastos Financeiros')} value={g.gastos_financeiros} indent />
                    <Row label={t('TOTAL GASTOS')} value={totalGastos} bold />
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="p-0">
                    <Row label={t('RESULTADO OPERACIONAL')} value={resultadoOperacional} bold />
                    <Row label={t('Imposto sobre o Rendimento')} value={data.imposto_rendimento || 0} indent />
                    <div className={`flex justify-between py-3 px-2 font-bold text-lg border-t-4 ${resultadoLiquido >= 0 ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                        <span>{t('RESULTADO LÍQUIDO')}</span><span className="font-mono">{fmt(resultadoLiquido)}</span>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
