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

interface AgingData {
    aging_summary: {
        current: number;
        '1_30_days': number;
        '31_60_days': number;
        '61_90_days': number;
        over_90_days: number;
        total: number;
    };
    customers: any[];
    as_of_date: string;
}

export default function InvoiceAging({ financialYear }: any) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [asOfDate, setAsOfDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<AgingData | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.invoice-aging'), {
                params: { as_of_date: asOfDate }
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

    const handleDownloadPDF = () => {
        window.open(route('account.reports.invoice-aging.print') + `?as_of_date=${asOfDate}&download=pdf`, '_blank');
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('As Of Date')}</label>
                        <DatePicker value={asOfDate} onChange={setAsOfDate} placeholder={t('Select date')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        {data && auth.user?.permissions?.includes('print-invoice-aging') && (
                            <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data && data.customers.length > 0 ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Invoice Aging Report')}</h3>
                            <p className="text-sm text-gray-600">{t('As of')} {formatDate(data.as_of_date)}</p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Customer')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Current')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">1-30 {t('Days')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">31-60 {t('Days')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">61-90 {t('Days')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">&gt;90 {t('Days')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.customers.map((customer, idx) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-4 py-3">{customer.customer_name}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer.current)}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer['1_30_days'])}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer['31_60_days'])}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer['61_90_days'])}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer.over_90_days)}</td>
                                            <td className="px-4 py-3 text-right font-semibold">{formatCurrency(customer.total)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-200 font-bold border-t-4">
                                        <td className="px-4 py-4">{t('Total')}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary.current)}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary['1_30_days'])}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary['31_60_days'])}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary['61_90_days'])}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary.over_90_days)}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.aging_summary.total)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Invoice Aging Report')}
                        description={t('No outstanding invoices found')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
