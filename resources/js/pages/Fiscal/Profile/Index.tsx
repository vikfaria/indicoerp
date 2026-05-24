import { Head, usePage, useForm, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Save, RefreshCw, Download, Calendar } from 'lucide-react';

interface FiscalProfile {
    nuit: string;
    fiscal_regime?: string;
    tax_regime?: string;
    accounting_framework: string;
    entity_classification?: string;
    nirf_classification?: string;
    province?: string;
    economic_activity_code?: string;
    activity_code?: string;
}
interface Period { id: number; fiscal_year: string; period_number: number; period_name: string; status: string; start_date: string; end_date: string; }

export default function FiscalProfileIndex() {
    const { t } = useTranslation();
    const { profile, periods } = usePage<{ profile: FiscalProfile; periods: Period[] }>().props;

    const form = useForm({
        nuit: profile.nuit || '',
        fiscal_regime: profile.fiscal_regime || profile.tax_regime || 'normal',
        accounting_framework: profile.accounting_framework || 'pgc_nirf',
        entity_classification: profile.entity_classification || profile.nirf_classification || 'small',
        province: profile.province || '',
        economic_activity_code: profile.economic_activity_code || profile.activity_code || '',
    });

    const handleSave = (e: React.FormEvent) => { e.preventDefault(); form.post(route('sce.fiscal.update-profile')); };
    const generatePeriods = () => router.post(route('sce.fiscal.generate-periods'), { year: new Date().getFullYear() });

    const saftForm = useForm({ start_date: `${new Date().getFullYear()}-01-01`, end_date: `${new Date().getFullYear()}-12-31` });
    const exportSaft = (e: React.FormEvent) => { e.preventDefault(); window.location.href = route('sce.fiscal.saft-export') + `?start_date=${saftForm.data.start_date}&end_date=${saftForm.data.end_date}`; };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Fiscal') }, { label: t('Perfil Fiscal') }]} pageTitle={t('Perfil Fiscal & Períodos')}>
            <Head title={t('Perfil Fiscal')} />
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Profile */}
                <Card>
                    <CardHeader><CardTitle className="flex items-center gap-2"><Save className="h-5 w-5" /> {t('Dados Fiscais')}</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSave} className="space-y-4">
                            <div><Label>{t('NUIT')}</Label><Input value={form.data.nuit} onChange={e => form.setData('nuit', e.target.value)} maxLength={9} placeholder="123456789" required /></div>
                            <div><Label>{t('Regime Fiscal')}</Label>
                                <Select value={form.data.fiscal_regime} onValueChange={v => form.setData('fiscal_regime', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="normal">{t('Normal')}</SelectItem>
                                        <SelectItem value="simplified">{t('Simplificado')}</SelectItem>
                                        <SelectItem value="exempt">{t('Isento')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><Label>{t('Classificação da Entidade')}</Label>
                                <Select value={form.data.entity_classification} onValueChange={v => form.setData('entity_classification', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="large">{t('Grande')}</SelectItem>
                                        <SelectItem value="medium">{t('Média')}</SelectItem>
                                        <SelectItem value="small">{t('Pequena')}</SelectItem>
                                        <SelectItem value="micro">{t('Demais')}</SelectItem>
                                        <SelectItem value="ispc">{t('ISPC')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><Label>{t('Referencial Contabilístico')}</Label>
                                <Select value={form.data.accounting_framework} onValueChange={v => form.setData('accounting_framework', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pgc_nirf">{t('PGC-NIRF (Geral)')}</SelectItem>
                                        <SelectItem value="pgc_pe">{t('PGC-PE (Pequenas Empresas)')}</SelectItem>
                                        <SelectItem value="ispc">{t('Regime ISPC')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><Label>{t('Província')}</Label><Input value={form.data.province} onChange={e => form.setData('province', e.target.value)} placeholder="Maputo" /></div>
                            <div><Label>{t('Código Actividade (CAE)')}</Label><Input value={form.data.economic_activity_code} onChange={e => form.setData('economic_activity_code', e.target.value)} /></div>
                            <Button type="submit" disabled={form.processing} className="w-full"><Save className="h-4 w-4 mr-2" /> {t('Guardar')}</Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {/* Periods */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2"><Calendar className="h-5 w-5" /> {t('Períodos Contabilísticos')}</CardTitle>
                            <Button size="sm" variant="outline" onClick={generatePeriods}><RefreshCw className="h-4 w-4 mr-1" /> {t('Gerar')}</Button>
                        </CardHeader>
                        <CardContent>
                            {periods.length === 0 ? (
                                <p className="text-muted-foreground text-center py-8">{t('Sem períodos. Clique em Gerar.')}</p>
                            ) : (
                                <div className="space-y-2 max-h-[300px] overflow-y-auto">
                                    {periods.map(p => (
                                        <div key={p.id} className="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-muted/50">
                                            <span className="text-sm font-medium">{p.period_name || `Período ${p.period_number}`}</span>
                                            <Badge className={p.status === 'closed' ? 'bg-gray-100 text-gray-600 border-0' : p.status === 'closing' ? 'bg-yellow-100 text-yellow-700 border-0' : 'bg-green-100 text-green-700 border-0'}>{p.status}</Badge>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* SAF-T Export */}
                    <Card className="bg-gradient-to-br from-indigo-50 to-blue-50 border-indigo-200">
                        <CardHeader><CardTitle className="flex items-center gap-2"><Download className="h-5 w-5 text-indigo-600" /> {t('Exportar SAF-T MZ')}</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={exportSaft} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div><Label>{t('Data Início')}</Label><Input type="date" value={saftForm.data.start_date} onChange={e => saftForm.setData('start_date', e.target.value)} /></div>
                                    <div><Label>{t('Data Fim')}</Label><Input type="date" value={saftForm.data.end_date} onChange={e => saftForm.setData('end_date', e.target.value)} /></div>
                                </div>
                                <Button type="submit" className="w-full bg-indigo-600 hover:bg-indigo-700"><Download className="h-4 w-4 mr-2" /> {t('Descarregar XML')}</Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
