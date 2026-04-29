import { Head, usePage, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ArrowLeft, CreditCard, TrendingUp, TrendingDown, Calendar, FileText, Hash, Building2 } from "lucide-react";
import { formatCurrency, formatDate } from '@/utils/helpers';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";

interface ChartOfAccount {
    id: number;
    account_code: string;
    account_name: string;
    level: number;
    normal_balance: string;
    opening_balance: number;
    current_balance: number;
    is_active: boolean;
    description?: string;
    account_type?: { name: string };
    parent_account?: { account_name: string };
}

interface JournalEntry {
    id: number;
    journal_number: string;
    journal_date: string;
    description: string;
    entry_type: string;
}

interface JournalEntryItem {
    id: number;
    description: string;
    debit_amount: number;
    credit_amount: number;
    created_at: string;
    journal_entry: JournalEntry;
}

interface PaginatedHistory {
    data: JournalEntryItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface ShowProps {
    chartofaccount: ChartOfAccount;
    history: PaginatedHistory;
    calculatedBalance: number;
    totalDebits: number;
    totalCredits: number;
}

export default function Show() {
    const { t } = useTranslation();
    const { chartofaccount, history, calculatedBalance, totalDebits, totalCredits } = usePage<ShowProps>().props;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Accounting'), url:route('account.index')},
                {label: t('Chart Of Accounts'), url: route('account.chart-of-accounts.index')},
                {label: t('View')}
            ]}
            pageTitle={t('View Chart Of Account')}
            backUrl={route('account.chart-of-accounts.index')}
        >
            <Head title={t('View Chart Of Account')} />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Account Summary Cards */}
                <div className="lg:col-span-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <div className="p-2 bg-blue-100 rounded-lg">
                                        <CreditCard className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-lg font-medium">{chartofaccount.account_name}</CardTitle>
                                        <p className="text-sm text-gray-500 flex items-center mt-1">
                                            <Hash className="h-4 w-4 mr-1" />
                                            {chartofaccount.account_code}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant={chartofaccount.is_active ? 'outline' : 'outline'} className={chartofaccount.is_active ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'}>
                                    {chartofaccount.is_active ? t('Active') : t('Inactive')}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <Building2 className="h-5 w-5 text-gray-600" />
                                    <div>
                                        <p className="font-semibold">{t('Account Type')}</p>
                                        <p className="text-sm font-medium text-gray-600">{chartofaccount.account_type?.name || '-'}</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <FileText className="h-5 w-5 text-gray-600" />
                                    <div>
                                        <p className="font-semibold">{t('Parent Account')}</p>
                                        <p className="text-sm font-medium text-gray-600">{chartofaccount.parent_account?.account_name || t('None')}</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div className={`h-5 w-5 rounded-full flex items-center justify-center text-xs font-bold ${
                                        chartofaccount.normal_balance === 'debit' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'
                                    }`}>
                                        {chartofaccount.normal_balance === 'debit' ? 'DR' : 'CR'}
                                    </div>
                                    <div>
                                        <p className="font-semibold">{t('Normal Balance')}</p>
                                        <p className="text-sm font-medium text-gray-600">{chartofaccount.normal_balance === 'debit' ? t('Debit') : t('Credit')}</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div className="h-5 w-5 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold">
                                        {chartofaccount.level}
                                    </div>
                                    <div>
                                        <p className="font-semibold">{t('Level')}</p>
                                        <p className="text-sm font-medium text-gray-600">{t('Level')} {chartofaccount.level}</p>
                                    </div>
                                </div>
                            </div>
                            {chartofaccount.description && (
                                <div className="mt-4 p-3 bg-blue-50 rounded-lg">
                                    <p className="font-semibold text-blue-900 mb-1">{t('Description')}</p>
                                    <p className="text-sm text-blue-800">{chartofaccount.description}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Balance Cards */}
                <div className="space-y-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Opening Balance')}</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {chartofaccount.opening_balance ? formatCurrency(chartofaccount.opening_balance) : formatCurrency(0)}
                                    </p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <TrendingUp className="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Current Balance')}</p>
                                    <p className={`text-2xl font-bold ${
                                        chartofaccount.current_balance >= 0 ? 'text-green-600' : 'text-red-600'
                                    }`}>
                                        {formatCurrency(chartofaccount.current_balance || 0)}
                                    </p>
                                    <p className="text-xs text-gray-500">{t('Stored')}</p>
                                </div>
                                <div className={`p-3 rounded-full ${
                                    chartofaccount.current_balance >= 0 ? 'bg-green-100' : 'bg-red-100'
                                }`}>
                                    {chartofaccount.current_balance >= 0 ?
                                        <TrendingUp className="h-6 w-6 text-green-600" /> :
                                        <TrendingDown className="h-6 w-6 text-red-600" />
                                    }
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Calculated Balance')}</p>
                                    <p className={`text-2xl font-bold ${
                                        calculatedBalance >= 0 ? 'text-blue-600' : 'text-orange-600'
                                    }`}>
                                        {formatCurrency(calculatedBalance || 0)}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        DR: {formatCurrency(totalDebits)} | CR: {formatCurrency(totalCredits)}
                                    </p>
                                </div>
                                <div className={`p-3 rounded-full ${
                                    calculatedBalance >= 0 ? 'bg-blue-100' : 'bg-orange-100'
                                }`}>
                                    {calculatedBalance >= 0 ?
                                        <TrendingUp className="h-6 w-6 text-blue-600" /> :
                                        <TrendingDown className="h-6 w-6 text-orange-600" />
                                    }
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card className="mt-6">
                <CardHeader className="pb-3">
                    <div className="flex items-center space-x-2">
                        <Calendar className="h-4 w-4 text-gray-600" />
                        <h3 className="text-lg font-medium">{t('Transaction History')}</h3>
                    </div>
                </CardHeader>
                <CardContent>
                    {history?.data?.length > 0 ? (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-50">
                                        <TableHead className="font-semibold">{t('Journal Number')}</TableHead>
                                        <TableHead className="font-semibold">{t('Date')}</TableHead>
                                        <TableHead className="font-semibold">{t('Description')}</TableHead>
                                        <TableHead className="text-right font-semibold">{t('Debit')}</TableHead>
                                        <TableHead className="text-right font-semibold">{t('Credit')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {history.data?.map((item) => (
                                        <TableRow key={item.id} className="hover:bg-gray-50">
                                            <TableCell>
                                                <span className="font-mono text-sm text-blue-700">{item.journal_entry.journal_number}</span>
                                            </TableCell>
                                            <TableCell>{formatDate(item.journal_entry.journal_date)}</TableCell>
                                            <TableCell className="max-w-xs truncate">{item.description}</TableCell>
                                            <TableCell className="text-right">
                                                {item.debit_amount > 0 ? (
                                                    <span className="text-red-600">{formatCurrency(item.debit_amount)}</span>
                                                ) : '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.credit_amount > 0 ? (
                                                    <span className="text-green-600">{formatCurrency(item.credit_amount)}</span>
                                                ) : '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">{t('No transaction history found')}</p>
                            <p className="text-gray-400 text-sm mt-1">{t('Transactions will appear here once journal entries are posted')}</p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
