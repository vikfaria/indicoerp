import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from '@/components/ui/label';
import { FileText, Search, Printer } from "lucide-react";
import { DatePicker } from '@/components/ui/date-picker';
import { formatDate, formatCurrency } from '@/utils/helpers';

interface Account {
    id: number;
    account_code: string;
    account_name: string;
    balance: number;
}

interface ProfitLossData {
    revenue: Account[];
    expenses: Account[];
    total_revenue: number;
    total_expenses: number;
    net_profit: number;
    from_date: string;
    to_date: string;
}

interface ProfitLossProps {
    profitLoss: ProfitLossData;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { profitLoss, auth } = usePage<ProfitLossProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [fromDate, setFromDate] = useState(urlParams.get('from_date') || profitLoss.from_date);
    const [toDate, setToDate] = useState(urlParams.get('to_date') || profitLoss.to_date);


    const handleGenerate = () => {
        if (!fromDate || !toDate) return;
        router.get(route('double-entry.profit-loss.index'), {
            from_date: fromDate,
            to_date: toDate
        }, {
            preserveState: true,
            replace: true
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Double Entry')},
                {label: t('Profit & Loss')}
            ]}
            pageTitle={t('Profit & Loss Statement')}
        >
            <Head title={t('Profit & Loss')} />

            <div className="max-w-7xl mx-auto space-y-6">
                <Card className="shadow-lg border-0 bg-gradient-to-r from-white to-gray-50">
                    <CardHeader className="pb-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-50 rounded-lg border flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <CardTitle className="text-xl">{t('Profit & Loss Statement')}</CardTitle>
                                    <p className="text-sm text-gray-600">
                                        {formatDate(profitLoss.from_date)} - {formatDate(profitLoss.to_date)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-end gap-3">
                                <div>
                                    <Label className="text-xs">{t('From Date')}</Label>
                                    <DatePicker
                                        value={fromDate}
                                        onChange={(value) => setFromDate(value)}
                                        placeholder={t('Select from date')}
                                    />
                                </div>
                                <div>
                                    <Label className="text-xs">{t('To Date')}</Label>
                                    <DatePicker
                                        value={toDate}
                                        onChange={(value) => setToDate(value)}
                                        placeholder={t('Select to date')}
                                    />
                                </div>
                                <Button onClick={handleGenerate} disabled={!fromDate || !toDate} size="sm">
                                    <Search className="h-4 w-4 mr-2" />
                                    {t('Generate')}
                                </Button>
                                {auth.user?.permissions?.includes('print-profit-loss') && (
                                    <Button variant="outline" size="sm" onClick={() => {
                                        const printUrl = route('double-entry.profit-loss.print') + `?from_date=${fromDate}&to_date=${toDate}&download=pdf`;
                                        window.open(printUrl, '_blank');
                                    }}>
                                        <Printer className="h-4 w-4 mr-2" />
                                        {t('Download PDF')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 shadow-sm hover:shadow-md transition-shadow">
                                <h4 className="font-semibold text-green-700 mb-2">{t('Total Revenue')}</h4>
                                <p className="text-3xl font-bold text-green-900 tabular-nums">
                                    {formatCurrency(profitLoss.total_revenue)}
                                </p>
                            </div>
                            <div className="text-center p-6 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200 shadow-sm hover:shadow-md transition-shadow">
                                <h4 className="font-semibold text-red-700 mb-2">{t('Total Expenses')}</h4>
                                <p className="text-3xl font-bold text-red-900 tabular-nums">
                                    {formatCurrency(profitLoss.total_expenses)}
                                </p>
                            </div>
                            <div className={`text-center p-6 rounded-xl border shadow-sm hover:shadow-md transition-shadow ${
                                profitLoss.net_profit >= 0
                                    ? 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200'
                                    : 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200'
                            }`}>
                                <h4 className={`font-semibold mb-2 ${profitLoss.net_profit >= 0 ? 'text-blue-700' : 'text-orange-700'}`}>
                                    {profitLoss.net_profit >= 0 ? t('Net Profit') : t('Net Loss')}
                                </h4>
                                <p className={`text-3xl font-bold tabular-nums ${profitLoss.net_profit >= 0 ? 'text-blue-900' : 'text-orange-900'}`}>
                                    {formatCurrency(Math.abs(profitLoss.net_profit))}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-lg border-0">
                    <CardContent className="p-8">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <h3 className="text-lg font-bold text-gray-800 mb-3">{t('Revenue')}</h3>
                                <div className="space-y-1">
                                    {profitLoss.revenue.length > 0 ? (
                                        profitLoss.revenue.map((account) => (
                                            <div key={account.id} className="flex justify-between items-center py-1.5 border-b border-gray-100">
                                                <p className="font-medium text-sm">
                                                    <span className="text-green-600">{account.account_code}</span> - {account.account_name}
                                                </p>
                                                <p className="font-semibold tabular-nums text-sm">{formatCurrency(account.balance)}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-muted-foreground py-4">{t('No revenue accounts')}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-bold text-gray-800 mb-3">{t('Expenses')}</h3>
                                <div className="space-y-1">
                                    {profitLoss.expenses.length > 0 ? (
                                        profitLoss.expenses.map((account) => (
                                            <div key={account.id} className="flex justify-between items-center py-1.5 border-b border-gray-100">
                                                <p className="font-medium text-sm">
                                                    <span className="text-green-600">{account.account_code}</span> - {account.account_name}
                                                </p>
                                                <p className="font-semibold tabular-nums text-sm">{formatCurrency(account.balance)}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-muted-foreground py-4">{t('No expense accounts')}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-3">
                            <div className="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                                <p className="font-bold">{t('Total Revenue')}</p>
                                <p className="font-bold tabular-nums">{formatCurrency(profitLoss.total_revenue)}</p>
                            </div>
                            <div className="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                                <p className="font-bold">{t('Total Expenses')}</p>
                                <p className="font-bold tabular-nums">{formatCurrency(profitLoss.total_expenses)}</p>
                            </div>
                        </div>

                        <div className="mt-8 pt-6 border-t-2 border-gray-400">
                            <div className="flex justify-between items-center">
                                <h3 className="text-base font-bold text-gray-900">
                                    {profitLoss.net_profit >= 0 ? t('Net Profit') : t('Net Loss')}
                                </h3>
                                <p className={`text-base font-bold tabular-nums ${profitLoss.net_profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {formatCurrency(Math.abs(profitLoss.net_profit))}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
