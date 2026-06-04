import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency } from '@/utils/helpers';
import { Download, RefreshCw, Wallet, FileSearch } from 'lucide-react';

interface ElectronicMoneyAccount {
    bank_account_id: number;
    account_number: string;
    account_name: string;
    bank_name: string;
    electronic_money_entity?: string | null;
    electronic_money_level?: string | null;
    electronic_money_account_purpose?: string | null;
    company_classification?: string | null;
    requires_attention_reason?: string | null;
    usage_mzn?: number;
    monthly_limit_mzn?: number;
    usage_ratio?: number;
}

interface ElectronicMoneyCompliancePayload {
    from_date: string;
    to_date: string;
    summary: {
        electronic_money_accounts: number;
        missing_classification: number;
        enterprise_exemption_misconfigured: number;
        monthly_limit_exceeded: number;
        monthly_limit_near_threshold: number;
    };
    missing_classification: ElectronicMoneyAccount[];
    enterprise_exemption_misconfigured: ElectronicMoneyAccount[];
    monthly_limit_exceeded: ElectronicMoneyAccount[];
    monthly_limit_near_threshold: ElectronicMoneyAccount[];
}

const ratioBadge = (ratio?: number): string => {
    if (!ratio && ratio !== 0) {
        return 'bg-slate-100 text-slate-800';
    }

    if (ratio >= 1) {
        return 'bg-red-100 text-red-800';
    }

    if (ratio >= 0.9) {
        return 'bg-amber-100 text-amber-800';
    }

    return 'bg-green-100 text-green-800';
};

export default function ElectronicMoneyComplianceReport() {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [data, setData] = useState<ElectronicMoneyCompliancePayload | null>(null);
    const [loading, setLoading] = useState(false);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            { label: t('Accounts'), value: data.summary.electronic_money_accounts },
            { label: t('Missing Classification'), value: data.summary.missing_classification },
            { label: t('Invalid Exemptions'), value: data.summary.enterprise_exemption_misconfigured },
            { label: t('Over Limit'), value: data.summary.monthly_limit_exceeded },
            { label: t('Near Threshold'), value: data.summary.monthly_limit_near_threshold },
        ];
    }, [data, t]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-electronic-money-compliance-report'), {
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
        window.location.href = route('account.reports.mozambique-electronic-money-compliance-report.export', {
            from_date: fromDate,
            to_date: toDate,
        });
    };

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <Wallet className="h-5 w-5 text-primary" />
                    {t('Electronic Money Compliance Report')}
                </CardTitle>
                <CardDescription>
                    {t('Tracks electronic money accounts, classification gaps and monthly limit usage.')}
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
                    <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
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
                        <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Missing Classification')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.missing_classification.map((account) => (
                                                <tr key={account.bank_account_id} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{account.account_name}</div>
                                                        <div className="text-xs text-muted-foreground">{account.account_number}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <div className="text-xs text-muted-foreground">{account.electronic_money_entity || '-'}</div>
                                                        <div className="text-xs text-muted-foreground">{account.electronic_money_level || '-'}</div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.missing_classification.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No classification gaps')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Over Monthly Limit')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.monthly_limit_exceeded.map((account) => (
                                                <tr key={account.bank_account_id} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{account.account_name}</div>
                                                        <div className="text-xs text-muted-foreground">{account.bank_name}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Badge className={ratioBadge(account.usage_ratio)}>
                                                            {(account.usage_ratio || 0).toFixed(2)}
                                                        </Badge>
                                                        <div className="mt-2 text-xs text-muted-foreground">
                                                            {formatCurrency(account.usage_mzn || 0)} / {formatCurrency(account.monthly_limit_mzn || 0)}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.monthly_limit_exceeded.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No accounts over the monthly limit')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">{t('Near Threshold')}</CardTitle>
                                </CardHeader>
                                <CardContent className="overflow-y-auto max-h-[24rem] p-0">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {data.monthly_limit_near_threshold.map((account) => (
                                                <tr key={account.bank_account_id} className="border-b">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">{account.account_name}</div>
                                                        <div className="text-xs text-muted-foreground">{account.account_number}</div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Badge className={ratioBadge(account.usage_ratio)}>
                                                            {(account.usage_ratio || 0).toFixed(2)}
                                                        </Badge>
                                                        <div className="mt-2 text-xs text-muted-foreground">
                                                            {formatCurrency(account.usage_mzn || 0)} / {formatCurrency(account.monthly_limit_mzn || 0)}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.monthly_limit_near_threshold.length === 0 && (
                                                <tr>
                                                    <td className="py-8 text-center text-muted-foreground">
                                                        {t('No accounts near threshold')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">{t('Invalid Exemptions')}</CardTitle>
                            </CardHeader>
                            <CardContent className="overflow-y-auto max-h-[20rem] p-0">
                                <table className="w-full text-sm">
                                    <tbody>
                                        {data.enterprise_exemption_misconfigured.map((account) => (
                                            <tr key={`exempt-${account.bank_account_id}`} className="border-b">
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">{account.account_name}</div>
                                                    <div className="text-xs text-muted-foreground">{account.account_number}</div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="text-xs text-muted-foreground">{account.company_classification || '-'}</div>
                                                    <div className="text-xs text-muted-foreground">{account.electronic_money_account_purpose || '-'}</div>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Badge className={account.requires_attention_reason === 'company_not_medium_or_large' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}>
                                                        {account.requires_attention_reason || '-'}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                        {data.enterprise_exemption_misconfigured.length === 0 && (
                                            <tr>
                                                <td className="py-8 text-center text-muted-foreground" colSpan={3}>
                                                    {t('No invalid exemptions')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <NoRecordsFound
                        icon={FileSearch}
                        title={t('Electronic Money Compliance Report')}
                        description={t('Select a period to generate the report.')}
                        className="h-auto py-12"
                    />
                )}
            </CardContent>
        </Card>
    );
}
