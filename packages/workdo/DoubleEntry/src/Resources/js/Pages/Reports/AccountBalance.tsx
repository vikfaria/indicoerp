import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Printer, FileText } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';
import axios from 'axios';

interface AccountBalanceItem {
    account_code: string;
    account_name: string;
    account_type: string;
    debit: number;
    credit: number;
    net_balance: number;
}

interface AccountBalanceGroup {
    accounts: AccountBalanceItem[];
    subtotal_debit: number;
    subtotal_credit: number;
    subtotal_net: number;
}

interface AccountBalanceData {
    grouped: Record<string, AccountBalanceGroup>;
    totals: {
        debit: number;
        credit: number;
        net: number;
    };
    as_of_date: string;
}

interface AccountBalanceProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    }
}

export default function AccountBalance({ financialYear }: AccountBalanceProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    const [asOfDate, setAsOfDate] = useState(financialYear?.year_end_date || '');
    const [accountType, setAccountType] = useState('');
    const [showZeroBalances, setShowZeroBalances] = useState(false);
    const [data, setData] = useState<AccountBalanceData | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('double-entry.reports.account-balance'), {
                params: { as_of_date: asOfDate, account_type: accountType, show_zero_balances: showZeroBalances }
            });
            setData(response.data);
        } catch (error) {
            console.error('Error fetching account balance:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleDownloadPDF = () => {
        const printUrl = route('double-entry.reports.account-balance.print') +
            `?as_of_date=${asOfDate}&account_type=${accountType}&show_zero_balances=${showZeroBalances}&download=pdf`;
        window.open(printUrl, '_blank');
    };

    const clearFilters = () => {
        setAsOfDate(financialYear?.year_end_date || '');
        setAccountType('');
        setShowZeroBalances(false);
    };

    const typeColors: Record<string, string> = {
        'Assets': 'bg-blue-50',
        'Liabilities': 'bg-red-50',
        'Equity': 'bg-green-50',
        'Revenue': 'bg-purple-50',
        'Expenses': 'bg-orange-50',
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('As of Date')}</label>
                        <DatePicker
                            value={asOfDate}
                            onChange={setAsOfDate}
                            placeholder={t('Select date')}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Account Type')}</label>
                        <Select value={accountType} onValueChange={setAccountType}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('All Types')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=" ">{t('All Types')}</SelectItem>
                                <SelectItem value="Assets">{t('Assets')}</SelectItem>
                                <SelectItem value="Liabilities">{t('Liabilities')}</SelectItem>
                                <SelectItem value="Equity">{t('Equity')}</SelectItem>
                                <SelectItem value="Revenue">{t('Revenue')}</SelectItem>
                                <SelectItem value="Expenses">{t('Expenses')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-end">
                        <div className="flex items-center space-x-2 mb-2">
                            <Checkbox
                                id="show-zero"
                                checked={showZeroBalances}
                                onCheckedChange={(checked) => setShowZeroBalances(checked as boolean)}
                            />
                            <Label htmlFor="show-zero" className="text-sm">{t('Show Zero Balances')}</Label>
                        </div>
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} size="sm">
                            {loading ? t('Loading...') : t('Generate')}
                        </Button>
                        <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                        {data && auth.user?.permissions?.includes('print-account-balance') && (
                            <Button variant="outline" size="sm" onClick={handleDownloadPDF} className="gap-2">
                                <Printer className="h-4 w-4" />
                                {t('Download PDF')}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                {data && Object.keys(data.grouped).length > 0 ? (
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[60vh] w-full">
                        <div className="min-w-[900px]">
                            {data.as_of_date && (
                                <div className="p-4 bg-gray-50 border-b">
                                    <h3 className="font-semibold text-lg">
                                        {t('Account Balance Summary')}
                                    </h3>
                                    <p className="text-sm text-gray-600">
                                        {t('As of')}: {formatDate(data.as_of_date)}
                                    </p>
                                </div>
                            )}

                            <table className="w-full">
                                <thead className="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Account Code')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Account Name')}</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold">{t('Type')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Debit')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Credit')}</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold">{t('Net Balance')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(data.grouped).map(([type, group]) => (
                                        <>
                                            <tr key={`header-${type}`} className={`${typeColors[type] || 'bg-gray-50'} font-semibold`}>
                                                <td colSpan={6} className="px-4 py-2 text-sm">{t(type)}</td>
                                            </tr>
                                            {group.accounts.map((account, idx) => (
                                                <tr key={`${type}-${idx}`} className="border-t hover:bg-gray-50">
                                                    <td className="px-4 py-2 text-sm">{account.account_code}</td>
                                                    <td className="px-4 py-2 text-sm">{account.account_name}</td>
                                                    <td className="px-4 py-2 text-sm">{t(type)}</td>
                                                    <td className="px-4 py-2 text-sm text-right">
                                                        {account.debit > 0 ? formatCurrency(account.debit) : '-'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right">
                                                        {account.credit > 0 ? formatCurrency(account.credit) : '-'}
                                                    </td>
                                                    <td className="px-4 py-2 text-sm text-right font-medium">
                                                        {formatCurrency(account.net_balance)}
                                                    </td>
                                                </tr>
                                            ))}
                                            <tr key={`subtotal-${type}`} className="bg-gray-100 font-semibold border-t-2">
                                                <td colSpan={3} className="px-4 py-3 text-sm">{t('Subtotal')} - {t(type)}</td>
                                                <td className="px-4 py-3 text-sm text-right">{formatCurrency(group.subtotal_debit)}</td>
                                                <td className="px-4 py-3 text-sm text-right">{formatCurrency(group.subtotal_credit)}</td>
                                                <td className="px-4 py-3 text-sm text-right">{formatCurrency(group.subtotal_net)}</td>
                                            </tr>
                                            <tr key={`space-${type}`} className="h-2">
                                                <td colSpan={6}></td>
                                            </tr>
                                        </>
                                    ))}
                                    <tr className="bg-gray-200 font-bold border-t-4 border-gray-800 sticky bottom-0">
                                        <td colSpan={3} className="px-4 py-3 text-sm">{t('Grand Total')}</td>
                                        <td className="px-4 py-3 text-sm text-right">{formatCurrency(data.totals.debit)}</td>
                                        <td className="px-4 py-3 text-sm text-right">{formatCurrency(data.totals.credit)}</td>
                                        <td className="px-4 py-3 text-sm text-right">{formatCurrency(data.totals.net)}</td>
                                    </tr>
                                </tbody>
                            </table>


                        </div>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={FileText}
                        title={t('Account Balance Summary')}
                        description={t('Select date to generate the report')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
