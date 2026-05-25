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
import { DataTable } from '@/components/ui/data-table';
import { Pagination } from '@/components/ui/pagination';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Plus, Receipt } from 'lucide-react';
import { useState } from 'react';

interface Rule { id: number; code: string; description: string; rate: number; }
interface Transaction { id: number; rule: Rule; gross_amount: number; tax_withheld: number; net_amount: number; transaction_date: string; vendor_name: string; document_reference: string; }

export default function WithholdingIndex() {
    const { t } = useTranslation();
    const page = usePage<any>();
    const { rules, transactions, year, month } = page.props as { rules: Rule[]; transactions: any; year: string; month: number };
    const userPermissions: string[] = page.props?.auth?.user?.permissions || [];
    const canManageTax = userPermissions.includes('manage-account-reports');
    const [showAdd, setShowAdd] = useState(false);

    const form = useForm({ rule_code: '', gross_amount: '', transaction_date: new Date().toISOString().split('T')[0], vendor_name: '', vendor_nuit: '', document_reference: '' });
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);

    const handleStore = (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManageTax) return;
        form.post(route('sce.tax.withholding.store'), { onSuccess: () => { setShowAdd(false); form.reset(); } });
    };

    const columns = [
        { key: 'transaction_date', header: t('Data'), render: (v: string) => new Date(v).toLocaleDateString('pt') },
        { key: 'vendor_name', header: t('Fornecedor'), render: (v: string) => v || '-' },
        { key: 'rule', header: t('Regra'), render: (v: Rule) => v ? <Badge variant="outline" className="font-mono">{v.code} ({v.rate}%)</Badge> : '-' },
        { key: 'gross_amount', header: t('Valor Bruto'), render: (v: number) => <span className="font-mono">{fmt(v)}</span> },
        { key: 'tax_withheld', header: t('Retenção'), render: (v: number) => <span className="font-mono text-red-600">{fmt(v)}</span> },
        { key: 'net_amount', header: t('Valor Líquido'), render: (v: number) => <span className="font-mono text-green-600">{fmt(v)}</span> },
        { key: 'document_reference', header: t('Referência'), render: (v: string) => v || '-' },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Impostos') }, { label: t('Retenções na Fonte') }]} pageTitle={t('Retenções na Fonte')}
            pageActions={canManageTax ? <Button size="sm" onClick={() => setShowAdd(true)}><Plus className="h-4 w-4 mr-1" /> {t('Nova Retenção')}</Button> : undefined}>
            <Head title={t('Retenções na Fonte')} />
            <AccountingSuiteNavigation section="tax" className="mb-4" />

            {/* Rules summary */}
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-6">
                {rules.slice(0, 5).map(r => (
                    <Card key={r.id} className="hover:shadow-md transition-shadow">
                        <CardContent className="p-3 text-center">
                            <Badge className="bg-purple-100 text-purple-700 border-0 mb-1">{r.code}</Badge>
                            <p className="text-[10px] text-muted-foreground truncate">{r.description}</p>
                            <p className="text-lg font-bold text-purple-800">{r.rate}%</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Transactions table */}
            <Card>
                <CardContent className="p-0">
                    <DataTable data={transactions.data || []} columns={columns} emptyState={
                        <div className="py-12 text-center">
                            <Receipt className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-semibold">{t('Sem retenções registadas')}</h3>
                        </div>
                    } />
                </CardContent>
                {transactions.meta && (
                    <CardContent className="px-4 py-2 border-t">
                        <Pagination data={{...transactions, ...transactions.meta}} routeName="sce.tax.withholding" filters={{ year, month }} />
                    </CardContent>
                )}
            </Card>

            {/* Add dialog */}
            {canManageTax && (
                <Dialog open={showAdd} onOpenChange={setShowAdd}>
                    <DialogContent className="max-w-md">
                        <DialogHeader><DialogTitle>{t('Nova Retenção na Fonte')}</DialogTitle></DialogHeader>
                        <form onSubmit={handleStore} className="space-y-4">
                            <div><Label>{t('Regra')}</Label>
                                <Select value={form.data.rule_code} onValueChange={v => form.setData('rule_code', v)}>
                                    <SelectTrigger><SelectValue placeholder={t('Seleccionar regra')} /></SelectTrigger>
                                    <SelectContent>{rules.map(r => <SelectItem key={r.id} value={r.code}>{r.code} — {r.description} ({r.rate}%)</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div><Label>{t('Valor Bruto')}</Label><Input type="number" step="0.01" value={form.data.gross_amount} onChange={e => form.setData('gross_amount', e.target.value)} required /></div>
                            <div><Label>{t('Data')}</Label><Input type="date" value={form.data.transaction_date} onChange={e => form.setData('transaction_date', e.target.value)} required /></div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><Label>{t('Fornecedor')}</Label><Input value={form.data.vendor_name} onChange={e => form.setData('vendor_name', e.target.value)} /></div>
                                <div><Label>{t('NUIT')}</Label><Input value={form.data.vendor_nuit} onChange={e => form.setData('vendor_nuit', e.target.value)} maxLength={9} /></div>
                            </div>
                            <div><Label>{t('Referência Documento')}</Label><Input value={form.data.document_reference} onChange={e => form.setData('document_reference', e.target.value)} /></div>
                            <DialogFooter><Button variant="outline" type="button" onClick={() => setShowAdd(false)}>{t('Cancelar')}</Button><Button type="submit" disabled={form.processing}>{t('Registar')}</Button></DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </AuthenticatedLayout>
    );
}
