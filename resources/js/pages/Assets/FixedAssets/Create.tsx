import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Save } from 'lucide-react';

export default function CreateFixedAsset() {
    const { t } = useTranslation();
    const form = useForm({
        asset_code: '', name: '', description: '', category: 'tangible', sub_category: '',
        acquisition_date: new Date().toISOString().split('T')[0], acquisition_cost: '',
        residual_value: '0', useful_life_months: '60', depreciation_method: 'straight_line',
        location: '', responsible_person: '', serial_number: '', supplier: '', invoice_reference: '',
    });

    const handleSubmit = (e: React.FormEvent) => { e.preventDefault(); form.post(route('sce.fixed-assets.store')); };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Activos') }, { label: t('Activos Fixos'), url: route('sce.fixed-assets.index') }, { label: t('Novo') }]}
            pageTitle={t('Registar Activo Fixo')} backUrl={route('sce.fixed-assets.index')}>
            <Head title={t('Novo Activo Fixo')} />
            <AccountingSuiteNavigation section="assets" className="mb-4" />
            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader><CardTitle>{t('Identificação')}</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div><Label>{t('Código')}</Label><Input value={form.data.asset_code} onChange={e => form.setData('asset_code', e.target.value)} required placeholder="AF-001" /></div>
                                <div><Label>{t('Categoria')}</Label>
                                    <Select value={form.data.category} onValueChange={v => form.setData('category', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="tangible">{t('Tangível')}</SelectItem>
                                            <SelectItem value="intangible">{t('Intangível')}</SelectItem>
                                            <SelectItem value="investment_property">{t('Prop. Investimento')}</SelectItem>
                                            <SelectItem value="biological">{t('Biológico')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div><Label>{t('Nome')}</Label><Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></div>
                            <div><Label>{t('Descrição')}</Label><Input value={form.data.description} onChange={e => form.setData('description', e.target.value)} /></div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><Label>{t('Localização')}</Label><Input value={form.data.location} onChange={e => form.setData('location', e.target.value)} /></div>
                                <div><Label>{t('Responsável')}</Label><Input value={form.data.responsible_person} onChange={e => form.setData('responsible_person', e.target.value)} /></div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>{t('Valor & Depreciação')}</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div><Label>{t('Data Aquisição')}</Label><Input type="date" value={form.data.acquisition_date} onChange={e => form.setData('acquisition_date', e.target.value)} required /></div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><Label>{t('Custo Aquisição (MZN)')}</Label><Input type="number" step="0.01" value={form.data.acquisition_cost} onChange={e => form.setData('acquisition_cost', e.target.value)} required /></div>
                                <div><Label>{t('Valor Residual (MZN)')}</Label><Input type="number" step="0.01" value={form.data.residual_value} onChange={e => form.setData('residual_value', e.target.value)} /></div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><Label>{t('Vida Útil (meses)')}</Label><Input type="number" value={form.data.useful_life_months} onChange={e => form.setData('useful_life_months', e.target.value)} required /></div>
                                <div><Label>{t('Método Depreciação')}</Label>
                                    <Select value={form.data.depreciation_method} onValueChange={v => form.setData('depreciation_method', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="straight_line">{t('Linha Recta')}</SelectItem>
                                            <SelectItem value="declining_balance">{t('Quotas Degressivas')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <div><Label>{t('Nº Série')}</Label><Input value={form.data.serial_number} onChange={e => form.setData('serial_number', e.target.value)} /></div>
                                <div><Label>{t('Fornecedor')}</Label><Input value={form.data.supplier} onChange={e => form.setData('supplier', e.target.value)} /></div>
                                <div><Label>{t('Ref. Factura')}</Label><Input value={form.data.invoice_reference} onChange={e => form.setData('invoice_reference', e.target.value)} /></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <div className="mt-6 flex justify-end">
                    <Button type="submit" size="lg" disabled={form.processing}><Save className="h-5 w-5 mr-2" /> {t('Registar Activo')}</Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
