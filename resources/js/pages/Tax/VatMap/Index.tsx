import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaxNavigation from '@/components/tax/tax-navigation';
import { Calculator, ArrowRight } from 'lucide-react';
import { useState } from 'react';

interface VatCode { id: number; code: string; description: string; rate: number; }
interface VatResult { by_code: Record<string, { output_tax: number; input_tax: number; net: number }>; total_output: number; total_input: number; net_payable: number; }

export default function VatMapIndex() {
    const { t } = useTranslation();
    const { vatCodes, vatResult, year, month } = usePage<{ vatCodes: VatCode[]; vatResult: VatResult | null; year: number; month: number }>().props;
    const [selYear, setSelYear] = useState(year);
    const [selMonth, setSelMonth] = useState(month);

    const calculate = () => router.get(route('sce.tax.vat-map'), { year: selYear, month: selMonth, calculate: 1 }, { preserveState: true });
    const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: new Date(2000, i).toLocaleString('pt', { month: 'long' }) }));
    const fmt = (n: number) => new Intl.NumberFormat('pt-MZ', { style: 'currency', currency: 'MZN' }).format(n);

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Impostos') }, { label: t('Mapa IVA') }]} pageTitle={t('Mapa IVA Mensal')}>
            <Head title={t('Mapa IVA')} />
            <TaxNavigation className="mb-4" />
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
