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
    overtime_monthly_limit_hours: number | null;
    overtime_yearly_limit_hours: number | null;
    leave_min_notice_days: number;
    leave_max_consecutive_days: number | null;
    leave_count_non_working_days: boolean;
    leave_count_holidays: boolean;
}

interface PageProps {
    irpsTables: IrpsTable[];
    inssRates: InssRate[];
    minimumWages: MinimumWage[];
    labourPolicy: LabourPolicy;
    auth: {
        user: {
            id: number;
            permissions?: string[];
        };
    };
}

export default function MozambiquePayrollComplianceIndex() {
    const { t } = useTranslation();
    const { irpsTables, inssRates, minimumWages, labourPolicy, auth } = usePage<PageProps>().props;
    const canEdit = auth.user?.permissions?.includes('edit-payrolls') ?? false;

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
        overtime_monthly_limit_hours: labourPolicy.overtime_monthly_limit_hours?.toString() ?? '',
        overtime_yearly_limit_hours: labourPolicy.overtime_yearly_limit_hours?.toString() ?? '',
        leave_min_notice_days: labourPolicy.leave_min_notice_days?.toString() ?? '0',
        leave_max_consecutive_days: labourPolicy.leave_max_consecutive_days?.toString() ?? '',
        leave_count_non_working_days: labourPolicy.leave_count_non_working_days,
        leave_count_holidays: labourPolicy.leave_count_holidays,
    });

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
