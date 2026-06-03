import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import AccountingSuiteNavigation from '@/components/accounting/accounting-suite-navigation';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calculator, ArrowRight, Download, Lock } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';

interface VatCode { id: number; code: string; description: string; rate: number; }
interface VatResult { by_code: Record<string, { output_tax: number; input_tax: number; net: number }>; total_output: number; total_input: number; net_payable: number; }

export default function VatMapIndex() {
    const { t } = useTranslation();
    const page = usePage<any>();
    const { vatCodes, vatResult, year, month } = page.props as { vatCodes: VatCode[]; vatResult: VatResult | null; year: number; month: number };
    const userPermissions: string[] = page.props?.auth?.user?.permissions || [];
    const canManageTax = userPermissions.includes('manage-account-reports');
    const [selYear, setSelYear] = useState(year);
    const [selMonth, setSelMonth] = useState(month);
    const [closing, setClosing] = useState(false);

    const calculate = () => router.get(route('sce.tax.vat-map'), { year: selYear, month: selMonth, calculate: 1 }, { preserveState: true });
    const exportCsv = () => window.location.assign(route('sce.tax.vat-map.export', { year: selYear, month: selMonth }));
    const formatDate = (date: Date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };
    const closeMonth = async () => {
        if (!canManageTax) {
            return;
        }

        if (!window.confirm(t('Close the selected VAT month and create a fiscal closing snapshot?'))) {
            return;
        }

        const closeReason = window.prompt(
            t('Close reason (optional)'),
            t('Fecho mensal de IVA')
        ) || '';

        const periodFrom = formatDate(new Date(selYear, selMonth - 1, 1));
        const periodTo = formatDate(new Date(selYear, selMonth, 0));

        setClosing(true);
        try {
            await axios.post(route('account.reports.fiscal-closings.close'), {
                period_from: periodFrom,
                period_to: periodTo,
                close_reason: closeReason || `Fecho mensal de IVA ${selYear}-${String(selMonth).padStart(2, '0')}`,
            });

            window.location.href = route('account.reports.fiscal-closings');
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setClosing(false);
        }
    };
    const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: new Date(2000, i).toLocaleString('pt', { month: 'long' }) }));
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Contabilidade') }, { label: t('Impostos') }, { label: t('Mapa IVA') }]} pageTitle={t('Mapa IVA Mensal')}>
            <Head title={t('Mapa IVA')} />
            <AccountingSuiteNavigation section="tax" className="mb-4" />
            <Card className="mb-6">
                <CardContent className="p-4 flex items-center gap-4">
                    <Select value={String(selMonth)} onValueChange={v => setSelMonth(Number(v))}>
                        <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                        <SelectContent>{months.map(m => <SelectItem key={m.value} value={String(m.value)}>{m.label.charAt(0).toUpperCase() + m.label.slice(1)}</SelectItem>)}</SelectContent>
                    </Select>
                    <Select value={String(selYear)} onValueChange={v => setSelYear(Number(v))}>
                        <SelectTrigger className="w-28"><SelectValue /></SelectTrigger>
                        <SelectContent>{[2024, 2025, 2026, 2027].map(y => <SelectItem key={y} value={String(y)}>{y}</SelectItem>)}</SelectContent>
                    </Select>
                    <Button variant="outline" onClick={exportCsv}><Download className="h-4 w-4 mr-2" /> {t('Exportar CSV')}</Button>
                    {canManageTax && <Button variant="outline" onClick={closeMonth} disabled={closing}><Lock className="h-4 w-4 mr-2" /> {closing ? t('Closing...') : t('Close Month')}</Button>}
                    <Button onClick={calculate}><Calculator className="h-4 w-4 mr-2" /> {t('Calcular')}</Button>
                </CardContent>
            </Card>

            {/* VAT codes table */}
            <Card className="mb-6">
                <CardHeader><CardTitle>{t('Códigos IVA')}</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50"><tr><th className="p-3 text-left">{t('Código')}</th><th className="p-3 text-left">{t('Descrição')}</th><th className="p-3 text-right">{t('Taxa')}</th></tr></thead>
                        <tbody>{vatCodes.map(v => (
                            <tr key={v.id} className="border-t hover:bg-muted/30">
                                <td className="p-3"><Badge variant="outline" className="font-mono">{v.code}</Badge></td>
                                <td className="p-3">{v.description}</td>
                                <td className="p-3 text-right font-mono">{v.rate}%</td>
                            </tr>
                        ))}</tbody>
                    </table>
                </CardContent>
            </Card>

            {vatResult && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
                        <CardContent className="p-6 text-center">
                            <p className="text-xs text-red-600 mb-1">{t('IVA Liquidado (Output)')}</p>
                            <p className="text-2xl font-bold text-red-800">{fmt(vatResult.total_output)}</p>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
                        <CardContent className="p-6 text-center">
                            <p className="text-xs text-green-600 mb-1">{t('IVA Dedutível (Input)')}</p>
                            <p className="text-2xl font-bold text-green-800">{fmt(vatResult.total_input)}</p>
                        </CardContent>
                    </Card>
                    <Card className={`bg-gradient-to-br ${vatResult.net_payable >= 0 ? 'from-orange-50 to-orange-100 border-orange-200' : 'from-blue-50 to-blue-100 border-blue-200'}`}>
                        <CardContent className="p-6 text-center">
                            <p className={`text-xs mb-1 ${vatResult.net_payable >= 0 ? 'text-orange-600' : 'text-blue-600'}`}>{vatResult.net_payable >= 0 ? t('A Pagar') : t('A Recuperar')}</p>
                            <p className={`text-2xl font-bold ${vatResult.net_payable >= 0 ? 'text-orange-800' : 'text-blue-800'}`}>{fmt(Math.abs(vatResult.net_payable))}</p>
                        </CardContent>
                    </Card>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
