import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Printer, FileText } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface IncomeStatementData {
    revenue: { category: string; amount: number }[];
    expenses: { category: string; amount: number }[];
    total_revenue: number;
    total_expenses: number;
    net_income: number;
    from_date: string;
    to_date: string;
}

export default function IncomeStatement({ financialYear }: any) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<IncomeStatementData | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.income-statement'), {
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

    const handleDownloadPDF = () => {
        window.open(route('account.reports.income-statement.print') + `?from_date=${fromDate}&to_date=${toDate}&download=pdf`, '_blank');
    };

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
                        {data && (
                            <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
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
                            <h3 className="font-semibold text-lg">{t('Income Statement')}</h3>
                            <p className="text-sm text-gray-600">
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}
                            </p>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh]">
                            <table className="w-full">
                                <tbody>
                                    <tr className="bg-blue-50">
                                        <td className="px-4 py-3 font-semibold">{t('Revenue')}</td>
                                        <td className="px-4 py-3 text-right"></td>
                                    </tr>
                                    {data.revenue.map((item, idx) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-8 py-2">{item.category}</td>
                                            <td className="px-4 py-2 text-right">{formatCurrency(item.amount)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-100 font-semibold border-t-2">
                                        <td className="px-4 py-3">{t('Total Revenue')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.total_revenue)}</td>
                                    </tr>

                                    <tr className="bg-blue-50">
                                        <td className="px-4 py-3 font-semibold">{t('Expenses')}</td>
                                        <td className="px-4 py-3 text-right"></td>
                                    </tr>
                                    {data.expenses.map((item, idx) => (
                                        <tr key={idx} className="border-t hover:bg-gray-50">
                                            <td className="px-8 py-2">{item.category}</td>
                                            <td className="px-4 py-2 text-right">{formatCurrency(item.amount)}</td>
                                        </tr>
                                    ))}
                                    <tr className="bg-gray-100 font-semibold border-t-2">
                                        <td className="px-4 py-3">{t('Total Expenses')}</td>
                                        <td className="px-4 py-3 text-right">{formatCurrency(data.total_expenses)}</td>
                                    </tr>

                                    <tr className={`font-bold border-t-4 ${data.net_income >= 0 ? 'bg-green-50' : 'bg-red-50'}`}>
                                        <td className="px-4 py-4 text-lg">{t('Net Income')}</td>
                                        <td className="px-4 py-4 text-lg text-right">{formatCurrency(data.net_income)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Income Statement')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
