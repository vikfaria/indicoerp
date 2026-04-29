import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Printer, FileText } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

export default function TaxSummary({ financialYear }: any) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.tax-summary'), {
                params: { from_date: fromDate, to_date: toDate }
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
                        {data && auth.user?.permissions?.includes('print-tax-summary') && (
                            <Button variant="outline" size="sm" onClick={() => window.open(route('account.reports.tax-summary.print') + `?from_date=${fromDate}&to_date=${toDate}&download=pdf`, '_blank')} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Tax Summary Report')}</h3>
                            <p className="text-sm text-gray-600">
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}
                            </p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <tbody>
                                    <tr className="bg-green-50">
                                        <td className="px-4 py-3 font-semibold">{t('Tax Collected (Sales)')}</td>
                                        <td className="px-4 py-3 text-right"></td>
                                    </tr>
                                    {data.tax_collected.items.map((item: any, idx: number) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-8 py-2">{item.tax_name}</td>
                                            <td className="px-4 py-2 text-right">{formatCurrency(item.amount)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-100 font-semibold border-t-2">
                                        <td className="px-4 py-3">{t('Total Tax Collected')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.tax_collected.total)}</td>
                                    </tr>

                                    <tr className="bg-red-50">
                                        <td className="px-4 py-3 font-semibold">{t('Tax Paid (Purchases)')}</td>
                                        <td className="px-4 py-3 text-right"></td>
                                    </tr>
                                    {data.tax_paid.items.map((item: any, idx: number) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-8 py-2">{item.tax_name}</td>
                                            <td className="px-4 py-2 text-right">{formatCurrency(item.amount)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-100 font-semibold border-t-2">
                                        <td className="px-4 py-3">{t('Total Tax Paid')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.tax_paid.total)}</td>
                                    </tr>

                                    <tr className={`font-bold border-t-4 ${data.net_tax_liability >= 0 ? 'bg-blue-50' : 'bg-yellow-50'}`}>
                                        <td className="px-4 py-4 text-lg">{t('Net Tax Liability')}</td>
                                        <td className="px-4 py-4 text-lg text-right">{formatCurrency(data.net_tax_liability)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Tax Summary Report')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
