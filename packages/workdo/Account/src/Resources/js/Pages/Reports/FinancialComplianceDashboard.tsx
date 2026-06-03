import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate } from '@/utils/helpers';
import { Download, RefreshCw, ShieldCheck, TriangleAlert } from 'lucide-react';

interface DashboardIndicator {
    code: string;
    label: string;
    severity: 'critical' | 'high' | 'medium' | 'low';
    value: number;
    source: string;
    message?: string;
}

interface DashboardPayload {
    from_date: string;
    to_date: string;
    due_soon_days: number;
    summary: {
        risk_score: number;
        risk_level: 'low' | 'medium' | 'high' | 'critical';
        total_indicators: number;
        active_indicators: number;
        critical_indicators: number;
        high_indicators: number;
        medium_indicators: number;
        low_indicators: number;
    };
    indicators: DashboardIndicator[];
    active_indicators: DashboardIndicator[];
    details: Record<string, any>;
}

const severityClassMap: Record<DashboardIndicator['severity'], string> = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-slate-100 text-slate-800',
};

const riskClassMap: Record<DashboardPayload['summary']['risk_level'], string> = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    critical: 'bg-red-100 text-red-800',
};

export default function FinancialComplianceDashboard() {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [dueSoonDays, setDueSoonDays] = useState('7');
    const [data, setData] = useState<DashboardPayload | null>(null);
    const [loading, setLoading] = useState(false);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            { label: t('Risk Score'), value: data.summary.risk_score },
            { label: t('Active Indicators'), value: data.summary.active_indicators },
            { label: t('Critical'), value: data.summary.critical_indicators },
            { label: t('High'), value: data.summary.high_indicators },
            { label: t('Medium'), value: data.summary.medium_indicators },
            { label: t('Low'), value: data.summary.low_indicators },
        ];
    }, [data, t]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-financial-compliance-dashboard'), {
                params: {
                    from_date: fromDate,
                    to_date: toDate,
                    due_soon_days: Number(dueSoonDays) || 7,
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
        window.location.href = route('account.reports.mozambique-financial-compliance-dashboard.export', {
            from_date: fromDate,
            to_date: toDate,
            due_soon_days: Number(dueSoonDays) || 7,
        });
    };

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <ShieldCheck className="h-5 w-5 text-primary" />
                    {t('Financial Compliance Dashboard')}
                </CardTitle>
                <CardDescription>
                    {t('Unified view of fiscal, exchange, GIFiM, electronic money and cash-closing risk indicators.')}
                </CardDescription>
            </CardHeader>

            <CardContent className="p-6 border-b bg-gray-50/50">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('From Date')}</label>
                        <DatePicker value={fromDate} onChange={setFromDate} placeholder={t('Select from date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('To Date')}</label>
                        <DatePicker value={toDate} onChange={setToDate} placeholder={t('Select to date')} />
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('Due Soon Days')}</label>
                        <Input
                            type="number"
                            min="1"
                            max="30"
                            value={dueSoonDays}
                            onChange={(e) => setDueSoonDays(e.target.value)}
                        />
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
                        <div className="rounded-lg border bg-white p-3 md:col-span-2">
                            <div className="text-xs uppercase text-muted-foreground">{t('Risk Level')}</div>
                            <div className="mt-2">
                                <Badge className={riskClassMap[data.summary.risk_level]}>{data.summary.risk_level}</Badge>
                            </div>
                        </div>
                    </div>
                )}
            </CardContent>

            <CardContent className="p-6">
                {data ? (
                    <div className="space-y-6">
                        <div className="rounded-lg border bg-slate-50 p-4 text-sm text-slate-700">
                            <div className="font-medium">{t('Report Period')}</div>
                            <div>
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)} · {t('Due soon window')}: {data.due_soon_days} {t('days')}
                            </div>
                        </div>

                        <div className="overflow-y-auto max-h-[55vh] rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-4 py-3 text-left">{t('Code')}</th>
                                        <th className="px-4 py-3 text-left">{t('Indicator')}</th>
                                        <th className="px-4 py-3 text-left">{t('Severity')}</th>
                                        <th className="px-4 py-3 text-left">{t('Value')}</th>
                                        <th className="px-4 py-3 text-left">{t('Source')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.active_indicators.map((indicator) => (
                                        <tr key={indicator.code} className="border-b align-top">
                                            <td className="px-4 py-3 font-mono text-xs">{indicator.code}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{indicator.label}</div>
                                                <div className="text-xs text-muted-foreground">{indicator.message || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={severityClassMap[indicator.severity]}>{indicator.severity}</Badge>
                                            </td>
                                            <td className="px-4 py-3">{indicator.value}</td>
                                            <td className="px-4 py-3">{indicator.source}</td>
                                        </tr>
                                    ))}
                                    {data.active_indicators.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="py-8 text-center text-muted-foreground">
                                                {t('No active compliance indicators for the selected period')}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Fiscal Alerts')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <div>{t('Late invoices')}: {data.details?.fiscal_alerts?.summary?.invoice_issued_with_delay ?? 0}</div>
                                    <div>{t('SAF-T pending')}: {data.details?.fiscal_alerts?.summary?.saft_missing_for_period ?? 0}</div>
                                    <div>{t('Missing NUIT')}: {data.details?.fiscal_alerts?.summary?.documents_without_valid_nuit ?? 0}</div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Operational Reports')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <div>
                                        <span>{t('Exchange control')}</span>: <span>{data.details?.exchange_control?.summary?.total_operations ?? 0}</span>
                                    </div>
                                    <div>
                                        <span>{t('GIFiM')}</span>: <span>{data.details?.gifim?.summary?.pending_alerts ?? 0}</span> {t('pending')}
                                    </div>
                                    <div>
                                        <span>{t('Electronic money')}</span>: <span>{data.details?.electronic_money?.summary?.monthly_limit_exceeded ?? 0}</span> {t('over limit')}
                                    </div>
                                    <div>
                                        <span>{t('Cash closing latest')}</span>: <span>{data.details?.cash_closings?.summary?.latest_closed_until ?? '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={TriangleAlert}
                        title={t('Financial Compliance Dashboard')}
                        description={t('Select a period and generate the dashboard.')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
