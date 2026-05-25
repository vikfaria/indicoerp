import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export default function BalanceSheet() {
    const { t } = useTranslation();
    const { data, asOfDate } = usePage<{ data: any; asOfDate: string }>().props;
    const [date, setDate] = useState(asOfDate);
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);

    const refresh = () => router.get(route('sce.reports.balance-sheet'), { date }, { preserveState: true });

    const Row = ({ label, value, bold }: { label: string; value: number; bold?: boolean }) => (
        <div className={`flex justify-between py-1.5 px-2 ${bold ? 'font-bold border-t-2 bg-muted/30' : 'hover:bg-muted/20'}`}>
            <span className="text-sm">{label}</span><span className={`font-mono text-sm ${bold ? 'text-primary' : ''}`}>{fmt(value)}</span>
        </div>
    );

    const ac = data.activo?.activo_corrente || {};
    const cp = data.capital_proprio || {};
    const pnc = data.passivo?.passivo_nao_corrente || {};
    const pc = data.passivo?.passivo_corrente || {};

    const totalActivoCorriente = Object.values(ac).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const totalActivo = (data.activo?.activo_nao_corrente || 0) + totalActivoCorriente;
    const totalCP = Object.values(cp).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const totalPNC = Object.values(pnc).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const totalPC = Object.values(pc).reduce((s: number, v) => s + (Number(v) || 0), 0);
    const totalPassivo = totalPNC + totalPC;

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Relatórios') }, { label: t('Balanço') }]} pageTitle={t('Balanço — PGC-MZ')}>
            <Head title={t('Balanço')} />
            <AccountingSuiteNavigation section="reports" className="mb-4" />
            <div className="flex items-center gap-3 mb-6">
                <Input type="date" value={date} onChange={e => setDate(e.target.value)} className="w-44" />
                <Button size="sm" onClick={refresh}>{t('Actualizar')}</Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* ACTIVO */}
                <Card>
                    <CardHeader className="bg-blue-50 border-b"><CardTitle className="text-blue-800">{t('ACTIVO')}</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <div className="px-3 py-2 bg-muted/30 text-xs font-semibold text-muted-foreground uppercase">{t('Activo Não Corrente')}</div>
                        <Row label={t('Imobilizações')} value={data.activo?.activo_nao_corrente || 0} />
                        <div className="px-3 py-2 bg-muted/30 text-xs font-semibold text-muted-foreground uppercase">{t('Activo Corrente')}</div>
                        <Row label={t('Inventários')} value={ac.inventarios} />
                        <Row label={t('Clientes')} value={ac.clientes} />
                        <Row label={t('Estado e Outros Entes Públicos')} value={ac.estado} />
                        <Row label={t('Outros Devedores')} value={ac.outros_devedores} />
                        <Row label={t('Diferimentos')} value={ac.diferimentos} />
                        <Row label={t('Caixa e Bancos')} value={ac.caixa_bancos} />
                        <Row label={t('TOTAL ACTIVO')} value={totalActivo} bold />
                    </CardContent>
                </Card>

                {/* CP + PASSIVO */}
                <Card>
                    <CardHeader className="bg-green-50 border-b"><CardTitle className="text-green-800">{t('CAPITAL PRÓPRIO + PASSIVO')}</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <div className="px-3 py-2 bg-muted/30 text-xs font-semibold text-muted-foreground uppercase">{t('Capital Próprio')}</div>
                        <Row label={t('Capital Social')} value={cp.capital_social} />
                        <Row label={t('Reservas')} value={cp.reservas} />
                        <Row label={t('Resultados Transitados')} value={cp.resultados_transitados} />
                        <Row label={t('Resultado Líquido')} value={cp.resultado_liquido} />
                        <Row label={t('TOTAL CAPITAL PRÓPRIO')} value={totalCP} bold />

                        <div className="px-3 py-2 bg-muted/30 text-xs font-semibold text-muted-foreground uppercase">{t('Passivo Não Corrente')}</div>
                        <Row label={t('Empréstimos MLP')} value={pnc.emprestimos_mlp} />
                        <Row label={t('Provisões')} value={pnc.provisoes} />

                        <div className="px-3 py-2 bg-muted/30 text-xs font-semibold text-muted-foreground uppercase">{t('Passivo Corrente')}</div>
                        <Row label={t('Fornecedores')} value={pc.fornecedores} />
                        <Row label={t('Estado')} value={pc.estado} />
                        <Row label={t('Empréstimos CP')} value={pc.emprestimos_cp} />
                        <Row label={t('Outros Credores')} value={pc.outros_credores} />
                        <Row label={t('TOTAL PASSIVO')} value={totalPassivo} bold />
                        <Row label={t('TOTAL CP + PASSIVO')} value={totalCP + totalPassivo} bold />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
