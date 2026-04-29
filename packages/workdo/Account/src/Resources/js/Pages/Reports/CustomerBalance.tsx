import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Checkbox } from '@/components/ui/checkbox';
import { Printer, FileText } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

export default function CustomerBalance({ financialYear }: any) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [asOfDate, setAsOfDate] = useState(financialYear?.year_end_date || '');
    const [showZeroBalances, setShowZeroBalances] = useState(false);
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.customer-balance'), {
                params: { as_of_date: asOfDate, show_zero_balances: showZeroBalances }
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
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('As Of Date')}</label>
                        <DatePicker value={asOfDate} onChange={setAsOfDate} placeholder={t('Select date')} />
                    </div>
                    <div className="flex items-end">
                        <label className="flex items-center gap-2 cursor-pointer mb-2">
                            <Checkbox checked={showZeroBalances} onCheckedChange={setShowZeroBalances} />
                            <span className="text-sm">{t('Show Zero Balances')}</span>
                        </label>
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        {data && auth.user?.permissions?.includes('print-customer-balance') && (
                            <Button variant="outline" size="sm" onClick={() => window.open(route('account.reports.customer-balance.print') + `?as_of_date=${asOfDate}&show_zero_balances=${showZeroBalances}&download=pdf`, '_blank')} className="gap-2">
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
                            <h3 className="font-semibold text-lg">{t('Customer Balance Summary')}</h3>
                            <p className="text-sm text-gray-600">{t('As of')} {formatDate(data.as_of_date)}</p>
                            <p className="text-sm font-semibold mt-2">{t('Total Outstanding')}: {formatCurrency(data.total_balance)}</p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Customer')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Email')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Invoiced')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Returns & Credit Notes')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Paid')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Balance')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.customers.map((customer: any, idx: number) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                {auth.user?.permissions?.includes('view-customer-detail-report') ? (
                                                    <Link href={route('account.reports.customer-detail', customer.customer_id)} className="text-blue-600 hover:text-blue-700">
                                                        {customer.customer_name}
                                                    </Link>
                                                ) : (
                                                    customer.customer_name
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{customer.customer_email}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer.total_invoiced)}</td>
                                            <td className="px-4 py-3 text-right text-red-600">{formatCurrency(customer.total_returns)}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(customer.total_paid)}</td>
                                            <td className="px-4 py-3 text-right font-semibold">{formatCurrency(customer.balance)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-200 font-bold border-t-4">
                                        <td colSpan={2} className="px-4 py-4">{t('Total')}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.customers.reduce((sum: number, c: any) => sum + c.total_invoiced, 0))}</td>
                                        <td className="px-4 py-4 text-right text-red-600">{formatCurrency(data.customers.reduce((sum: number, c: any) => sum + c.total_returns, 0))}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.customers.reduce((sum: number, c: any) => sum + c.total_paid, 0))}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.total_balance)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Customer Balance Summary')}
                        description={t('No customer balances found')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
