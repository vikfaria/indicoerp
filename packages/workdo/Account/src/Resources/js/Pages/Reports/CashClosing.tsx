import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CircleDollarSign, Download, Lock, RotateCcw } from 'lucide-react';

interface CashAccountRow {
    bank_account_id: number;
    account_number: string;
    account_name: string;
    bank_name: string;
    account_type: string;
    current_balance_mzn: number;
}

interface CashClosingRow {
    id: number;
    bank_account_id: number;
    cash_account_name?: string | null;
    cash_account_number?: string | null;
    cash_account_type?: string | null;
    closing_date: string;
    status: 'closed' | 'reopened';
    opening_balance_mzn: number;
    cash_in_mzn: number;
    cash_out_mzn: number;
    expected_balance_mzn: number;
    counted_balance_mzn: number;
    variance_mzn: number;
    close_reason?: string | null;
    reopen_reason?: string | null;
    closed_at?: string | null;
    reopened_at?: string | null;
    closed_by?: string | null;
    reopened_by?: string | null;
    snapshot?: any;
}

interface CashClosingPayload {
    latest_closed_until: string | null;
    cash_accounts: CashAccountRow[];
    closings: CashClosingRow[];
}

export default function CashClosing() {
    const { t } = useTranslation();
    const [closingDate, setClosingDate] = useState(new Date().toISOString().slice(0, 10));
    const [selectedCashAccountId, setSelectedCashAccountId] = useState('');
    const [countedBalance, setCountedBalance] = useState('');
    const [closeReason, setCloseReason] = useState('');
    const [data, setData] = useState<CashClosingPayload>({
        latest_closed_until: null,
        cash_accounts: [],
        closings: [],
    });
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const selectedAccount = data.cash_accounts.find((account) => String(account.bank_account_id) === selectedCashAccountId) || null;

    const fetchClosings = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.cash-closings'));
            const payload: CashClosingPayload = response.data;
            setData(payload);

            if (!selectedCashAccountId && payload.cash_accounts.length > 0) {
                const firstAccount = payload.cash_accounts[0];
                setSelectedCashAccountId(String(firstAccount.bank_account_id));
                setCountedBalance(String(firstAccount.current_balance_mzn ?? 0));
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchClosings();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!selectedAccount) {
            return;
        }

        setCountedBalance(String(selectedAccount.current_balance_mzn ?? 0));
    }, [selectedAccount]);

    const handleClose = async () => {
        if (!selectedCashAccountId) {
            return;
        }

        setSubmitting(true);
        try {
            const response = await axios.post(route('account.reports.cash-closings.close'), {
                bank_account_id: Number(selectedCashAccountId),
                closing_date: closingDate,
                counted_balance_mzn: countedBalance,
                close_reason: closeReason || null,
            });

            setData(response.data.data);
            setCloseReason('');
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setSubmitting(false);
        }
    };

    const handleReopen = async (closingId: number) => {
        const reopenReason = window.prompt(t('Reason for reopening (optional)')) || '';

        try {
            const response = await axios.post(route('account.reports.cash-closings.reopen', closingId), {
                reopen_reason: reopenReason || null,
            });

            setData(response.data.data);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleExport = () => {
        window.location.href = route('account.reports.cash-closings.export');
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Cash Account')}</label>
                        <Select value={selectedCashAccountId} onValueChange={setSelectedCashAccountId}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select cash account')} />
                            </SelectTrigger>
                            <SelectContent>
                                {data.cash_accounts.map((account) => (
                                    <SelectItem key={account.bank_account_id} value={String(account.bank_account_id)}>
                                        {account.account_name} - {account.account_number}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Closing Date')}</label>
                        <DatePicker value={closingDate} onChange={setClosingDate} placeholder={t('Select closing date')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Counted Balance')}</label>
                        <Input type="number" step="0.01" min="0" value={countedBalance} onChange={(e) => setCountedBalance(e.target.value)} placeholder={t('Physical cash count')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Close Reason')}</label>
                        <Input value={closeReason} onChange={(e) => setCloseReason(e.target.value)} placeholder={t('Optional')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={handleClose} disabled={submitting || !selectedCashAccountId} className="gap-2">
                            <Lock className="h-4 w-4" />
                            {submitting ? t('Closing...') : t('Close Cash Day')}
                        </Button>
                        <Button variant="outline" onClick={handleExport} className="gap-2">
                            <Download className="h-4 w-4" />
                            {t('Export CSV')}
                        </Button>
                        <Button variant="outline" onClick={fetchClosings} disabled={loading}>
                            {t('Refresh')}
                        </Button>
                    </div>
                </div>

                <div className="mt-4 grid grid-cols-1 gap-2 text-sm md:grid-cols-3">
                    <div>
                        <span className="font-medium">{t('Latest Closed Until')}:</span> <span>{data.latest_closed_until || '-'}</span>
                    </div>
                    <div>
                        <span className="font-medium">{t('Selected Account Balance')}:</span> <span>{selectedAccount ? Number(selectedAccount.current_balance_mzn).toFixed(2) : '-'}</span>
                    </div>
                    <div>
                        <span className="font-medium">{t('Cash Accounts')}:</span> <span>{data.cash_accounts.length}</span>
                    </div>
                </div>
            </CardContent>

            <CardContent className="p-0">
                <div className="overflow-y-auto max-h-[60vh]">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-gray-50">
                                <th className="text-left py-3 px-4">{t('Date')}</th>
                                <th className="text-left py-3 px-4">{t('Cash Account')}</th>
                                <th className="text-left py-3 px-4">{t('Status')}</th>
                                <th className="text-left py-3 px-4">{t('Opening')}</th>
                                <th className="text-left py-3 px-4">{t('Cash In')}</th>
                                <th className="text-left py-3 px-4">{t('Cash Out')}</th>
                                <th className="text-left py-3 px-4">{t('Expected')}</th>
                                <th className="text-left py-3 px-4">{t('Counted')}</th>
                                <th className="text-left py-3 px-4">{t('Variance')}</th>
                                <th className="text-left py-3 px-4">{t('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.closings.map((closing) => (
                                <tr key={closing.id} className="border-b align-top">
                                    <td className="py-3 px-4">{closing.closing_date}</td>
                                    <td className="py-3 px-4">
                                        <div className="font-medium">{closing.cash_account_name || '-'}</div>
                                        <div className="text-xs text-muted-foreground">{closing.cash_account_number || '-'}</div>
                                    </td>
                                    <td className="py-3 px-4">
                                        <Badge className={closing.status === 'closed' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}>
                                            {closing.status}
                                        </Badge>
                                    </td>
                                    <td className="py-3 px-4">{Number(closing.opening_balance_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">{Number(closing.cash_in_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">{Number(closing.cash_out_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">{Number(closing.expected_balance_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">{Number(closing.counted_balance_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">{Number(closing.variance_mzn).toFixed(2)}</td>
                                    <td className="py-3 px-4">
                                        {closing.status === 'closed' && (
                                            <Button variant="ghost" size="sm" onClick={() => handleReopen(closing.id)} className="gap-2">
                                                <RotateCcw className="h-4 w-4" />
                                                {t('Reopen')}
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {data.closings.length === 0 && (
                                <tr>
                                    <td colSpan={10} className="py-6 text-center text-muted-foreground">
                                        <CircleDollarSign className="h-5 w-5 mx-auto mb-2" />
                                        {t('No cash closings found')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
