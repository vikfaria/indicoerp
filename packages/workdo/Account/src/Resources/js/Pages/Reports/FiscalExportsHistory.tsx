import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDate } from '@/utils/helpers';
import { ClipboardList, Download, RefreshCw, Send } from 'lucide-react';

interface ReportAuthProps {
    auth: {
        user?: {
            permissions?: string[];
        };
    };
}

interface FiscalExportHistoryRow {
    id: number;
    export_type: string;
    period_start?: string | null;
    period_end?: string | null;
    generated_by?: number | null;
    generated_at?: string | null;
    file_name?: string | null;
    file_hash?: string | null;
    file_path?: string | null;
    status?: string | null;
    submission_channel?: string | null;
    submission_reference?: string | null;
    submitted_at?: string | null;
    metadata?: Record<string, any>;
}

interface FiscalExportsHistoryPayload {
    from_date: string | null;
    to_date: string | null;
    rows: FiscalExportHistoryRow[];
    summary_by_status: Record<string, number>;
    summary_by_type: Record<string, number>;
}

const statusBadgeClass = (status?: string | null): string => {
    switch (status) {
        case 'submitted':
        case 'validated':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'generated':
        default:
            return 'bg-slate-100 text-slate-800';
    }
};

export default function FiscalExportsHistory() {
    const { t } = useTranslation();
    const { auth } = usePage<ReportAuthProps>().props;
    const userPermissions = auth.user?.permissions || [];
    const canManageReports = userPermissions.includes('manage-account-reports');
    const canDownloadReports = canManageReports || userPermissions.includes('view-tax-summary');

    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [exportType, setExportType] = useState('');
    const [status, setStatus] = useState('all');
    const [limit, setLimit] = useState('200');
    const [data, setData] = useState<FiscalExportsHistoryPayload>({
        from_date: null,
        to_date: null,
        rows: [],
        summary_by_status: {},
        summary_by_type: {},
    });
    const [loading, setLoading] = useState(false);
    const [submittingId, setSubmittingId] = useState<number | null>(null);

    const summaryCards = useMemo(() => {
        return [
            { label: t('Generated'), value: data.summary_by_status.generated ?? 0 },
            { label: t('Submitted'), value: data.summary_by_status.submitted ?? 0 },
            { label: t('Validated'), value: data.summary_by_status.validated ?? 0 },
            { label: t('Rejected'), value: data.summary_by_status.rejected ?? 0 },
        ];
    }, [data.summary_by_status, t]);

    const fetchHistory = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-fiscal-exports-history'), {
                params: {
                    from_date: fromDate,
                    to_date: toDate,
                    export_type: exportType || undefined,
                    status: status === 'all' ? undefined : status,
                    limit: Number(limit) || 200,
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
        fetchHistory();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleSubmit = async (row: FiscalExportHistoryRow) => {
        if (!canManageReports) {
            return;
        }

        const submissionChannel = window.prompt(
            t('Submission channel (manual_upload, xml_export, webservice, api)'),
            row.submission_channel || 'manual_upload'
        ) || '';
        const submissionReference = window.prompt(
            t('Submission reference'),
            row.submission_reference || ''
        ) || '';
        const notes = window.prompt(t('Submission notes (optional)'), '') || '';

        if (!submissionChannel || !submissionReference) {
            return;
        }

        setSubmittingId(row.id);
        try {
            await axios.post(route('account.reports.mozambique-fiscal-exports-history.submit', row.id), {
                submission_channel: submissionChannel,
                submission_reference: submissionReference,
                status: 'submitted',
                notes: notes || null,
            });
            await fetchHistory();
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setSubmittingId(null);
        }
    };

    const handleDownload = (row: FiscalExportHistoryRow) => {
        if (!row.file_path || !canDownloadReports) {
            return;
        }

        window.location.href = route('account.reports.mozambique-fiscal-exports-history.download', row.id);
    };

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <ClipboardList className="h-5 w-5 text-primary" />
                    {t('Fiscal Export History')}
                </CardTitle>
                <CardDescription>
                    {t('Tracks SAF-T and other fiscal exports with status, hash, period and submission metadata.')}
                </CardDescription>
            </CardHeader>

            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('Export Type')}</label>
                        <Input
                            value={exportType}
                            onChange={(e) => setExportType(e.target.value)}
                            placeholder={t('e.g. saft_xml, fiscal_closings_csv')}
                        />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('Status')}</label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All statuses')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                <SelectItem value="all">{t('All statuses')}</SelectItem>
                                <SelectItem value="generated">{t('Generated')}</SelectItem>
                                <SelectItem value="submitted">{t('Submitted')}</SelectItem>
                                <SelectItem value="validated">{t('Validated')}</SelectItem>
                                <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                                    </SelectContent>
                                </Select>
                    </div>
                    <div className="flex items-end gap-2">
                        <Button onClick={fetchHistory} disabled={loading} className="gap-2">
                            <RefreshCw className="h-4 w-4" />
                            {loading ? t('Loading...') : t('Refresh')}
                        </Button>
                        <Input
                            type="number"
                            min="10"
                            max="500"
                            value={limit}
                            onChange={(e) => setLimit(e.target.value)}
                            className="w-24"
                        />
                    </div>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                    {summaryCards.map((card) => (
                        <div key={card.label} className="rounded-lg border bg-white p-3">
                            <div className="text-xs uppercase text-muted-foreground">{card.label}</div>
                            <div className="mt-1 text-xl font-semibold">{card.value}</div>
                        </div>
                    ))}
                </div>
            </CardContent>

            <CardContent className="p-0">
                <div className="overflow-y-auto max-h-[65vh]">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-gray-50">
                                <th className="px-4 py-3 text-left">{t('Export')}</th>
                                <th className="px-4 py-3 text-left">{t('Period')}</th>
                                <th className="px-4 py-3 text-left">{t('Status')}</th>
                                <th className="px-4 py-3 text-left">{t('Hash')}</th>
                                <th className="px-4 py-3 text-left">{t('Submission')}</th>
                                <th className="px-4 py-3 text-left">{t('Generated At')}</th>
                                <th className="px-4 py-3 text-left">{t('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.rows.map((row) => (
                                <tr key={row.id} className="border-b align-top">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{row.export_type}</div>
                                        <div className="text-xs text-muted-foreground">{row.file_name || '-'}</div>
                                        {row.file_path && (
                                            <div className="mt-1 text-xs text-muted-foreground">{t('Archive saved')}</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.period_start || '-'} {row.period_end ? `→ ${row.period_end}` : ''}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={statusBadgeClass(row.status)}>{row.status || '-'}</Badge>
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {row.file_hash ? `${row.file_hash.slice(0, 12)}...` : '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div>{row.submission_channel || '-'}</div>
                                        <div className="text-xs text-muted-foreground">{row.submission_reference || '-'}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {row.submitted_at ? formatDate(row.submitted_at) : '-'}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.generated_at ? formatDate(row.generated_at) : '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-2">
                                            {row.file_path && canDownloadReports ? (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDownload(row)}
                                                    className="gap-2"
                                                >
                                                    <Download className="h-4 w-4" />
                                                    {t('Download')}
                                                </Button>
                                            ) : null}
                                            {canManageReports ? (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleSubmit(row)}
                                                    disabled={submittingId === row.id}
                                                    className="gap-2"
                                                >
                                                    <Send className="h-4 w-4" />
                                                    {submittingId === row.id ? t('Saving...') : t('Submit')}
                                                </Button>
                                            ) : null}
                                            {!canManageReports && !row.file_path && (
                                                <span className="text-xs text-muted-foreground">{t('Read only')}</span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {data.rows.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="py-8 text-center text-muted-foreground">
                                        {t('No fiscal exports found')}
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
