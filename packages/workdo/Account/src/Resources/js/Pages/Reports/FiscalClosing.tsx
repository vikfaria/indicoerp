import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { RotateCcw, Lock } from 'lucide-react';
import axios from 'axios';

interface FiscalClosingProps {
    financialYear?: {
        year_start_date: string;
        year_end_date: string;
    };
}

interface FiscalClosingRow {
    id: number;
    period_from: string;
    period_to: string;
    status: 'closed' | 'reopened';
    close_reason?: string | null;
    reopen_reason?: string | null;
    closed_at?: string | null;
    reopened_at?: string | null;
    closed_by?: string | null;
    reopened_by?: string | null;
    snapshot?: any;
}

export default function FiscalClosing({ financialYear }: FiscalClosingProps) {
    const { t } = useTranslation();
    const [periodFrom, setPeriodFrom] = useState(financialYear?.year_start_date || '');
    const [periodTo, setPeriodTo] = useState(financialYear?.year_end_date || '');
    const [closeReason, setCloseReason] = useState('');
    const [data, setData] = useState<{ latest_closed_until: string | null; closings: FiscalClosingRow[] }>({
        latest_closed_until: null,
        closings: [],
    });
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const fetchClosings = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.fiscal-closings'));
            setData(response.data);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchClosings();
    }, []);

    const handleClose = async () => {
        setSubmitting(true);
        try {
            const response = await axios.post(route('account.reports.fiscal-closings.close'), {
                period_from: periodFrom,
                period_to: periodTo,
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
            const response = await axios.post(route('account.reports.fiscal-closings.reopen', closingId), {
                reopen_reason: reopenReason || null,
            });
            setData(response.data.data);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    return (
        <Card className="shadow-sm">
            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Period From')}</label>
                        <DatePicker value={periodFrom} onChange={setPeriodFrom} placeholder={t('Select start date')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Period To')}</label>
                        <DatePicker value={periodTo} onChange={setPeriodTo} placeholder={t('Select end date')} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('Close Reason')}</label>
                        <Input value={closeReason} onChange={(e) => setCloseReason(e.target.value)} placeholder={t('Optional')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={handleClose} disabled={submitting} className="gap-2">
                            <Lock className="h-4 w-4" />
                            {submitting ? t('Closing...') : t('Close Period')}
                        </Button>
                        <Button variant="outline" onClick={fetchClosings} disabled={loading}>
                            {t('Refresh')}
                        </Button>
                    </div>
                </div>

                <div className="mt-4 text-sm">
                    <span className="font-medium">{t('Latest Closed Until')}:</span>{' '}
                    <span>{data.latest_closed_until || '-'}</span>
                </div>
            </CardContent>

            <CardContent className="p-0">
                <div className="overflow-y-auto max-h-[60vh]">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-gray-50">
                                <th className="text-left py-3 px-4">{t('Period')}</th>
                                <th className="text-left py-3 px-4">{t('Status')}</th>
                                <th className="text-left py-3 px-4">{t('Closed By')}</th>
                                <th className="text-left py-3 px-4">{t('Closed At')}</th>
                                <th className="text-left py-3 px-4">{t('Net VAT')}</th>
                                <th className="text-left py-3 px-4">{t('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.closings.map((closing) => (
                                <tr key={closing.id} className="border-b align-top">
                                    <td className="py-3 px-4">{closing.period_from} - {closing.period_to}</td>
                                    <td className="py-3 px-4">{closing.status}</td>
                                    <td className="py-3 px-4">{closing.closed_by || '-'}</td>
                                    <td className="py-3 px-4">{closing.closed_at || '-'}</td>
                                    <td className="py-3 px-4">
                                        {closing.snapshot?.mozambique_fiscal_map?.vat?.net_vat_payable ?? '-'}
                                    </td>
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
                                    <td colSpan={6} className="py-6 text-center text-muted-foreground">
                                        {t('No fiscal closings found')}
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

