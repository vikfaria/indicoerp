import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Printer, FileText } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface Account {
    id: number;
    account_code: string;
    account_name: string;
}

interface Transaction {
    id: number;
    date: string;
    description: string;
    reference_type: string;
    debit: number;
    credit: number;
    balance: number;
}

interface AccountStatementData {
    opening_balance: number;
    transactions: Transaction[];
    closing_balance: number;
}

interface AccountStatementProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    }
}

export default function AccountStatement({ financialYear }: AccountStatementProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [accountId, setAccountId] = useState('');
    const [fromDate, setFromDate] = useState(financialYear?.year_start_date || '');
    const [toDate, setToDate] = useState(financialYear?.year_end_date || '');
    const [accounts, setAccounts] = useState<Account[]>([]);
    const [data, setData] = useState<AccountStatementData | null>(null);
    const [selectedAccount, setSelectedAccount] = useState<Account | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        if (!accountId) return;

        setLoading(true);
        try {
            const response = await axios.get(route('double-entry.reports.account-statement'), {
                params: { account_id: accountId, from_date: fromDate, to_date: toDate }
            });
            setData(response.data.data);
            setAccounts(response.data.accounts);
            setSelectedAccount(response.data.selectedAccount);
        } catch (error) {
            console.error('Error fetching account statement:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const loadData = async () => {
            try {
                const response = await axios.get(route('double-entry.reports.account-statement'));
                setAccounts(response.data.accounts);
                if (response.data.data) {
                    setData(response.data.data);
                }
                if (response.data.selectedAccount) {
                    setAccountId(response.data.selectedAccount.id.toString());
                    setSelectedAccount(response.data.selectedAccount);
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        };
        loadData();
    }, []);

    const handleDownloadPDF = () => {
        const printUrl = route('double-entry.reports.account-statement.print') +
            `?account_id=${accountId}&from_date=${fromDate}&to_date=${toDate}&download=pdf`;
        window.open(printUrl, '_blank');
    };

    const clearFilters = () => {
        setAccountId('');
        setFromDate(financialYear?.year_start_date || '');
        setToDate(financialYear?.year_end_date || '');
        setData(null);
        setSelectedAccount(null);
    };

    const totalDebit = data?.transactions.reduce((sum, t) => sum + t.debit, 0) || 0;
    const totalCredit = data?.transactions.reduce((sum, t) => sum + t.credit, 0) || 0;

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Account')}</label>
                        <Select value={accountId} onValueChange={setAccountId}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Account')} />
                            </SelectTrigger>
                            <SelectContent searchable>
                                {accounts.map(account => (
                                    <SelectItem key={account.id} value={account.id.toString()}>
                                        {account.account_code} - {account.account_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
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
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={!accountId || loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                        {data && auth.user?.permissions?.includes('print-account-statement') && (
                            <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            {data ? (
                <CardContent className="p-0">
                    {selectedAccount && (
                        <div className="p-4 bg-gray-50 border-b">
                            <h3 className="font-semibold text-lg">
                                {selectedAccount.account_code} - {selectedAccount.account_name}
                            </h3>
                            {fromDate && toDate && (
                                <p className="text-sm text-gray-600">
                                    {t('Statement Period')}: {formatDate(fromDate)} {t('to')} {formatDate(toDate)}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[60vh] w-full">
                        <div className="min-w-[900px]">
                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Date')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Description')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Reference')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Debit')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Credit')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Balance')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.opening_balance !== 0 && (
                                        <tr className="bg-blue-50">
                                            <td className="px-4 py-3 text-sm font-semibold" colSpan={5}>
                                                {t('Opening Balance')}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-right font-semibold">
                                                {formatCurrency(data.opening_balance)}
                                            </td>
                                        </tr>
                                    )}
                                    {data.transactions.length > 0 ? (
                                        data.transactions.map((transaction) => (
                                            <tr key={transaction.id} className="border-t hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm">{formatDate(transaction.date)}</td>
                                                <td className="px-4 py-3 text-sm">{transaction.description}</td>
                                                <td className="px-4 py-3 text-sm">{transaction.reference_type}</td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {transaction.debit > 0 ? formatCurrency(transaction.debit) : '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {transaction.credit > 0 ? formatCurrency(transaction.credit) : '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right font-medium">
                                                    {formatCurrency(transaction.balance)}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-8">
                                                <NoRecordsFound
                                                    icon={FileText}
                                                    title={t('No transactions found')}
                                                    description={t('No transactions found for the selected account and date range.')}
                                                    className="h-auto"
                                                />
                                            </td>
                                        </tr>
                                    )}
                                    {data.transactions.length > 0 && (
                                        <>
                                            <tr className="bg-gray-50 border-t-2">
                                                <td className="px-4 py-3 text-sm font-semibold" colSpan={3}>
                                                    {t('Total')}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right font-semibold">
                                                    {formatCurrency(totalDebit)}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right font-semibold">
                                                    {formatCurrency(totalCredit)}
                                                </td>
                                                <td className="px-4 py-3 text-sm"></td>
                                            </tr>
                                            <tr className="bg-gray-100 font-semibold sticky bottom-0">
                                                <td className="px-4 py-3 text-sm" colSpan={5}>
                                                    {t('Closing Balance')}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {formatCurrency(data.closing_balance)}
                                                </td>
                                            </tr>
                                        </>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            ) : (
                <CardContent className="p-0">
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Account Statement')}
                        description={t('Select an account and date range to generate the statement')}
                        className="h-auto py-12"
                    />
                </CardContent>
            )}
        </Card>
    );
}
