import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import InputError from '@/components/ui/input-error';
import SystemSetupSidebar from "../SystemSetupSidebar";
import { Trash2 } from "lucide-react";

interface IrpsBracket {
    id: number;
    range_from: number;
    range_to: number | null;
    fixed_amount: number;
    rate_percent: number;
    sequence: number;
}

interface IrpsTable {
    id: number;
    name: string;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    created_by: number | null;
    brackets: IrpsBracket[];
}

interface InssRate {
    id: number;
    employee_rate: number;
    employer_rate: number;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    created_by: number | null;
}

interface MinimumWage {
    id: number;
    sector_code: string;
    sector_name: string;
    monthly_amount: number;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    created_by: number | null;
}

interface LabourPolicy {
    overtime_daily_limit_hours: number | null;
    overtime_weekly_limit_hours: number | null;
    overtime_monthly_limit_hours: number | null;
    overtime_quarterly_limit_hours: number | null;
    overtime_yearly_limit_hours: number | null;
    leave_min_notice_days: number;
    leave_max_consecutive_days: number | null;
    leave_count_non_working_days: boolean;
    leave_count_holidays: boolean;
}

interface LegalSettings {
    foreign_quota: {
        micro_max_workers: number;
        small_max_workers: number;
        medium_max_workers: number;
        micro_quota_percent: number;
        small_quota_percent: number;
        medium_quota_percent: number;
        large_quota_percent: number;
    };
    probation_limits_days: {
        base_indefinite: number;
        general: number;
        technician_mid: number;
        technician_high: number;
        leadership: number;
    };
    probation_alert_days: {
        primary: number;
        secondary: number;
    };
}

interface ComplianceAlertItem {
    key: string;
    label: string;
    count: number;
    severity: 'high' | 'medium';
}

interface ComplianceSnapshot {
    generated_at: string;
    metrics: {
        total_workers: number;
        triggered_alerts: number;
        high_alerts: number;
        medium_alerts: number;
    };
    items: ComplianceAlertItem[];
    quota: {
        employer_type: string;
        max_percentage: number;
        total_workers: number;
        quota_slots: number;
        current_foreign_workers: number;
        remaining_slots: number;
        is_exceeded: boolean;
    };
    payroll_obligations?: PayrollObligations;
    compliance_panel?: {
        generated_at: string;
        indicators: Array<{
            key: string;
            label: string;
            count: number;
            severity: 'high' | 'medium';
            status: 'ok' | 'attention' | 'high_risk';
        }>;
        metrics: {
            total_indicators: number;
            triggered_indicators: number;
            high_risk_indicators: number;
            attention_indicators: number;
            ok_indicators: number;
        };
    };
}

interface ComplianceAlertsState {
    synced_at: string;
    source_generated_at?: string | null;
    metrics: {
        open_alerts: number;
        open_high_alerts: number;
        open_medium_alerts: number;
        resolved_alerts: number;
    };
}

type ObligationStatus = 'not_applicable' | 'pending' | 'overdue' | 'completed';

interface PayrollObligationRow {
    reference_period: string;
    month_label: string;
    payroll_runs: number;
    employee_count: number;
    total_gross_pay: number;
    total_irps: number;
    total_inss_employee: number;
    total_inss_employer: number;
    total_inss: number;
    has_completed_payroll: boolean;
    inss_due_date: string | null;
    irps_due_date: string | null;
    inss_status: ObligationStatus;
    irps_status: ObligationStatus;
}

interface PayrollObligations {
    generated_at: string;
    records: PayrollObligationRow[];
    totals: {
        applicable_periods: number;
        overdue_inss: number;
        pending_inss: number;
        completed_inss: number;
        overdue_irps: number;
        pending_irps: number;
        completed_irps: number;
        total_irps: number;
        total_inss_employee: number;
        total_inss_employer: number;
        total_inss: number;
    };
}

interface CostCenterOption {
    id: number;
    code: string;
    name: string;
}

interface MappingSourceOption {
    id: number;
    name: string;
}

interface CostCenterMappingConfig {
    mode: 'configured' | 'configured_with_heuristic';
    mappings: {
        employee: Record<string, number>;
        department: Record<string, number>;
        branch: Record<string, number>;
    };
}

interface PageProps {
    irpsTables: IrpsTable[];
    inssRates: InssRate[];
    minimumWages: MinimumWage[];
    labourPolicy: LabourPolicy;
    legalSettings: LegalSettings;
    complianceSnapshot: ComplianceSnapshot;
    complianceAlerts?: ComplianceAlertsState;
    costCenters: CostCenterOption[];
    departments: MappingSourceOption[];
    branches: MappingSourceOption[];
    employees: MappingSourceOption[];
    costCenterMappingConfig: CostCenterMappingConfig;
    auth: {
        user: {
            id: number;
            permissions?: string[];
        };
    };
}

export default function MozambiquePayrollComplianceIndex() {
    const { t } = useTranslation();
    const { irpsTables, inssRates, minimumWages, labourPolicy, legalSettings, complianceSnapshot, complianceAlerts, costCenters, departments, branches, employees, costCenterMappingConfig, auth } = usePage<PageProps>().props;
    const canEdit = auth.user?.permissions?.includes('edit-payrolls') ?? false;
    const triggeredItems = (complianceSnapshot?.items ?? []).filter((item) => item.count > 0);
    const payrollObligations = complianceSnapshot?.payroll_obligations;
    const payrollObligationRows = payrollObligations?.records ?? [];
    const complianceSyncedAt = complianceAlerts?.synced_at ?? complianceSnapshot?.generated_at;
    const compliancePanel = complianceSnapshot?.compliance_panel;
    const compliancePanelIndicators = compliancePanel?.indicators ?? [];
    const emptyMappings = { employee: {}, department: {}, branch: {} } as CostCenterMappingConfig['mappings'];

    const formatMoney = (value: number) =>
        new Intl.NumberFormat('pt-MZ', {
            style: 'currency',
            currency: 'MZN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(value) ? value : 0);

    const formatDateTime = (value?: string | null): string => {
        if (!value) {
            return '-';
        }

        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleString('pt-MZ');
    };

    const obligationStatusLabel = (status: ObligationStatus): string => {
        switch (status) {
            case 'completed':
                return t('Completed');
            case 'overdue':
                return t('Overdue');
            case 'pending':
                return t('Pending');
            default:
                return t('N/A');
        }
    };

    const obligationStatusBadge = (status: ObligationStatus): string => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-700';
            case 'overdue':
                return 'bg-red-100 text-red-700';
            case 'pending':
                return 'bg-yellow-100 text-yellow-700';
            default:
                return 'bg-slate-100 text-slate-700';
        }
    };

    const legalError = (key: string): string | undefined =>
        (legalSettingsForm.errors as Record<string, string | undefined>)[key];

    const irpsTableForm = useForm({
        name: '',
        effective_from: '',
        effective_to: '',
        is_active: true,
    });

    const irpsBracketForm = useForm({
        irps_table_id: '',
        sequence: '',
        range_from: '',
        range_to: '',
        fixed_amount: '',
        rate_percent: '',
    });

    const inssRateForm = useForm({
        employee_rate: '3',
        employer_rate: '4',
        effective_from: '',
        effective_to: '',
        is_active: true,
    });

    const minimumWageForm = useForm({
        sector_code: 'GENERAL',
        sector_name: 'General',
        monthly_amount: '',
        effective_from: '',
        effective_to: '',
        is_active: true,
    });

    const labourPolicyForm = useForm({
        overtime_daily_limit_hours: labourPolicy.overtime_daily_limit_hours?.toString() ?? '',
        overtime_weekly_limit_hours: labourPolicy.overtime_weekly_limit_hours?.toString() ?? '',
        overtime_monthly_limit_hours: labourPolicy.overtime_monthly_limit_hours?.toString() ?? '',
        overtime_quarterly_limit_hours: labourPolicy.overtime_quarterly_limit_hours?.toString() ?? '',
        overtime_yearly_limit_hours: labourPolicy.overtime_yearly_limit_hours?.toString() ?? '',
        leave_min_notice_days: labourPolicy.leave_min_notice_days?.toString() ?? '0',
        leave_max_consecutive_days: labourPolicy.leave_max_consecutive_days?.toString() ?? '',
        leave_count_non_working_days: labourPolicy.leave_count_non_working_days,
        leave_count_holidays: labourPolicy.leave_count_holidays,
    });

    const legalSettingsForm = useForm<LegalSettings>({
        foreign_quota: { ...legalSettings.foreign_quota },
        probation_limits_days: { ...legalSettings.probation_limits_days },
        probation_alert_days: { ...legalSettings.probation_alert_days },
    });

    const workforceImportForm = useForm<{ csv_file: File | null }>({
        csv_file: null,
    });

    const costCenterMappingForm = useForm<CostCenterMappingConfig>({
        mode: costCenterMappingConfig?.mode ?? 'configured_with_heuristic',
        mappings: {
            employee: { ...(costCenterMappingConfig?.mappings?.employee ?? emptyMappings.employee) },
            department: { ...(costCenterMappingConfig?.mappings?.department ?? emptyMappings.department) },
            branch: { ...(costCenterMappingConfig?.mappings?.branch ?? emptyMappings.branch) },
        },
    });

    const setMappingValue = (type: 'employee' | 'department' | 'branch', sourceId: number, value: string) => {
        const nextMappings = {
            ...costCenterMappingForm.data.mappings,
            [type]: { ...costCenterMappingForm.data.mappings[type] },
        };

        if (value === '') {
            delete nextMappings[type][String(sourceId)];
        } else {
            nextMappings[type][String(sourceId)] = Number(value);
        }

        costCenterMappingForm.setData('mappings', nextMappings);
    };

    const mappingValue = (type: 'employee' | 'department' | 'branch', sourceId: number): string => {
        const value = costCenterMappingForm.data.mappings[type][String(sourceId)];
        return value ? String(value) : '';
    };

    const setForeignQuotaField = (field: keyof LegalSettings['foreign_quota'], value: string) => {
        legalSettingsForm.setData('foreign_quota', {
            ...legalSettingsForm.data.foreign_quota,
            [field]: Number(value),
        });
    };

    const setProbationLimitField = (field: keyof LegalSettings['probation_limits_days'], value: string) => {
        legalSettingsForm.setData('probation_limits_days', {
            ...legalSettingsForm.data.probation_limits_days,
            [field]: Number(value),
        });
    };

    const setProbationAlertField = (field: keyof LegalSettings['probation_alert_days'], value: string) => {
        legalSettingsForm.setData('probation_alert_days', {
            ...legalSettingsForm.data.probation_alert_days,
            [field]: Number(value),
        });
    };

    const handleDelete = (routeName: string, id: number) => {
        router.delete(route(routeName, id), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                { label: t('System Setup') },
                { label: t('Mozambique Payroll Compliance') },
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Mozambique Payroll Compliance')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="mozambique-payroll-compliance" />
                </div>

                <div className="flex-1 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Labour Compliance Dashboard')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Total Workers')}</p>
                                    <p className="text-xl font-semibold">{complianceSnapshot?.metrics?.total_workers ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Triggered Alerts')}</p>
                                    <p className="text-xl font-semibold">{complianceSnapshot?.metrics?.triggered_alerts ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('High Alerts')}</p>
                                    <p className="text-xl font-semibold text-red-600">{complianceSnapshot?.metrics?.high_alerts ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Medium Alerts')}</p>
                                    <p className="text-xl font-semibold text-yellow-600">{complianceSnapshot?.metrics?.medium_alerts ?? 0}</p>
                                </div>
                            </div>

                            <p className="text-xs text-muted-foreground">
                                {t('Last alert sync')}: {formatDateTime(complianceSyncedAt)}
                            </p>

                            <div className="rounded-lg border p-3 text-sm">
                                <p className="font-medium">{t('Foreign Quota')}</p>
                                <p className="text-muted-foreground">
                                    {t('Type')}: {complianceSnapshot?.quota?.employer_type ?? '-'} · {t('Used')}: {complianceSnapshot?.quota?.current_foreign_workers ?? 0}/{complianceSnapshot?.quota?.quota_slots ?? 0} · {t('Remaining')}: {complianceSnapshot?.quota?.remaining_slots ?? 0}
                                </p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Indicator')}</th>
                                            <th className="text-left py-2">{t('Severity')}</th>
                                            <th className="text-left py-2">{t('Count')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {triggeredItems.length === 0 && (
                                            <tr>
                                                <td colSpan={3} className="py-3 text-muted-foreground">
                                                    {t('No active compliance alerts.')}
                                                </td>
                                            </tr>
                                        )}
                                        {triggeredItems.map((item) => (
                                            <tr key={item.key} className="border-b">
                                                <td className="py-2">{item.label}</td>
                                                <td className="py-2">
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${item.severity === 'high' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'}`}>
                                                        {item.severity === 'high' ? t('High') : t('Medium')}
                                                    </span>
                                                </td>
                                                <td className="py-2 font-semibold">{item.count}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Labour Compliance Risk Panel')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Triggered Indicators')}</p>
                                    <p className="text-xl font-semibold">{compliancePanel?.metrics?.triggered_indicators ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('High Risk Indicators')}</p>
                                    <p className="text-xl font-semibold text-red-600">{compliancePanel?.metrics?.high_risk_indicators ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Attention Indicators')}</p>
                                    <p className="text-xl font-semibold text-yellow-600">{compliancePanel?.metrics?.attention_indicators ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Indicators OK')}</p>
                                    <p className="text-xl font-semibold text-green-600">{compliancePanel?.metrics?.ok_indicators ?? 0}</p>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Indicator')}</th>
                                            <th className="text-left py-2">{t('Risk')}</th>
                                            <th className="text-left py-2">{t('Count')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {compliancePanelIndicators.map((indicator) => (
                                            <tr key={indicator.key} className="border-b">
                                                <td className="py-2">{indicator.label}</td>
                                                <td className="py-2">
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                            indicator.status === 'high_risk'
                                                                ? 'bg-red-100 text-red-700'
                                                                : indicator.status === 'attention'
                                                                  ? 'bg-yellow-100 text-yellow-700'
                                                                  : 'bg-green-100 text-green-700'
                                                        }`}
                                                    >
                                                        {indicator.status === 'high_risk'
                                                            ? t('High Risk')
                                                            : indicator.status === 'attention'
                                                              ? t('Attention')
                                                              : t('OK')}
                                                    </span>
                                                </td>
                                                <td className="py-2 font-semibold">{indicator.count}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <CardTitle>{t('Payroll Legal Obligations (INSS / IRPS)')}</CardTitle>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        window.open(
                                            route('hrm.mozambique-payroll-compliance.reports.workforce-register.export'),
                                            '_blank'
                                        )
                                    }
                                >
                                    {t('Workforce Register')}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        window.open(
                                            route('hrm.mozambique-payroll-compliance.reports.expatriates.export'),
                                            '_blank'
                                        )
                                    }
                                >
                                    {t('Expatriates Report')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    workforceImportForm.post(
                                        route('hrm.mozambique-payroll-compliance.reports.workforce-register.import'),
                                        {
                                            forceFormData: true,
                                            preserveScroll: true,
                                            onSuccess: () => workforceImportForm.reset('csv_file'),
                                        }
                                    );
                                }}
                            >
                                <div>
                                    <Label>{t('Import Workforce Register (CSV)')}</Label>
                                    <Input
                                        type="file"
                                        accept=".csv,text/csv"
                                        onChange={(e) => workforceImportForm.setData('csv_file', e.target.files?.[0] ?? null)}
                                    />
                                    <InputError message={workforceImportForm.errors.csv_file} />
                                </div>
                                <Button type="submit" disabled={!canEdit || workforceImportForm.processing || !workforceImportForm.data.csv_file}>
                                    {t('Import CSV')}
                                </Button>
                            </form>

                            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Overdue INSS Submissions')}</p>
                                    <p className="text-xl font-semibold text-red-600">{payrollObligations?.totals?.overdue_inss ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('Overdue IRPS Submissions')}</p>
                                    <p className="text-xl font-semibold text-red-600">{payrollObligations?.totals?.overdue_irps ?? 0}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('INSS Amount (6 months)')}</p>
                                    <p className="text-xl font-semibold">{formatMoney(payrollObligations?.totals?.total_inss ?? 0)}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">{t('IRPS Amount (6 months)')}</p>
                                    <p className="text-xl font-semibold">{formatMoney(payrollObligations?.totals?.total_irps ?? 0)}</p>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Reference')}</th>
                                            <th className="text-left py-2">{t('Payroll Runs')}</th>
                                            <th className="text-left py-2">{t('INSS Due')}</th>
                                            <th className="text-left py-2">{t('INSS Status')}</th>
                                            <th className="text-left py-2">{t('IRPS Due')}</th>
                                            <th className="text-left py-2">{t('IRPS Status')}</th>
                                            <th className="text-left py-2">{t('IRPS Total')}</th>
                                            <th className="text-left py-2">{t('INSS Total')}</th>
                                            <th className="text-left py-2">{t('Reports')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payrollObligationRows.map((row) => (
                                            <tr key={row.reference_period} className="border-b">
                                                <td className="py-2">{row.reference_period}</td>
                                                <td className="py-2">{row.payroll_runs}</td>
                                                <td className="py-2">{row.inss_due_date ?? '-'}</td>
                                                <td className="py-2">
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${obligationStatusBadge(row.inss_status)}`}>
                                                        {obligationStatusLabel(row.inss_status)}
                                                    </span>
                                                </td>
                                                <td className="py-2">{row.irps_due_date ?? '-'}</td>
                                                <td className="py-2">
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${obligationStatusBadge(row.irps_status)}`}>
                                                        {obligationStatusLabel(row.irps_status)}
                                                    </span>
                                                </td>
                                                <td className="py-2">{formatMoney(row.total_irps ?? 0)}</td>
                                                <td className="py-2">{formatMoney(row.total_inss ?? 0)}</td>
                                                <td className="py-2">
                                                    {row.has_completed_payroll ? (
                                                        <div className="flex flex-wrap gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.modelo19-support.export', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Modelo 19')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.inss-guide.export', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('INSS Guide')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.bank-payment-file.export', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Bank Payment File')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.cost-allocation.export', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Cost Allocation')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.export', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Journal Lines')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.cost-allocation.json', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Cost JSON')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.cost-allocation.xml', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Cost XML')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.json', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Journal JSON')}
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    window.open(
                                                                        route('hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.xml', {
                                                                            reference_period: row.reference_period,
                                                                        }),
                                                                        '_blank'
                                                                    )
                                                                }
                                                            >
                                                                {t('Journal XML')}
                                                            </Button>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Payroll Cost Center Mapping')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="space-y-4"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    costCenterMappingForm.put(route('hrm.mozambique-payroll-compliance.cost-center-mappings.update'));
                                }}
                            >
                                <div className="space-y-2 max-w-sm">
                                    <Label>{t('Mapping Mode')}</Label>
                                    <select
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={costCenterMappingForm.data.mode}
                                        onChange={(e) => costCenterMappingForm.setData('mode', e.target.value as CostCenterMappingConfig['mode'])}
                                        disabled={!canEdit}
                                    >
                                        <option value="configured">{t('Configured Only (Strict)')}</option>
                                        <option value="configured_with_heuristic">{t('Configured + Heuristic Fallback')}</option>
                                    </select>
                                    <p className="text-xs text-muted-foreground">
                                        {t('Strict mode enforces explicit mapping and does not auto-infer cost center by department/branch names.')}
                                    </p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="text-left py-2">{t('Source Type')}</th>
                                                <th className="text-left py-2">{t('Source')}</th>
                                                <th className="text-left py-2">{t('Cost Center')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {departments.map((department) => (
                                                <tr key={`dep-${department.id}`} className="border-b">
                                                    <td className="py-2">{t('Department')}</td>
                                                    <td className="py-2">{department.name}</td>
                                                    <td className="py-2">
                                                        <select
                                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                            value={mappingValue('department', department.id)}
                                                            onChange={(e) => setMappingValue('department', department.id, e.target.value)}
                                                            disabled={!canEdit}
                                                        >
                                                            <option value="">{t('Not mapped')}</option>
                                                            {costCenters.map((center) => (
                                                                <option key={`dep-${department.id}-cc-${center.id}`} value={center.id}>
                                                                    {center.code} - {center.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                </tr>
                                            ))}
                                            {branches.map((branch) => (
                                                <tr key={`branch-${branch.id}`} className="border-b">
                                                    <td className="py-2">{t('Branch')}</td>
                                                    <td className="py-2">{branch.name}</td>
                                                    <td className="py-2">
                                                        <select
                                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                            value={mappingValue('branch', branch.id)}
                                                            onChange={(e) => setMappingValue('branch', branch.id, e.target.value)}
                                                            disabled={!canEdit}
                                                        >
                                                            <option value="">{t('Not mapped')}</option>
                                                            {costCenters.map((center) => (
                                                                <option key={`branch-${branch.id}-cc-${center.id}`} value={center.id}>
                                                                    {center.code} - {center.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                </tr>
                                            ))}
                                            {employees.map((employee) => (
                                                <tr key={`emp-${employee.id}`} className="border-b">
                                                    <td className="py-2">{t('Employee')}</td>
                                                    <td className="py-2">{employee.name}</td>
                                                    <td className="py-2">
                                                        <select
                                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                            value={mappingValue('employee', employee.id)}
                                                            onChange={(e) => setMappingValue('employee', employee.id, e.target.value)}
                                                            disabled={!canEdit}
                                                        >
                                                            <option value="">{t('Not mapped')}</option>
                                                            {costCenters.map((center) => (
                                                                <option key={`emp-${employee.id}-cc-${center.id}`} value={center.id}>
                                                                    {center.code} - {center.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {canEdit && (
                                    <Button type="submit" disabled={costCenterMappingForm.processing}>
                                        {t('Save Cost Center Mapping')}
                                    </Button>
                                )}
                                <InputError message={costCenterMappingForm.errors.mode} />
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('IRPS Tables')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-4 gap-3"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    irpsTableForm.post(route('hrm.mozambique-payroll-compliance.irps-tables.store'));
                                }}
                            >
                                <div>
                                    <Label>{t('Name')}</Label>
                                    <Input value={irpsTableForm.data.name} onChange={(e) => irpsTableForm.setData('name', e.target.value)} />
                                    <InputError message={irpsTableForm.errors.name} />
                                </div>
                                <div>
                                    <Label>{t('Effective From')}</Label>
                                    <Input type="date" value={irpsTableForm.data.effective_from} onChange={(e) => irpsTableForm.setData('effective_from', e.target.value)} />
                                    <InputError message={irpsTableForm.errors.effective_from} />
                                </div>
                                <div>
                                    <Label>{t('Effective To')}</Label>
                                    <Input type="date" value={irpsTableForm.data.effective_to} onChange={(e) => irpsTableForm.setData('effective_to', e.target.value)} />
                                    <InputError message={irpsTableForm.errors.effective_to} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" disabled={!canEdit || irpsTableForm.processing} className="w-full">
                                        {t('Add')}
                                    </Button>
                                </div>
                            </form>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Name')}</th>
                                            <th className="text-left py-2">{t('Effective From')}</th>
                                            <th className="text-left py-2">{t('Effective To')}</th>
                                            <th className="text-left py-2">{t('Brackets')}</th>
                                            <th className="text-left py-2">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {irpsTables.map((table) => (
                                            <tr key={table.id} className="border-b align-top">
                                                <td className="py-2">{table.name}</td>
                                                <td className="py-2">{table.effective_from}</td>
                                                <td className="py-2">{table.effective_to || '-'}</td>
                                                <td className="py-2">
                                                    <div className="space-y-1">
                                                        {table.brackets.map((bracket) => (
                                                            <div key={bracket.id} className="text-xs flex items-center justify-between gap-2">
                                                                <span>{`#${bracket.sequence} | ${bracket.range_from} - ${bracket.range_to ?? '∞'} | ${bracket.fixed_amount} + ${bracket.rate_percent}%`}</span>
                                                                {canEdit && table.created_by !== null && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => handleDelete('hrm.mozambique-payroll-compliance.irps-brackets.destroy', bracket.id)}
                                                                        className="h-6 w-6 p-0"
                                                                    >
                                                                        <Trash2 className="h-3 w-3 text-red-600" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="py-2">
                                                    {canEdit && table.created_by !== null && (
                                                        <Button variant="ghost" size="sm" onClick={() => handleDelete('hrm.mozambique-payroll-compliance.irps-tables.destroy', table.id)}>
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <form
                                className="grid grid-cols-1 md:grid-cols-7 gap-3 pt-2"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    irpsBracketForm.post(route('hrm.mozambique-payroll-compliance.irps-brackets.store'));
                                }}
                            >
                                <div>
                                    <Label>{t('Table ID')}</Label>
                                    <Input value={irpsBracketForm.data.irps_table_id} onChange={(e) => irpsBracketForm.setData('irps_table_id', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Seq')}</Label>
                                    <Input value={irpsBracketForm.data.sequence} onChange={(e) => irpsBracketForm.setData('sequence', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('From')}</Label>
                                    <Input value={irpsBracketForm.data.range_from} onChange={(e) => irpsBracketForm.setData('range_from', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('To')}</Label>
                                    <Input value={irpsBracketForm.data.range_to} onChange={(e) => irpsBracketForm.setData('range_to', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Fixed')}</Label>
                                    <Input value={irpsBracketForm.data.fixed_amount} onChange={(e) => irpsBracketForm.setData('fixed_amount', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Rate %')}</Label>
                                    <Input value={irpsBracketForm.data.rate_percent} onChange={(e) => irpsBracketForm.setData('rate_percent', e.target.value)} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" disabled={!canEdit || irpsBracketForm.processing} className="w-full">
                                        {t('Add Bracket')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('INSS Rates')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-5 gap-3"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    inssRateForm.post(route('hrm.mozambique-payroll-compliance.inss-rates.store'));
                                }}
                            >
                                <div>
                                    <Label>{t('Employee Rate %')}</Label>
                                    <Input value={inssRateForm.data.employee_rate} onChange={(e) => inssRateForm.setData('employee_rate', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Employer Rate %')}</Label>
                                    <Input value={inssRateForm.data.employer_rate} onChange={(e) => inssRateForm.setData('employer_rate', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Effective From')}</Label>
                                    <Input type="date" value={inssRateForm.data.effective_from} onChange={(e) => inssRateForm.setData('effective_from', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Effective To')}</Label>
                                    <Input type="date" value={inssRateForm.data.effective_to} onChange={(e) => inssRateForm.setData('effective_to', e.target.value)} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" disabled={!canEdit || inssRateForm.processing} className="w-full">
                                        {t('Add')}
                                    </Button>
                                </div>
                            </form>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Employee %')}</th>
                                            <th className="text-left py-2">{t('Employer %')}</th>
                                            <th className="text-left py-2">{t('Effective From')}</th>
                                            <th className="text-left py-2">{t('Effective To')}</th>
                                            <th className="text-left py-2">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {inssRates.map((rate) => (
                                            <tr key={rate.id} className="border-b">
                                                <td className="py-2">{rate.employee_rate}</td>
                                                <td className="py-2">{rate.employer_rate}</td>
                                                <td className="py-2">{rate.effective_from}</td>
                                                <td className="py-2">{rate.effective_to || '-'}</td>
                                                <td className="py-2">
                                                    {canEdit && rate.created_by !== null && (
                                                        <Button variant="ghost" size="sm" onClick={() => handleDelete('hrm.mozambique-payroll-compliance.inss-rates.destroy', rate.id)}>
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Mozambique Legal Settings')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <form
                                className="space-y-6"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    legalSettingsForm.put(route('hrm.mozambique-payroll-compliance.legal-settings.update'));
                                }}
                            >
                                <div className="space-y-3">
                                    <h4 className="font-medium">{t('Foreign Worker Quota Rules')}</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <div>
                                            <Label>{t('Micro Max Workers')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.foreign_quota.micro_max_workers}
                                                onChange={(e) => setForeignQuotaField('micro_max_workers', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.micro_max_workers')} />
                                        </div>
                                        <div>
                                            <Label>{t('Small Max Workers')}</Label>
                                            <Input
                                                type="number"
                                                min="2"
                                                value={legalSettingsForm.data.foreign_quota.small_max_workers}
                                                onChange={(e) => setForeignQuotaField('small_max_workers', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.small_max_workers')} />
                                        </div>
                                        <div>
                                            <Label>{t('Medium Max Workers')}</Label>
                                            <Input
                                                type="number"
                                                min="3"
                                                value={legalSettingsForm.data.foreign_quota.medium_max_workers}
                                                onChange={(e) => setForeignQuotaField('medium_max_workers', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.medium_max_workers')} />
                                        </div>
                                        <div>
                                            <Label>{t('Micro Quota %')}</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={legalSettingsForm.data.foreign_quota.micro_quota_percent}
                                                onChange={(e) => setForeignQuotaField('micro_quota_percent', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.micro_quota_percent')} />
                                        </div>
                                        <div>
                                            <Label>{t('Small Quota %')}</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={legalSettingsForm.data.foreign_quota.small_quota_percent}
                                                onChange={(e) => setForeignQuotaField('small_quota_percent', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.small_quota_percent')} />
                                        </div>
                                        <div>
                                            <Label>{t('Medium Quota %')}</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={legalSettingsForm.data.foreign_quota.medium_quota_percent}
                                                onChange={(e) => setForeignQuotaField('medium_quota_percent', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.medium_quota_percent')} />
                                        </div>
                                        <div>
                                            <Label>{t('Large Quota %')}</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={legalSettingsForm.data.foreign_quota.large_quota_percent}
                                                onChange={(e) => setForeignQuotaField('large_quota_percent', e.target.value)}
                                            />
                                            <InputError message={legalError('foreign_quota.large_quota_percent')} />
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <h4 className="font-medium">{t('Probation Legal Limits (days)')}</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
                                        <div>
                                            <Label>{t('Base Indefinite')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_limits_days.base_indefinite}
                                                onChange={(e) => setProbationLimitField('base_indefinite', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_limits_days.base_indefinite')} />
                                        </div>
                                        <div>
                                            <Label>{t('General')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_limits_days.general}
                                                onChange={(e) => setProbationLimitField('general', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_limits_days.general')} />
                                        </div>
                                        <div>
                                            <Label>{t('Technician Mid')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_limits_days.technician_mid}
                                                onChange={(e) => setProbationLimitField('technician_mid', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_limits_days.technician_mid')} />
                                        </div>
                                        <div>
                                            <Label>{t('Technician High')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_limits_days.technician_high}
                                                onChange={(e) => setProbationLimitField('technician_high', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_limits_days.technician_high')} />
                                        </div>
                                        <div>
                                            <Label>{t('Leadership')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_limits_days.leadership}
                                                onChange={(e) => setProbationLimitField('leadership', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_limits_days.leadership')} />
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <h4 className="font-medium">{t('Probation Alert Days')}</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <Label>{t('Primary Alert (days)')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={legalSettingsForm.data.probation_alert_days.primary}
                                                onChange={(e) => setProbationAlertField('primary', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_alert_days.primary')} />
                                        </div>
                                        <div>
                                            <Label>{t('Secondary Alert (days)')}</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                value={legalSettingsForm.data.probation_alert_days.secondary}
                                                onChange={(e) => setProbationAlertField('secondary', e.target.value)}
                                            />
                                            <InputError message={legalError('probation_alert_days.secondary')} />
                                        </div>
                                        <div className="flex items-end">
                                            <Button type="submit" disabled={!canEdit || legalSettingsForm.processing} className="w-full">
                                                {t('Save Legal Settings')}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Overtime & Leave Rules')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-4 gap-3"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    labourPolicyForm.put(route('hrm.mozambique-payroll-compliance.labour-policy.update'));
                                }}
                            >
                                <div>
                                    <Label>{t('Daily Overtime Limit (hours)')}</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={labourPolicyForm.data.overtime_daily_limit_hours}
                                        onChange={(e) => labourPolicyForm.setData('overtime_daily_limit_hours', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.overtime_daily_limit_hours} />
                                </div>
                                <div>
                                    <Label>{t('Weekly Overtime Limit (hours)')}</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={labourPolicyForm.data.overtime_weekly_limit_hours}
                                        onChange={(e) => labourPolicyForm.setData('overtime_weekly_limit_hours', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.overtime_weekly_limit_hours} />
                                </div>
                                <div>
                                    <Label>{t('Monthly Overtime Limit (hours)')}</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={labourPolicyForm.data.overtime_monthly_limit_hours}
                                        onChange={(e) => labourPolicyForm.setData('overtime_monthly_limit_hours', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.overtime_monthly_limit_hours} />
                                </div>
                                <div>
                                    <Label>{t('Quarterly Overtime Limit (hours)')}</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={labourPolicyForm.data.overtime_quarterly_limit_hours}
                                        onChange={(e) => labourPolicyForm.setData('overtime_quarterly_limit_hours', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.overtime_quarterly_limit_hours} />
                                </div>
                                <div>
                                    <Label>{t('Yearly Overtime Limit (hours)')}</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        min="0"
                                        value={labourPolicyForm.data.overtime_yearly_limit_hours}
                                        onChange={(e) => labourPolicyForm.setData('overtime_yearly_limit_hours', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.overtime_yearly_limit_hours} />
                                </div>
                                <div>
                                    <Label>{t('Leave Minimum Notice (days)')}</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={labourPolicyForm.data.leave_min_notice_days}
                                        onChange={(e) => labourPolicyForm.setData('leave_min_notice_days', e.target.value)}
                                    />
                                    <InputError message={labourPolicyForm.errors.leave_min_notice_days} />
                                </div>
                                <div>
                                    <Label>{t('Max Consecutive Leave Days')}</Label>
                                    <Input
                                        type="number"
                                        min="1"
                                        value={labourPolicyForm.data.leave_max_consecutive_days}
                                        onChange={(e) => labourPolicyForm.setData('leave_max_consecutive_days', e.target.value)}
                                        placeholder={t('Disabled when empty')}
                                    />
                                    <InputError message={labourPolicyForm.errors.leave_max_consecutive_days} />
                                </div>
                                <div className="flex items-end">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={labourPolicyForm.data.leave_count_non_working_days}
                                            onChange={(e) => labourPolicyForm.setData('leave_count_non_working_days', e.target.checked)}
                                        />
                                        {t('Count Non-Working Days')}
                                    </label>
                                </div>
                                <div className="flex items-end">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={labourPolicyForm.data.leave_count_holidays}
                                            onChange={(e) => labourPolicyForm.setData('leave_count_holidays', e.target.checked)}
                                        />
                                        {t('Count Holidays')}
                                    </label>
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" disabled={!canEdit || labourPolicyForm.processing} className="w-full">
                                        {t('Save Rules')}
                                    </Button>
                                </div>
                            </form>
                            <p className="text-xs text-muted-foreground">
                                {t('Empty overtime limits are treated as disabled. Leave day counting follows the selected checkboxes.')}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Minimum Wages')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 md:grid-cols-6 gap-3"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    minimumWageForm.post(route('hrm.mozambique-payroll-compliance.minimum-wages.store'));
                                }}
                            >
                                <div>
                                    <Label>{t('Sector Code')}</Label>
                                    <Input value={minimumWageForm.data.sector_code} onChange={(e) => minimumWageForm.setData('sector_code', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Sector Name')}</Label>
                                    <Input value={minimumWageForm.data.sector_name} onChange={(e) => minimumWageForm.setData('sector_name', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Amount')}</Label>
                                    <Input value={minimumWageForm.data.monthly_amount} onChange={(e) => minimumWageForm.setData('monthly_amount', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Effective From')}</Label>
                                    <Input type="date" value={minimumWageForm.data.effective_from} onChange={(e) => minimumWageForm.setData('effective_from', e.target.value)} />
                                </div>
                                <div>
                                    <Label>{t('Effective To')}</Label>
                                    <Input type="date" value={minimumWageForm.data.effective_to} onChange={(e) => minimumWageForm.setData('effective_to', e.target.value)} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" disabled={!canEdit || minimumWageForm.processing} className="w-full">
                                        {t('Add')}
                                    </Button>
                                </div>
                            </form>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">{t('Code')}</th>
                                            <th className="text-left py-2">{t('Sector')}</th>
                                            <th className="text-left py-2">{t('Amount')}</th>
                                            <th className="text-left py-2">{t('Effective From')}</th>
                                            <th className="text-left py-2">{t('Effective To')}</th>
                                            <th className="text-left py-2">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {minimumWages.map((wage) => (
                                            <tr key={wage.id} className="border-b">
                                                <td className="py-2">{wage.sector_code}</td>
                                                <td className="py-2">{wage.sector_name}</td>
                                                <td className="py-2">{wage.monthly_amount}</td>
                                                <td className="py-2">{wage.effective_from}</td>
                                                <td className="py-2">{wage.effective_to || '-'}</td>
                                                <td className="py-2">
                                                    {canEdit && wage.created_by !== null && (
                                                        <Button variant="ghost" size="sm" onClick={() => handleDelete('hrm.mozambique-payroll-compliance.minimum-wages.destroy', wage.id)}>
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
