import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { Download, RefreshCw, ShieldAlert, FileSearch } from 'lucide-react';
import { toast } from 'sonner';

interface GifimOperation {
    direction: 'outbound' | 'inbound';
    payment_id: number;
    payment_reference: string;
    payment_date: string;
    payment_method: string;
    counterparty: string;
    currency_code: string;
    amount_mzn: number;
    status: string;
    gifim_alert_required: boolean;
    gifim_alert_category?: 'cash_threshold' | 'electronic_threshold' | null;
    gifim_alert_status: 'not_required' | 'pending' | 'communicated';
    gifim_reference?: string | null;
    gifim_reported_at?: string | null;
    gifim_submitted_document?: string | null;
    gifim_justification?: string | null;
    high_value_approval_reference?: string | null;
    missing_high_value_approval_reference?: boolean;
    missing_communication_evidence?: boolean;
}

interface GifimCompliancePayload {
    from_date: string;
    to_date: string;
    summary: {
        total_operations: number;
        total_alert_required: number;
        cash_threshold_alerts: number;
        electronic_threshold_alerts: number;
        pending_alerts: number;
        communicated_alerts: number;
        missing_high_value_approval_reference: number;
        missing_communication_evidence: number;
        outbound_operations: number;
        inbound_operations: number;
    };
    operations: GifimOperation[];
    pending_alerts: GifimOperation[];
    communicated_alerts: GifimOperation[];
    requires_attention: GifimOperation[];
}

const statusBadge = (status: string) => {
    switch (status) {
        case 'communicated':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-amber-100 text-amber-800';
        default:
            return 'bg-slate-100 text-slate-800';
    }
};

interface GifimCommunicationFormState {
    direction: 'outbound' | 'inbound';
    payment_id: number | null;
    gifim_reference: string;
    gifim_submitted_document: string;
    gifim_reported_at: string;
    gifim_justification: string;
    high_value_approval_reference: string;
}

const emptyGifimCommunicationForm = (): GifimCommunicationFormState => ({
    direction: 'outbound',
    payment_id: null,
    gifim_reference: '',
    gifim_submitted_document: '',
    gifim_reported_at: new Date().toISOString().slice(0, 16),
    gifim_justification: '',
    high_value_approval_reference: '',
});

export default function GifimComplianceReport() {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [data, setData] = useState<GifimCompliancePayload | null>(null);
    const [loading, setLoading] = useState(false);
    const [gifimRow, setGifimRow] = useState<GifimOperation | null>(null);
    const [gifimSaving, setGifimSaving] = useState(false);
    const [gifimForm, setGifimForm] = useState<GifimCommunicationFormState>(emptyGifimCommunicationForm);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            { label: t('Operations'), value: data.summary.total_operations },
            { label: t('Alert Required'), value: data.summary.total_alert_required },
            { label: t('Pending'), value: data.summary.pending_alerts },
            { label: t('Communicated'), value: data.summary.communicated_alerts },
            { label: t('Cash Threshold'), value: data.summary.cash_threshold_alerts },
            { label: t('Electronic Threshold'), value: data.summary.electronic_threshold_alerts },
        ];
    }, [data, t]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-gifim-compliance-report'), {
                params: {
                    from_date: fromDate,
                    to_date: toDate,
                },
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleExport = () => {
        window.location.href = route('account.reports.mozambique-gifim-compliance-report.export', {
            from_date: fromDate,
            to_date: toDate,
        });
    };

    const openGifimDialog = (row: GifimOperation) => {
        setGifimRow(row);
        setGifimForm({
            direction: row.direction,
            payment_id: row.payment_id,
            gifim_reference: row.gifim_reference || row.payment_reference,
            gifim_submitted_document: row.gifim_submitted_document || `GIFIM-${row.payment_reference}`,
            gifim_reported_at: row.gifim_reported_at ? row.gifim_reported_at.slice(0, 16) : new Date().toISOString().slice(0, 16),
            gifim_justification: row.gifim_justification || '',
            high_value_approval_reference: row.high_value_approval_reference || '',
        });
    };

    const closeGifimDialog = () => {
        setGifimRow(null);
        setGifimForm(emptyGifimCommunicationForm());
    };

    const submitGifimCommunication = async () => {
        if (!gifimRow || gifimForm.payment_id === null) {
            return;
        }

        setGifimSaving(true);
        try {
            const response = await axios.post(route('account.reports.mozambique-gifim-compliance-report.communicate'), {
                direction: gifimForm.direction,
                payment_id: gifimForm.payment_id,
                gifim_reference: gifimForm.gifim_reference,
                gifim_submitted_document: gifimForm.gifim_submitted_document,
                gifim_reported_at: gifimForm.gifim_reported_at || null,
                gifim_justification: gifimForm.gifim_justification || null,
                high_value_approval_reference: gifimForm.high_value_approval_reference || null,
            });

            toast.success(response.data?.message || t('GIFiM communication marked successfully.'));
            closeGifimDialog();
            await fetchData();
        } catch (error: any) {
            toast.error(error?.response?.data?.message || t('Unable to record GIFiM communication.'));
        } finally {
            setGifimSaving(false);
        }
    };

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <ShieldAlert className="h-5 w-5 text-primary" />
                    {t('GIFiM Compliance Report')}
                </CardTitle>
                <CardDescription>
                    {t('Tracks cash and electronic operations that require GIFiM communication or approval references.')}
                </CardDescription>
            </CardHeader>

            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchData} disabled={loading} className="gap-2">
                            <RefreshCw className="h-4 w-4" />
                            {loading ? t('Loading...') : t('Refresh')}
                        </Button>
                        <Button variant="outline" onClick={handleExport} className="gap-2">
                            <Download className="h-4 w-4" />
                            {t('Export CSV')}
                        </Button>
                    </div>
                </div>

                {data && (
                    <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-6">
                        {summaryCards.map((card) => (
                            <div key={card.label} className="rounded-lg border bg-white p-3">
                                <div className="text-xs uppercase text-muted-foreground">{card.label}</div>
                                <div className="mt-1 text-xl font-semibold">{card.value}</div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            <CardContent className="p-6">
                {data ? (
                    <div className="space-y-6">
                        <div className="rounded-lg border bg-slate-50 p-4 text-sm">
                            <div className="font-medium">{t('Period')}</div>
                            <div>{formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)}</div>
                        </div>

                        <div className="overflow-y-auto max-h-[60vh] rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-4 py-3 text-left">{t('Reference')}</th>
                                        <th className="px-4 py-3 text-left">{t('Counterparty')}</th>
                                        <th className="px-4 py-3 text-left">{t('Method')}</th>
                                        <th className="px-4 py-3 text-left">{t('Amount')}</th>
                                        <th className="px-4 py-3 text-left">{t('Status')}</th>
                                        <th className="px-4 py-3 text-left">{t('Evidence')}</th>
                                        <th className="px-4 py-3 text-left">{t('Actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.requires_attention.map((row) => (
                                        <tr key={`${row.direction}-${row.payment_id}`} className="border-b align-top">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.payment_reference}</div>
                                                <div className="text-xs text-muted-foreground">{formatDate(row.payment_date)}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>{row.counterparty}</div>
                                                <div className="text-xs text-muted-foreground">{row.direction}</div>
                                            </td>
                                            <td className="px-4 py-3">{row.payment_method}</td>
                                            <td className="px-4 py-3">{formatCurrency(row.amount_mzn)}</td>
                                            <td className="px-4 py-3">
                                                <Badge className={statusBadge(row.gifim_alert_status)}>{row.gifim_alert_status}</Badge>
                                                <div className="mt-2 text-xs text-muted-foreground">
                                                    {row.gifim_alert_category || '-'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="text-xs text-muted-foreground">{row.high_value_approval_reference || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{row.gifim_reference || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{row.gifim_submitted_document || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Button size="sm" variant="outline" onClick={() => openGifimDialog(row)}>
                                                    {t('Record communication')}
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {data.requires_attention.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="py-8 text-center text-muted-foreground">
                                                {t('No GIFiM alerts requiring attention')}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Pending Alerts')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[22rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.pending_alerts.map((row) => (
                                                <tr key={`pending-${row.direction}-${row.payment_id}`} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.payment_reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.payment_method}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">{formatCurrency(row.amount_mzn)}</td>
                                                </tr>
                                            ))}
                                            {data.pending_alerts.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No pending GIFiM alerts')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Communicated Alerts')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[22rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.communicated_alerts.map((row) => (
                                                <tr key={`communicated-${row.direction}-${row.payment_id}`} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{row.payment_reference}</div>
                                                        <div className="text-xs text-muted-foreground">{row.gifim_reference || '-'}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Badge className={statusBadge(row.gifim_alert_status)}>{row.gifim_alert_status}</Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.communicated_alerts.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No communicated GIFiM alerts')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={FileSearch}
                        title={t('GIFiM Compliance Report')}
                        description={t('Select a period to generate the report.')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>

            <Dialog open={Boolean(gifimRow)} onOpenChange={(open) => !open && closeGifimDialog()}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{t('Record GIFiM Communication')}</DialogTitle>
                        <DialogDescription>
                            {gifimRow ? `${gifimRow.payment_reference} · ${gifimRow.counterparty}` : ''}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('GIFiM reference')}</Label>
                            <Input
                                value={gifimForm.gifim_reference}
                                onChange={(event) => setGifimForm((current) => ({
                                    ...current,
                                    gifim_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Submitted document')}</Label>
                            <Input
                                value={gifimForm.gifim_submitted_document}
                                onChange={(event) => setGifimForm((current) => ({
                                    ...current,
                                    gifim_submitted_document: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('Reported at')}</Label>
                            <Input
                                type="datetime-local"
                                value={gifimForm.gifim_reported_at}
                                onChange={(event) => setGifimForm((current) => ({
                                    ...current,
                                    gifim_reported_at: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>{t('High-value approval reference')}</Label>
                            <Input
                                value={gifimForm.high_value_approval_reference}
                                onChange={(event) => setGifimForm((current) => ({
                                    ...current,
                                    high_value_approval_reference: event.target.value,
                                }))}
                            />
                        </div>

                        <div className="md:col-span-2 space-y-2">
                            <Label>{t('Justification')}</Label>
                            <Input
                                value={gifimForm.gifim_justification}
                                onChange={(event) => setGifimForm((current) => ({
                                    ...current,
                                    gifim_justification: event.target.value,
                                }))}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeGifimDialog}>
                            {t('Cancel')}
                        </Button>
                        <Button type="button" onClick={submitGifimCommunication} disabled={gifimSaving}>
                            {gifimSaving ? t('Saving...') : t('Record communication')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
