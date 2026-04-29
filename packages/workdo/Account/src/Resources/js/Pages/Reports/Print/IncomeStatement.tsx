import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function PrintIncomeStatement() {
    const { t } = useTranslation();
    const { data, filters } = usePage<any>().props;

    return (
        <>
            <Head title={t('Income Statement')} />
            <div className="p-8 max-w-5xl mx-auto">
                <div className="text-center mb-6">
                    <h1 className="text-2xl font-bold">{t('Income Statement')}</h1>
                    <p className="text-gray-600">{formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}</p>
                </div>

                <table className="w-full border-collapse">
                    <tbody>
                        <tr className="bg-gray-100">
                            <td className="px-4 py-2 font-semibold border">{t('Revenue')}</td>
                            <td className="px-4 py-2 border"></td>
                        </tr>
                        {data.revenue.map((item: any, idx: number) => (
                            <tr key={idx}>
                                <td className="px-8 py-1 border">{item.category}</td>
                                <td className="px-4 py-1 text-right border">{formatCurrency(item.amount)}</td>
                            </tr>
                        ))}
                        <tr className="font-semibold bg-gray-50">
                            <td className="px-4 py-2 border">{t('Total Revenue')}</td>
                            <td className="px-4 py-2 text-right border">{formatCurrency(data.total_revenue)}</td>
                        </tr>

                        <tr className="bg-gray-100">
                            <td className="px-4 py-2 font-semibold border">{t('Expenses')}</td>
                            <td className="px-4 py-2 border"></td>
                        </tr>
                        {data.expenses.map((item: any, idx: number) => (
                            <tr key={idx}>
                                <td className="px-8 py-1 border">{item.category}</td>
                                <td className="px-4 py-1 text-right border">{formatCurrency(item.amount)}</td>
                            </tr>
                        ))}
                        <tr className="font-semibold bg-gray-50">
                            <td className="px-4 py-2 border">{t('Total Expenses')}</td>
                            <td className="px-4 py-2 text-right border">{formatCurrency(data.total_expenses)}</td>
                        </tr>

                        <tr className="font-bold bg-gray-200">
                            <td className="px-4 py-3 text-lg border">{t('Net Income')}</td>
                            <td className="px-4 py-3 text-lg text-right border">{formatCurrency(data.net_income)}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </>
    );
}
