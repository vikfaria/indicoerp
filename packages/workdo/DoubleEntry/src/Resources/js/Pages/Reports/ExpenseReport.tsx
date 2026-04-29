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

interface ExpenseItem {
    account_code: string;
    account_name: string;
    amount: number;
}

interface ExpenseReportData {
    expenses: ExpenseItem[];
    total_expenses: number;
    from_date: string;
    to_date: string;
}

interface ExpenseReportProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    }
}

export default function ExpenseReport({ financialYear }: ExpenseReportProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [data, setData] = useState<ExpenseReportData | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('double-entry.reports.expense-report'), {
                params: { from_date: fromDate, to_date: toDate }
            });
            setData(response.data);
        } catch (error) {
            console.error('Error fetching expense report:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleDownloadPDF = () => {
        const printUrl = route('double-entry.reports.expense-report.print') +
            `?from_date=${fromDate}&to_date=${toDate}&download=pdf`;
        window.open(printUrl, '_blank');
    };

    const clearFilters = () => {
        setFromDate(financialYear?.year_start_date || '');
        setToDate(financialYear?.year_end_date || '');
    };

    const getPercentage = (amount: number) => {
        if (!data || data.total_expenses === 0) return 0;
        return ((amount / data.total_expenses) * 100).toFixed(1);
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Date')}</label>
                        <DatePicker
                            value={fromDate}
                            onChange={setFromDate}
                            placeholder={t('Select from date')}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Date')}</label>
                        <DatePicker
                            value={toDate}
                            onChange={setToDate}
                            placeholder={t('Select to date')}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div className="flex items-end gap-2">
                            <Button onClick={fetchData} disabled={loading} size="sm">
                                {loading ? t('Loading...') : t('Generate')}
                            </Button>
                            <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            {data && auth.user?.permissions?.includes('print-expense-report') && (
                                <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
                                    <Printer className="h-4 w-4" />
                                    {t('Download PDF')}
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data && data.expenses.length > 0 ? (
                    <>
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">{t('Expense Report by Category')}</h3>
                            <p className="text-sm text-gray-600">
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}
                            </p>
                            <p className="text-sm font-semibold mt-2">
                                {t('Total Expenses')}: {formatCurrency(data.total_expenses)}
                            </p>
                        </div>

                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[60vh] w-full">
                            <div className="min-w-[700px]">
                                <table className="w-full">
                                    <thead className="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Rank')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Account Code')}</th>
                                            <th className="px-4 py-3 text-left text-sm font-semibold">{t('Expense Category')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('Amount')}</th>
                                            <th className="px-4 py-3 text-right text-sm font-semibold">{t('% of Total')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.expenses.map((expense, idx) => (
                                            <tr key={idx} className={`border-t hover:bg-gray-50 ${idx < 5 ? 'bg-blue-50/30' : ''}`}>
                                                <td className="px-4 py-3 text-sm font-medium">{idx + 1}</td>
                                                <td className="px-4 py-3 text-sm">{expense.account_code}</td>
                                                <td className="px-4 py-3 text-sm">{expense.account_name}</td>
                                                <td className="px-4 py-3 text-sm text-right font-semibold">
                                                    {formatCurrency(expense.amount)}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {getPercentage(expense.amount)}%
                                                </td>
                                            </tr>
                                        ))}
                                        <tr className="bg-gray-200 font-bold border-t-4 border-gray-800 sticky bottom-0">
                                            <td colSpan={3} className="px-4 py-4 text-base">{t('Total Expenses')}</td>
                                            <td className="px-4 py-4 text-base text-right">{formatCurrency(data.total_expenses)}</td>
                                            <td className="px-4 py-4 text-base text-right">100%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Expense Report')}
                        description={t('Select date range to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
