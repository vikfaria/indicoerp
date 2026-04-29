import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Download, FileText } from 'lucide-react';
import { formatCurrency, formatDate } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface MozambiqueVatDeclarationProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    };
}

export default function MozambiqueVatDeclaration({ financialYear }: MozambiqueVatDeclarationProps) {
    const { t } = useTranslation();
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-vat-declaration'), {
                params: { from_date: fromDate, to_date: toDate },
            });
            setData(response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            className="gap-2"
                            onClick={() => window.open(route('account.reports.mozambique-vat-declaration.export') + `?from_date=${fromDate}&to_date=${toDate}`, '_blank')}
                        >
                            <Download className="h-4 w-4" />
                            {t('Export CSV')}
                        </Button>
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Mozambique VAT Declaration')}</h3>
                            <p className="text-sm text-gray-600">
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}
                            </p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <tbody>
                                    <tr className="border-t bg-blue-50 font-semibold">
                                        <td className="px-4 py-3">{t('Output VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.output_vat)}</td>
                                    </tr>
                                    <tr className="border-t bg-blue-50 font-semibold">
                                        <td className="px-4 py-3">{t('Input VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.input_vat)}</td>
                                    </tr>
                                    <tr className="border-t bg-blue-100 font-bold">
                                        <td className="px-4 py-3">{t('Net VAT Payable')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.net_vat_payable)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Sales VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.sales_vat)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Purchase VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.purchase_vat)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Credit Notes VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.credit_notes_vat)}</td>
                                    </tr>
                                    <tr className="border-t">
                                        <td className="px-4 py-3">{t('Debit Notes VAT')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.totals.debit_notes_vat)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div className="p-4 border-t">
                            <h4 className="font-semibold mb-3">{t('Monthly Breakdown')}</h4>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-gray-50">
                                            <th className="px-3 py-2 text-left">{t('Period')}</th>
                                            <th className="px-3 py-2 text-right">{t('Sales VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Purchase VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Credit Notes VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Debit Notes VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Output VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Input VAT')}</th>
                                            <th className="px-3 py-2 text-right">{t('Net VAT Payable')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.monthly.map((row: any) => (
                                            <tr key={row.period} className="border-b">
                                                <td className="px-3 py-2">{row.period}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.sales_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.purchase_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.credit_notes_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.debit_notes_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.output_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.input_vat)}</td>
                                                <td className="px-3 py-2 text-right">{formatCurrency(row.net_vat_payable)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Mozambique VAT Declaration')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
