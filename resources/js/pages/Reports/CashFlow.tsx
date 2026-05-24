import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ArrowUpCircle, ArrowDownCircle, Wallet } from 'lucide-react';
import { useState } from 'react';

export default function CashFlow() {
    const { t } = useTranslation();
    const { data, startDate, endDate } = usePage<{ data: any; startDate: string; endDate: string }>().props;
    const [sd, setSd] = useState(startDate);
    const [ed, setEd] = useState(endDate);
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n || 0);
    const refresh = () => router.get(route('sce.reports.cash-flow'), { start_date: sd, end_date: ed }, { preserveState: true });

    const op = data.actividades_operacionais || {};

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Activos & Relatórios') }, { label: t('Fluxos de Caixa') }]} pageTitle={t('Demonstração de Fluxos de Caixa')}>
            <Head title={t('Fluxos de Caixa')} />
            <div className="flex items-center gap-3 mb-6">
                <Input type="date" value={sd} onChange={e => setSd(e.target.value)} className="w-40" />
                <span className="text-muted-foreground">{t('a')}</span>
                <Input type="date" value={ed} onChange={e => setEd(e.target.value)} className="w-40" />
                <Button size="sm" onClick={refresh}>{t('Actualizar')}</Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4 flex items-center gap-3"><Wallet className="h-8 w-8 text-blue-500" /><div><p className="text-xs text-blue-600">{t('Saldo Inicial')}</p><p className="text-xl font-bold text-blue-800">{fmt(data.saldo_inicial)}</p></div></CardContent>
                </Card>
                <Card className={`bg-gradient-to-br ${data.variacao >= 0 ? 'from-green-50 to-green-100 border-green-200' : 'from-red-50 to-red-100 border-red-200'}`}>
                    <CardContent className="p-4 flex items-center gap-3">
                        {data.variacao >= 0 ? <ArrowUpCircle className="h-8 w-8 text-green-500" /> : <ArrowDownCircle className="h-8 w-8 text-red-500" />}
                        <div><p className={`text-xs ${data.variacao >= 0 ? 'text-green-600' : 'text-red-600'}`}>{t('Variação')}</p><p className={`text-xl font-bold ${data.variacao >= 0 ? 'text-green-800' : 'text-red-800'}`}>{fmt(data.variacao)}</p></div>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-indigo-50 to-indigo-100 border-indigo-200">
                    <CardContent className="p-4 flex items-center gap-3"><Wallet className="h-8 w-8 text-indigo-500" /><div><p className="text-xs text-indigo-600">{t('Saldo Final')}</p><p className="text-xl font-bold text-indigo-800">{fmt(data.saldo_final)}</p></div></CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="bg-gradient-to-r from-purple-50 to-indigo-50 border-b"><CardTitle>{t('Actividades Operacionais')}</CardTitle></CardHeader>
                <CardContent className="p-0">
                    {[
                        { label: t('Recebimentos de Clientes'), value: op.recebimentos_clientes, positive: true },
                        { label: t('Pagamentos a Fornecedores'), value: op.pagamentos_fornecedores, positive: false },
                        { label: t('Pagamentos ao Pessoal'), value: op.pagamentos_pessoal, positive: false },
                        { label: t('Pagamentos de Impostos'), value: op.pagamentos_impostos, positive: false },
                    ].map((item, i) => (
                        <div key={i} className="flex justify-between py-2.5 px-4 hover:bg-muted/20 border-b last:border-b-0">
                            <span className="text-sm">{item.label}</span>
                            <span className={`font-mono text-sm font-medium ${item.positive ? 'text-green-600' : 'text-red-600'}`}>{fmt(item.value)}</span>
                        </div>
                    ))}
                    <div className="flex justify-between py-3 px-4 font-bold border-t-2 bg-muted/30">
                        <span>{t('Fluxo Líquido Operacional')}</span>
                        <span className={`font-mono ${op.fluxo_liquido_operacional >= 0 ? 'text-green-700' : 'text-red-700'}`}>{fmt(op.fluxo_liquido_operacional)}</span>
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
