import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { Download, RefreshCw, TriangleAlert, Layers3 } from 'lucide-react';

interface CostCenterAnalysisRow {
    cost_center_id: number;
    cost_center_code: string;
    cost_center_name: string;
    parent_cost_center_name?: string | null;
    journal_count: number;
    line_count: number;
    debit_total: number;
    credit_total: number;
    net_total: number;
    reference_breakdown: {
        reference_type: string;
        line_count: number;
        debit_total: number;
        credit_total: number;
        net_total: number;
    }[];
}

interface CostCenterAllocationGapRow {
    journal_id: number;
    journal_number: string;
    journal_date: string;
    reference_type: string;
    account_code: string;
    account_name: string;
    allocation_status: 'assigned' | 'unassigned' | 'required_missing';
    debit_amount: number;
    credit_amount: number;
    net_amount: number;
    cost_center_code?: string | null;
    cost_center_name?: string | null;
    parent_cost_center_name?: string | null;
}

interface PayrollAllocationRow {
    reference_period: string;
    payroll_title: string;
    pay_date: string;
    employee_name: string;
    employee_nuit: string;
    branch: string;
    department: string;
    designation: string;
    business_unit: string;
    worksite: string;
    cost_center_code: string;
    cost_center_name: string;
    project_name: string;
    client_name: string;
    allocation_source: string;
    allocation_minutes: number;
    gross_pay: number;
    net_pay: number;
}

interface CostCenterAnalysisPayload {
    from_date: string;
    to_date: string;
    reference_period: string;
    summary: {
        journal_lines: number;
        journals: number;
        cost_centers: number;
        assigned_lines: number;
        unassigned_lines: number;
        required_missing_lines: number;
        assigned_debit_total: number;
        assigned_credit_total: number;
        assigned_net_total: number;
        payroll_rows: number;
        payroll_cost_centers: number;
        payroll_departments: number;
        payroll_branches: number;
        payroll_projects: number;
    };
    cost_centers: CostCenterAnalysisRow[];
    required_missing_lines: CostCenterAllocationGapRow[];
    unassigned_lines: CostCenterAllocationGapRow[];
    reference_types: {
        reference_type: string;
        journal_count: number;
        line_count: number;
        assigned_lines: number;
        required_missing_lines: number;
        unassigned_lines: number;
        debit_total: number;
        credit_total: number;
        net_total: number;
    }[];
    payroll_allocations: PayrollAllocationRow[];
    payroll_summary: {
        rows: number;
        gross_pay_total: number;
        net_pay_total: number;
        cost_centers: number;
        departments: number;
        branches: number;
        projects: number;
        allocation_sources: number;
    };
}

const badgeClassMap: Record<CostCenterAllocationGapRow['allocation_status'], string> = {
    assigned: 'bg-green-100 text-green-800',
    unassigned: 'bg-slate-100 text-slate-800',
    required_missing: 'bg-red-100 text-red-800',
};

export default function CostCenterAnalysis() {
    const { t } = useTranslation();
    const currentYear = new Date().getFullYear();
    const [fromDate, setFromDate] = useState(`${currentYear}-01-01`);
    const [toDate, setToDate] = useState(new Date().toISOString().slice(0, 10));
    const [referencePeriod, setReferencePeriod] = useState(new Date().toISOString().slice(0, 7));
    const [data, setData] = useState<CostCenterAnalysisPayload | null>(null);
    const [loading, setLoading] = useState(false);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            { label: t('Journal Lines'), value: data.summary.journal_lines },
            { label: t('Assigned Lines'), value: data.summary.assigned_lines },
            { label: t('Required Missing'), value: data.summary.required_missing_lines },
            { label: t('Cost Centers'), value: data.summary.cost_centers },
            { label: t('Payroll Rows'), value: data.summary.payroll_rows },
            { label: t('Projects'), value: data.summary.payroll_projects },
        ];
    }, [data, t]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('account.reports.mozambique-cost-center-analysis'), {
                params: {
                    from_date: fromDate,
                    to_date: toDate,
                    reference_period: referencePeriod || undefined,
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
        window.location.href = route('account.reports.mozambique-cost-center-analysis.export', {
            from_date: fromDate,
            to_date: toDate,
            reference_period: referencePeriod || undefined,
        });
    };

    const combinedGaps = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            ...data.required_missing_lines,
            ...data.unassigned_lines,
        ];
    }, [data]);

    return (
        <Card className="shadow-sm">
            <CardHeader className="border-b bg-gray-50/50">
                <CardTitle className="flex items-center gap-2 text-xl">
                    <Layers3 className="h-5 w-5 text-primary" />
                    {t('Cost Center Analysis')}
                </CardTitle>
                <CardDescription>
                    {t('Validates analytical allocation by cost center and highlights accounts that require a center but are missing one.')}
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
                        <label className="mb-2 block text-sm font-medium text-gray-700">{t('Payroll Period')}</label>
                        <Input
                            value={referencePeriod}
                            onChange={(e) => setReferencePeriod(e.target.value)}
                            placeholder="YYYY-MM"
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

                {data && data.summary.required_missing_lines > 0 && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <div className="flex items-center gap-2 font-medium">
                            <TriangleAlert className="h-4 w-4" />
                            {t('There are journal lines posted to accounts that require a cost center but do not have one assigned.')}
                        </div>
                        <div className="mt-1">
                            {t('This is a production risk for analytical reporting and allocation control.')}
                        </div>
                    </div>
                )}

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

            <CardContent className="space-y-6 p-6">
                {data ? (
                    <>
                        <div className="rounded-lg border bg-slate-50 p-4 text-sm text-slate-700">
                            <div className="font-medium">{t('Report Period')}</div>
                            <div>
                                {formatDate(data.from_date)} {t('to')} {formatDate(data.to_date)} · {t('Payroll period')}: {data.reference_period}
                            </div>
                        </div>

                        <div className="overflow-y-auto max-h-[45vh] rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-4 py-3 text-left">{t('Cost Center')}</th>
                                        <th className="px-4 py-3 text-left">{t('Parent')}</th>
                                        <th className="px-4 py-3 text-left">{t('Journals')}</th>
                                        <th className="px-4 py-3 text-left">{t('Lines')}</th>
                                        <th className="px-4 py-3 text-left">{t('Debit')}</th>
                                        <th className="px-4 py-3 text-left">{t('Credit')}</th>
                                        <th className="px-4 py-3 text-left">{t('Net')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.cost_centers.map((row) => (
                                        <tr key={row.cost_center_id} className="border-b align-top">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.cost_center_code}</div>
                                                <div className="text-xs text-muted-foreground">{row.cost_center_name}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">{row.parent_cost_center_name || '-'}</td>
                                            <td className="px-4 py-3">{row.journal_count}</td>
                                            <td className="px-4 py-3">{row.line_count}</td>
                                            <td className="px-4 py-3">{formatCurrency(row.debit_total)}</td>
                                            <td className="px-4 py-3">{formatCurrency(row.credit_total)}</td>
                                            <td className="px-4 py-3 font-medium">{formatCurrency(row.net_total)}</td>
                                        </tr>
                                    ))}
                                    {data.cost_centers.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="py-8 text-center text-muted-foreground">
                                                {t('No cost centers with assigned journal lines found for the selected period')}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="overflow-y-auto max-h-[45vh] rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-4 py-3 text-left">{t('Journal')}</th>
                                        <th className="px-4 py-3 text-left">{t('Date')}</th>
                                        <th className="px-4 py-3 text-left">{t('Account')}</th>
                                        <th className="px-4 py-3 text-left">{t('Cost Center')}</th>
                                        <th className="px-4 py-3 text-left">{t('Status')}</th>
                                        <th className="px-4 py-3 text-left">{t('Amount')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {combinedGaps.map((row) => (
                                        <tr key={`${row.journal_id}-${row.account_code}-${row.allocation_status}`} className="border-b align-top">
                                            <td className="px-4 py-3 font-mono text-xs">{row.journal_number}</td>
                                            <td className="px-4 py-3">{row.journal_date ? formatDate(row.journal_date) : '-'}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.account_code}</div>
                                                <div className="text-xs text-muted-foreground">{row.account_name}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.cost_center_code || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{row.cost_center_name || row.parent_cost_center_name || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={badgeClassMap[row.allocation_status]}>
                                                    {t(row.allocation_status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">{formatCurrency(row.debit_amount - row.credit_amount)}</td>
                                        </tr>
                                    ))}
                                    {combinedGaps.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="py-8 text-center text-muted-foreground">
                                                {t('No allocation gaps detected')}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="overflow-y-auto max-h-[45vh] rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-gray-50">
                                        <th className="px-4 py-3 text-left">{t('Employee')}</th>
                                        <th className="px-4 py-3 text-left">{t('Branch')}</th>
                                        <th className="px-4 py-3 text-left">{t('Department')}</th>
                                        <th className="px-4 py-3 text-left">{t('Project')}</th>
                                        <th className="px-4 py-3 text-left">{t('Cost Center')}</th>
                                        <th className="px-4 py-3 text-left">{t('Source')}</th>
                                        <th className="px-4 py-3 text-left">{t('Gross')}</th>
                                        <th className="px-4 py-3 text-left">{t('Net')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.payroll_allocations.map((row, index) => (
                                        <tr key={`${row.employee_name}-${row.payroll_title}-${index}`} className="border-b align-top">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.employee_name}</div>
                                                <div className="text-xs text-muted-foreground">{row.employee_nuit || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">{row.branch || '-'}</td>
                                            <td className="px-4 py-3">{row.department || '-'}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.project_name || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{row.client_name || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.cost_center_code || '-'}</div>
                                                <div className="text-xs text-muted-foreground">{row.cost_center_name || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">{row.allocation_source || '-'}</Badge>
                                                <div className="mt-1 text-xs text-muted-foreground">{row.designation || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">{formatCurrency(row.gross_pay)}</td>
                                            <td className="px-4 py-3">{formatCurrency(row.net_pay)}</td>
                                        </tr>
                                    ))}
                                    {data.payroll_allocations.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="py-8 text-center text-muted-foreground">
                                                {t('No payroll allocation rows found for the selected period')}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </>
                ) : (
                    <NoRecordsFound />
                )}
            </CardContent>
        </Card>
    );
}
