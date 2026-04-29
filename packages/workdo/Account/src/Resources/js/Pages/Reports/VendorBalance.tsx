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

export default function VendorBalance({ financialYear }: any) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [asOfDate, setAsOfDate] = useState(financialYear?.year_end_date || '');
    const [showZeroBalances, setShowZeroBalances] = useState(false);
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.vendor-balance'), {
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
                        {data && auth.user?.permissions?.includes('print-vendor-balance') && (
                            <Button variant="outline" size="sm" onClick={() => window.open(route('account.reports.vendor-balance.print') + `?as_of_date=${asOfDate}&show_zero_balances=${showZeroBalances}&download=pdf`, '_blank')} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data && data.vendors.length > 0 ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Vendor Balance Summary')}</h3>
                            <p className="text-sm text-gray-600">{t('As of')} {formatDate(data.as_of_date)}</p>
                            <p className="text-sm font-semibold mt-2">{t('Total Outstanding')}: {formatCurrency(data.total_balance)}</p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Vendor')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Email')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Billed')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Returns & Debit Notes')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Total Paid')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Balance')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.vendors.map((vendor: any, idx: number) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                {auth.user?.permissions?.includes('view-vendor-detail-report') ? (
                                                    <Link href={route('account.reports.vendor-detail', vendor.vendor_id)} className="text-blue-600 hover:text-blue-700">
                                                        {vendor.vendor_name}
                                                    </Link>
                                                ) : (
                                                    vendor.vendor_name
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{vendor.vendor_email}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(vendor.total_billed)}</td>
                                            <td className="px-4 py-3 text-right text-red-600">{formatCurrency(vendor.total_returns)}</td>
                                            <td className="px-4 py-3 text-right">{formatCurrency(vendor.total_paid)}</td>
                                            <td className="px-4 py-3 text-right font-semibold">{formatCurrency(vendor.balance)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-200 font-bold border-t-4">
                                        <td colSpan={2} className="px-4 py-4">{t('Total')}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.vendors.reduce((sum: number, v: any) => sum + v.total_billed, 0))}</td>
                                        <td className="px-4 py-4 text-right text-red-600">{formatCurrency(data.vendors.reduce((sum: number, v: any) => sum + v.total_returns, 0))}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.vendors.reduce((sum: number, v: any) => sum + v.total_paid, 0))}</td>
                                        <td className="px-4 py-4 text-right">{formatCurrency(data.total_balance)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Vendor Balance Summary')}
                        description={t('No vendor balances found')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
