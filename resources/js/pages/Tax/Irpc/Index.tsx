import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import TaxNavigation from '@/components/tax/tax-navigation';
import { Calculator, Plus, Trash2, ArrowDown, ArrowUp } from 'lucide-react';
import { useState } from 'react';

interface Adjustment { id: number; type: string; category: string; description: string; amount: number; legal_basis: string; }
interface IrpcResult { accounting_result: number; add_backs: number; deductions: number; taxable_income: number; irpc_rate: number; irpc_due: number; ppc_total: number; net_payable: number; }

export default function IrpcIndex() {
    const { t } = useTranslation();
    const page = usePage<any>();
    const { config, adjustments, irpcResult, categories, year } = page.props as { config: any; adjustments: Adjustment[]; irpcResult: IrpcResult | null; categories: string[]; year: string };
    const userPermissions: string[] = page.props?.auth?.user?.permissions || [];
    const canManageTax = userPermissions.includes('manage-account-reports');
    const [showAdd, setShowAdd] = useState(false);

    const adjForm = useForm({ fiscal_year: year, type: 'add_back', category: '', description: '', amount: '', legal_basis: '' });
    const calculate = () => router.get(route('sce.tax.irpc'), { year, calculate: 1 }, { preserveState: true });

    const handleAddAdjustment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManageTax) return;
        adjForm.post(route('sce.tax.irpc.adjustment'), { onSuccess: () => { setShowAdd(false); adjForm.reset(); } });
    };

    const deleteAdj = (id: number) => router.delete(route('sce.tax.irpc.adjustment.destroy', id));
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);

    const addBacks = adjustments.filter(a => a.type === 'add_back');
    const deductions = adjustments.filter(a => a.type === 'deduction');

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Impostos') }, { label: 'IRPC' }]} pageTitle={`IRPC — ${t('Exercício')} ${year}`}
            pageActions={<div className="flex gap-2">
                {canManageTax && <Button size="sm" variant="outline" onClick={() => setShowAdd(true)}><Plus className="h-4 w-4 mr-1" /> {t('Correcção')}</Button>}
                <Button size="sm" onClick={calculate}><Calculator className="h-4 w-4 mr-1" /> {t('Calcular')}</Button>
            </div>}>
            <Head title="IRPC" />
            <TaxNavigation className="mb-4" />

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {/* Add-backs */}
                <Card>
                    <CardHeader className="py-3"><CardTitle className="text-sm flex items-center gap-2"><ArrowUp className="h-4 w-4 text-red-500" /> {t('Acréscimos')} ({addBacks.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {addBacks.length === 0 ? <p className="p-4 text-sm text-muted-foreground text-center">{t('Sem acréscimos')}</p> : (
                            <div className="divide-y">{addBacks.map(a => (
                                <div key={a.id} className="flex items-center gap-3 px-4 py-2">
                                    <div className="flex-1"><p className="text-sm font-medium">{a.description}</p><p className="text-xs text-muted-foreground">{a.category}</p></div>
                                    <span className="text-sm font-mono text-red-600">+{fmt(a.amount)}</span>
                                    {canManageTax && <Button variant="ghost" size="sm" className="h-6 w-6 p-0 text-destructive" onClick={() => deleteAdj(a.id)}><Trash2 className="h-3 w-3" /></Button>}
                                </div>
                            ))}</div>
                        )}
                    </CardContent>
                </Card>
                {/* Deductions */}
                <Card>
                    <CardHeader className="py-3"><CardTitle className="text-sm flex items-center gap-2"><ArrowDown className="h-4 w-4 text-green-500" /> {t('Deduções')} ({deductions.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {deductions.length === 0 ? <p className="p-4 text-sm text-muted-foreground text-center">{t('Sem deduções')}</p> : (
                            <div className="divide-y">{deductions.map(a => (
                                <div key={a.id} className="flex items-center gap-3 px-4 py-2">
                                    <div className="flex-1"><p className="text-sm font-medium">{a.description}</p><p className="text-xs text-muted-foreground">{a.category}</p></div>
                                    <span className="text-sm font-mono text-green-600">-{fmt(a.amount)}</span>
                                    {canManageTax && <Button variant="ghost" size="sm" className="h-6 w-6 p-0 text-destructive" onClick={() => deleteAdj(a.id)}><Trash2 className="h-3 w-3" /></Button>}
                                </div>
                            ))}</div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* IRPC Result */}
            {irpcResult && (
                <Card className="bg-gradient-to-br from-purple-50 to-indigo-50 border-purple-200">
                    <CardHeader><CardTitle>{t('Cálculo IRPC')} — {year}</CardTitle></CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {[
                                { label: t('Resultado Contab.'), value: irpcResult.accounting_result, color: 'text-gray-800' },
                                { label: t('Acréscimos'), value: irpcResult.add_backs, color: 'text-red-600' },
                                { label: t('Deduções'), value: -irpcResult.deductions, color: 'text-green-600' },
                                { label: t('Matéria Colectável'), value: irpcResult.taxable_income, color: 'text-purple-700' },
                            ].map((item, i) => (
                                <div key={i} className="text-center p-3 bg-white/60 rounded-lg">
                                    <p className="text-xs text-muted-foreground mb-1">{item.label}</p>
                                    <p className={`text-lg font-bold ${item.color}`}>{fmt(item.value)}</p>
                                </div>
                            ))}
                        </div>
                        <div className="mt-4 flex items-center justify-center gap-8 p-4 bg-white/80 rounded-lg">
                            <div className="text-center"><p className="text-xs text-muted-foreground">{t('IRPC')} ({irpcResult.irpc_rate}%)</p><p className="text-xl font-bold text-purple-800">{fmt(irpcResult.irpc_due)}</p></div>
                            <div className="text-center"><p className="text-xs text-muted-foreground">{t('PPC Pagos')}</p><p className="text-xl font-bold text-blue-600">-{fmt(irpcResult.ppc_total)}</p></div>
                            <div className="text-center border-l pl-8"><p className="text-xs text-muted-foreground">{irpcResult.net_payable >= 0 ? t('A Pagar') : t('A Recuperar')}</p><p className={`text-2xl font-bold ${irpcResult.net_payable >= 0 ? 'text-red-700' : 'text-green-700'}`}>{fmt(Math.abs(irpcResult.net_payable))}</p></div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Add adjustment dialog */}
            {canManageTax && (
                <Dialog open={showAdd} onOpenChange={setShowAdd}>
                    <DialogContent className="max-w-md">
                        <DialogHeader><DialogTitle>{t('Nova Correcção Fiscal')}</DialogTitle></DialogHeader>
                        <form onSubmit={handleAddAdjustment} className="space-y-4">
                            <div><Label>{t('Tipo')}</Label>
                                <Select value={adjForm.data.type} onValueChange={v => adjForm.setData('type', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent><SelectItem value="add_back">{t('Acréscimo')}</SelectItem><SelectItem value="deduction">{t('Dedução')}</SelectItem></SelectContent>
                                </Select>
                            </div>
                            <div><Label>{t('Categoria')}</Label><Input value={adjForm.data.category} onChange={e => adjForm.setData('category', e.target.value)} required placeholder="ex: Multas" /></div>
                            <div><Label>{t('Descrição')}</Label><Input value={adjForm.data.description} onChange={e => adjForm.setData('description', e.target.value)} required /></div>
                            <div><Label>{t('Montante')}</Label><Input type="number" step="0.01" value={adjForm.data.amount} onChange={e => adjForm.setData('amount', e.target.value)} required /></div>
                            <div><Label>{t('Base Legal')}</Label><Input value={adjForm.data.legal_basis} onChange={e => adjForm.setData('legal_basis', e.target.value)} placeholder="Art. X CIRPC" /></div>
                            <DialogFooter><Button variant="outline" type="button" onClick={() => setShowAdd(false)}>{t('Cancelar')}</Button><Button type="submit" disabled={adjForm.processing}>{t('Registar')}</Button></DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </AuthenticatedLayout>
    );
}
