import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/ui/data-table';
import { SearchInput } from '@/components/ui/search-input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { Plus, Eye, Building2, Play } from 'lucide-react';
import { useState } from 'react';

interface Asset { id: number; asset_code: string; name: string; category: string; acquisition_date: string; acquisition_cost: number; accumulated_depreciation: number; net_book_value: number; status: string; }
interface Summary { total_acquisition_cost: number; total_accumulated_depreciation: number; total_net_book_value: number; active_count: number; fully_depreciated_count: number; disposed_count: number; }

export default function FixedAssetsIndex() {
    const { t } = useTranslation();
    const { assets, summary, byCategory } = usePage<{ assets: any; summary: Summary; byCategory: any[] }>().props;
    const [search, setSearch] = useState('');
    const [showDepreciation, setShowDepreciation] = useState(false);

    const depForm = useForm({ year: String(new Date().getFullYear()), month: String(new Date().getMonth() + 1) });
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);

    const handleSearch = () => router.get(route('sce.fixed-assets.index'), { search }, { preserveState: true, replace: true });
    const runDepreciation = (e: React.FormEvent) => {
        e.preventDefault();
        depForm.post(route('sce.fixed-assets.depreciation'), { onSuccess: () => setShowDepreciation(false) });
    };

    const statusBadge = (s: string) => {
        const map: Record<string, string> = { active: 'bg-green-100 text-green-700', fully_depreciated: 'bg-gray-100 text-gray-600', disposed: 'bg-red-100 text-red-700', impaired: 'bg-yellow-100 text-yellow-700' };
        return <Badge className={`border-0 ${map[s] || map.active}`}>{s}</Badge>;
    };

    const columns = [
        { key: 'asset_code', header: t('Código'), render: (v: string) => <span className="font-mono font-semibold">{v}</span> },
        { key: 'name', header: t('Nome') },
        { key: 'category', header: t('Categoria'), render: (v: string) => <Badge variant="outline">{v}</Badge> },
        { key: 'acquisition_cost', header: t('Custo Aquisição'), render: (v: number) => <span className="font-mono">{fmt(v)}</span> },
        { key: 'accumulated_depreciation', header: t('Dep. Acumulada'), render: (v: number) => <span className="font-mono text-red-600">{fmt(v)}</span> },
        { key: 'net_book_value', header: t('Valor Líquido'), render: (v: number) => <span className="font-mono text-green-600 font-semibold">{fmt(v)}</span> },
        { key: 'status', header: t('Estado'), render: (v: string) => statusBadge(v) },
        { key: 'actions', header: '', render: (_: any, a: Asset) => <Button variant="ghost" size="sm" onClick={() => router.get(route('sce.fixed-assets.show', a.id))} className="h-8 w-8 p-0 text-blue-600"><Eye className="h-4 w-4" /></Button> },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Activos') }, { label: t('Activos Fixos') }]} pageTitle={t('Registo de Activos Fixos')}
            pageActions={<div className="flex gap-2">
                <Button size="sm" variant="outline" onClick={() => setShowDepreciation(true)}><Play className="h-4 w-4 mr-1" /> {t('Depreciar')}</Button>
                <Button size="sm" onClick={() => router.visit(route('sce.fixed-assets.create'))}><Plus className="h-4 w-4 mr-1" /> {t('Novo Activo')}</Button>
            </div>}>
            <Head title={t('Activos Fixos')} />
            <AccountingSuiteNavigation section="assets" className="mb-4" />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                    <CardContent className="p-4"><p className="text-xs text-blue-600">{t('Custo Total Aquisição')}</p><p className="text-2xl font-bold text-blue-800">{fmt(summary.total_acquisition_cost)}</p><p className="text-xs text-blue-500 mt-1">{summary.active_count} {t('activos')}</p></CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
                    <CardContent className="p-4"><p className="text-xs text-red-600">{t('Depreciação Acumulada')}</p><p className="text-2xl font-bold text-red-800">{fmt(summary.total_accumulated_depreciation)}</p></CardContent>
                </Card>
                <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
                    <CardContent className="p-4"><p className="text-xs text-green-600">{t('Valor Líquido Total')}</p><p className="text-2xl font-bold text-green-800">{fmt(summary.total_net_book_value)}</p></CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-4 border-b bg-gray-50/50">
                    <div className="max-w-md"><SearchInput value={search} onChange={setSearch} onSearch={handleSearch} placeholder={t('Pesquisar activos...')} /></div>
                </CardContent>
                <CardContent className="p-0"><DataTable data={assets.data || []} columns={columns} emptyState={<div className="py-12 text-center"><Building2 className="h-12 w-12 text-muted-foreground mx-auto mb-4" /><h3 className="text-lg font-semibold">{t('Sem activos registados')}</h3></div>} /></CardContent>
                {assets.meta && <CardContent className="px-4 py-2 border-t"><Pagination data={{...assets, ...assets.meta}} routeName="sce.fixed-assets.index" filters={{ search }} /></CardContent>}
            </Card>

            <Dialog open={showDepreciation} onOpenChange={setShowDepreciation}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>{t('Executar Depreciação Mensal')}</DialogTitle></DialogHeader>
                    <form onSubmit={runDepreciation} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div><Label>{t('Ano')}</Label><Input value={depForm.data.year} onChange={e => depForm.setData('year', e.target.value)} /></div>
                            <div><Label>{t('Mês')}</Label><Input type="number" min={1} max={12} value={depForm.data.month} onChange={e => depForm.setData('month', e.target.value)} /></div>
                        </div>
                        <DialogFooter><Button variant="outline" type="button" onClick={() => setShowDepreciation(false)}>{t('Cancelar')}</Button><Button type="submit" disabled={depForm.processing}><Play className="h-4 w-4 mr-1" /> {t('Executar')}</Button></DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
