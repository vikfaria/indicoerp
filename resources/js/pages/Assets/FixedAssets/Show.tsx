import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TrendingDown } from 'lucide-react';
import { useState } from 'react';

interface Asset { id: number; asset_code: string; name: string; category: string; acquisition_date: string; acquisition_cost: number; residual_value: number; useful_life_months: number; accumulated_depreciation: number; net_book_value: number; depreciation_method: string; status: string; location: string; responsible_person: string; disposal_date?: string | null; disposal_proceeds?: number | null; impairment_losses?: number; revaluation_surplus?: number; }
interface DepEntry { id: number; depreciation_date: string; depreciation_amount: number; accumulated_after: number; net_book_value_after: number; }
interface Schedule { period: string; depreciation: number; accumulated: number; net_book_value: number; }

export default function ShowFixedAsset() {
    const { t } = useTranslation();
    const { asset, depreciations, schedule } = usePage<{ asset: Asset; depreciations: DepEntry[]; schedule: Schedule[] }>().props;
    const [showDisposal, setShowDisposal] = useState(false);
    const disposalForm = useForm({
        disposal_date: new Date().toISOString().split('T')[0],
        disposal_proceeds: '0',
    });
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);
    const depPercent = asset.acquisition_cost > 0 ? Math.round((asset.accumulated_depreciation / asset.acquisition_cost) * 100) : 0;
    const disposalDate = asset.disposal_date ? new Date(asset.disposal_date).toLocaleDateString('pt') : '-';
    const disposalProceeds = asset.disposal_proceeds ? fmt(asset.disposal_proceeds) : fmt(0);

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Activos') }, { label: t('Activos Fixos'), url: route('sce.fixed-assets.index') }, { label: asset.asset_code }]}
            pageTitle={`${asset.asset_code} — ${asset.name}`} backUrl={route('sce.fixed-assets.index')}>
            <Head title={asset.name} />
            <AccountingSuiteNavigation section="assets" className="mb-4" />

            <Card className="mb-6 border-amber-200 bg-amber-50/70">
                <CardContent className="p-4 text-sm text-amber-900">
                    {t('Escopo automático: aquisição, depreciação em linha recta e baixa contabilística. Reavaliação, impairment e métodos avançados permanecem como validação contabilística manual.')}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200">
                    <CardContent className="p-6 text-center"><p className="text-xs text-blue-600">{t('Custo Aquisição')}</p><p className="text-2xl font-bold text-blue-800">{fmt(asset.acquisition_cost)}</p></CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-red-50 to-orange-50 border-red-200">
                    <CardContent className="p-6 text-center">
                        <p className="text-xs text-red-600">{t('Depreciação Acumulada')}</p>
                        <p className="text-2xl font-bold text-red-800">{fmt(asset.accumulated_depreciation)}</p>
                        <div className="mt-2 w-full bg-gray-200 rounded-full h-2"><div className="bg-red-500 h-2 rounded-full" style={{ width: `${depPercent}%` }} /></div>
                        <p className="text-xs text-red-500 mt-1">{depPercent}%</p>
                    </CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-green-50 to-emerald-50 border-green-200">
                    <CardContent className="p-6 text-center"><p className="text-xs text-green-600">{t('Valor Líquido')}</p><p className="text-2xl font-bold text-green-800">{fmt(asset.net_book_value)}</p><Badge className={`mt-2 border-0 ${asset.status === 'active' ? 'bg-green-100 text-green-700' : asset.status === 'disposed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>{asset.status}</Badge></CardContent>
                </Card>
            </div>

            {asset.status !== 'disposed' && (
                <div className="flex justify-end mb-4">
                    <Button variant="outline" onClick={() => setShowDisposal(true)}>
                        {t('Baixar Activo')}
                    </Button>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Details */}
                <Card>
                    <CardHeader><CardTitle>{t('Detalhes')}</CardTitle></CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            {[
                                [t('Categoria'), asset.category], [t('Data Aquisição'), new Date(asset.acquisition_date).toLocaleDateString('pt')],
                                [t('Vida Útil'), `${asset.useful_life_months} meses`], [t('Método'), asset.depreciation_method],
                                [t('Valor Residual'), fmt(asset.residual_value)], [t('Localização'), asset.location || '-'],
                                [t('Responsável'), asset.responsible_person || '-'],
                                [t('Data Baixa'), disposalDate],
                                [t('Proveito Baixa'), disposalProceeds],
                            ].map(([label, val], i) => (
                                <div key={i}><dt className="text-xs text-muted-foreground">{label}</dt><dd className="font-medium">{val}</dd></div>
                            ))}
                        </dl>
                    </CardContent>
                </Card>

                {/* Depreciation history */}
                <Card>
                    <CardHeader><CardTitle className="flex items-center gap-2"><TrendingDown className="h-4 w-4" /> {t('Histórico Depreciações')}</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {depreciations.length === 0 ? <p className="p-6 text-center text-muted-foreground">{t('Sem depreciações registadas')}</p> : (
                            <div className="max-h-[300px] overflow-y-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 sticky top-0"><tr><th className="p-2 text-left">{t('Data')}</th><th className="p-2 text-right">{t('Depreciação')}</th><th className="p-2 text-right">{t('Acumulada')}</th><th className="p-2 text-right">{t('VL')}</th></tr></thead>
                                    <tbody>{depreciations.map(d => (
                                        <tr key={d.id} className="border-t"><td className="p-2 text-xs">{new Date(d.depreciation_date).toLocaleDateString('pt')}</td><td className="p-2 text-right font-mono text-xs text-red-600">{fmt(d.depreciation_amount)}</td><td className="p-2 text-right font-mono text-xs">{fmt(d.accumulated_after)}</td><td className="p-2 text-right font-mono text-xs text-green-600">{fmt(d.net_book_value_after)}</td></tr>
                                    ))}</tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Future schedule */}
            {schedule.length > 0 && (
                <Card className="mt-6">
                    <CardHeader><CardTitle>{t('Plano de Depreciação Futuro')}</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <div className="max-h-[300px] overflow-y-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 sticky top-0"><tr><th className="p-2 text-left">{t('Período')}</th><th className="p-2 text-right">{t('Depreciação')}</th><th className="p-2 text-right">{t('Acumulada')}</th><th className="p-2 text-right">{t('Valor Líquido')}</th></tr></thead>
                                <tbody>{schedule.map((s, i) => (
                                    <tr key={i} className="border-t"><td className="p-2 text-xs">{s.period}</td><td className="p-2 text-right font-mono text-xs">{fmt(s.depreciation)}</td><td className="p-2 text-right font-mono text-xs">{fmt(s.accumulated)}</td><td className="p-2 text-right font-mono text-xs text-green-600">{fmt(s.net_book_value)}</td></tr>
                                ))}</tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            <Dialog open={showDisposal} onOpenChange={setShowDisposal}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Registar Baixa do Activo')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        disposalForm.post(route('sce.fixed-assets.dispose', asset.id), {
                            preserveScroll: true,
                            onSuccess: () => setShowDisposal(false),
                        });
                    }} className="space-y-4">
                        <div>
                            <Label>{t('Data da baixa')}</Label>
                            <Input type="date" value={disposalForm.data.disposal_date} onChange={(e) => disposalForm.setData('disposal_date', e.target.value)} />
                        </div>
                        <div>
                            <Label>{t('Proveitos da alienação (MZN)')}</Label>
                            <Input type="number" step="0.01" min="0" value={disposalForm.data.disposal_proceeds} onChange={(e) => disposalForm.setData('disposal_proceeds', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button variant="outline" type="button" onClick={() => setShowDisposal(false)}>{t('Cancelar')}</Button>
                            <Button type="submit" disabled={disposalForm.processing}>{t('Confirmar Baixa')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
