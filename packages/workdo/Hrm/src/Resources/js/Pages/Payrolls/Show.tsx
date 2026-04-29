import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { PayslipModal } from './payslip/PayslipModal';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Calculator, Users, DollarSign, Calendar, Download, Eye, Trash2, CreditCard, FileDown } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { formatDate, formatCurrency } from '@/utils/helpers';

interface PayrollEntry {
    id: number;
    employee: {
        id: number;
        name: string;
        email: string;
        user: {
            name: string;
            email: string;
        };
    };
    basic_salary: number;
    total_allowances: number;
    total_manual_overtimes: number;
    total_deductions: number;
    total_loans: number;
    gross_pay: number;
    net_pay: number;
    attendance_overtime_amount: number;
    working_days: number;
    present_days: number;
    absent_days: number;
    paid_leave_days: number;
    unpaid_leave_days: number;
    overtime_hours: number;
    allowances_breakdown: Record<string, number>;
    deductions_breakdown: Record<string, number>;
    manual_overtimes_breakdown: Record<string, number>;
    loans_breakdown: Record<string, number>;
}

interface Payroll {
    id: number;
    title: string;
    payroll_frequency: string;
    pay_period_start: string;
    pay_period_end: string;
    pay_date: string;
    status: string;
    total_gross_pay: number;
    total_deductions: number;
    total_net_pay: number;
    total_irps?: number;
    total_inss_employee?: number;
    total_inss_employer?: number;
    employee_count: number;
    payroll_entries: PayrollEntry[];
}

interface ShowProps {
    payroll: Payroll;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Show() {
    const { t } = useTranslation();
    const { payroll, auth } = usePage<ShowProps>().props;
    const [selectedPayrollEntry, setSelectedPayrollEntry] = useState<PayrollEntry | null>(null);
    const [isPayslipModalOpen, setIsPayslipModalOpen] = useState(false);
    

    const openPayslipModal = (entry: PayrollEntry) => {
        setSelectedPayrollEntry(entry);
        setIsPayslipModalOpen(true);
    };

    const closePayslipModal = () => {
        setIsPayslipModalOpen(false);
        setSelectedPayrollEntry(null);
    };

    const handlePayment = (entryId: number) => {
        router.patch(route('hrm.payroll-entries.pay', entryId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be handled by flash messages
            }
        });
    };
    
    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.payroll-entries.destroy',
        defaultMessage: t('Are you sure you want to delete this payroll entry? This will remove the salary calculation for this employee.')
    });

    const getStatusColor = (status: string) => {
        const colors = {
            draft: 'bg-yellow-100 text-yellow-800',
            processing: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800'
        };
        return colors[status as keyof typeof colors] || colors.draft;
    };

    const tableColumns = [
        {
            key: 'employee_name',
            header: t('Employee'),
            render: (_: any, entry: PayrollEntry) => (
                <div>
                    <div className="font-medium">{entry.employee?.user?.name || entry.employee?.name}</div>
                    <div className="text-sm text-gray-500">{entry.employee?.user?.email || entry.employee?.email}</div>
                </div>
            )
        },
        {
            key: 'basic_salary',
            header: t('Basic Salary'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_allowances',
            header: t('Allowances'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_manual_overtimes',
            header: t('Manual OT'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'attendance_overtime_amount',
            header: t('Attendance OT'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_deductions',
            header: t('Deductions'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_loans',
            header: t('Loans'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'gross_pay',
            header: t('Gross Pay'),
            render: (value: number) => (
                <span className="font-medium text-green-600">{formatCurrency(value)}</span>
            )
        },
        {
            key: 'net_pay',
            header: t('Net Pay'),
            render: (value: number) => (
                <span className="font-bold text-blue-600">{formatCurrency(value)}</span>
            )
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    'paid': 'bg-green-100 text-green-800',
                    'unpaid': 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || statusColors.unpaid}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Unpaid')}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['pay-payslip', 'download-payslip', 'view-payslip', 'delete-payslip'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, entry: PayrollEntry) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('pay-payslip') && entry.status !== 'paid' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => handlePayment(entry.id)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <CreditCard className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Pay Salary')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('download-payslip') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => window.open(route('hrm.payroll-entries.print', entry.id) + '?download=pdf', '_blank')} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                        <Download className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Download Payslip')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-payslip') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openPayslipModal(entry)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View Payslip')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-payslip') && entry.status !== 'paid' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(entry.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Delete Payslip')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                { label: t('Payrolls'), url: route('hrm.payrolls.index') },
                { label: payroll.title }
            ]}
            pageTitle={t('Payroll Details')}
            backUrl={route('hrm.payrolls.index')}
        >
            <Head title={`${t('Payroll')} - ${payroll.title}`} />

            <div className="space-y-8">
                {/* Payroll Summary */}
                <Card className="shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="p-3 bg-primary/10 rounded-lg">
                                    <Calculator className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-1xl font-bold text-gray-900">{payroll.title}</CardTitle>
                                    <div className="flex items-center gap-6 mt-2">
                                        <p className="text-base text-gray-600">
                                            {formatDate(payroll.pay_period_start)} - {formatDate(payroll.pay_period_end)}
                                        </p>
                                        <div className="flex items-center gap-4 text-sm text-gray-600">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-gray-500" />
                                                <span className="font-medium">{t('Pay Date')}:</span>
                                                <span>{formatDate(payroll.pay_date)}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{t('Frequency')}:</span>
                                                <span>{t(payroll.payroll_frequency?.charAt(0).toUpperCase() + payroll.payroll_frequency?.slice(1))}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                payroll.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                payroll.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                                payroll.status === 'completed' ? 'bg-green-100 text-green-800' :
                                payroll.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                'bg-yellow-100 text-yellow-800'
                            }`}>
                                {t(payroll.status?.charAt(0).toUpperCase() + payroll.status?.slice(1) || 'Draft')}
                            </span>
                        </div>
                        <div className="pt-3">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => window.open(route('hrm.payrolls.mozambique-map', payroll.id), '_blank')}
                                className="flex items-center gap-2"
                            >
                                <FileDown className="h-4 w-4" />
                                {t('Export Mozambique Map')}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                            <div className="flex items-center gap-4 p-5 bg-blue-50 rounded-xl border border-blue-100">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Users className="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Employees')}</p>
                                    <p className="text-2xl font-bold text-blue-600">{payroll.employee_count}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 p-5 bg-green-50 rounded-xl border border-green-100">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <DollarSign className="h-6 w-6 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Gross Pay')}</p>
                                    <p className="text-2xl font-bold text-green-600">{formatCurrency(payroll.total_gross_pay)}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 p-5 bg-red-50 rounded-xl border border-red-100">
                                <div className="p-2 bg-red-100 rounded-lg">
                                    <DollarSign className="h-6 w-6 text-red-600" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Deductions')}</p>
                                    <p className="text-2xl font-bold text-red-600">{formatCurrency(payroll.total_deductions)}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 p-5 bg-purple-50 rounded-xl border border-purple-100">
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <DollarSign className="h-6 w-6 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Net Pay')}</p>
                                    <p className="text-2xl font-bold text-purple-600">{formatCurrency(payroll.total_net_pay)}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 p-5 bg-amber-50 rounded-xl border border-amber-100">
                                <div className="p-2 bg-amber-100 rounded-lg">
                                    <DollarSign className="h-6 w-6 text-amber-700" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('IRPS')}</p>
                                    <p className="text-2xl font-bold text-amber-700">{formatCurrency(payroll.total_irps || 0)}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 p-5 bg-cyan-50 rounded-xl border border-cyan-100">
                                <div className="p-2 bg-cyan-100 rounded-lg">
                                    <DollarSign className="h-6 w-6 text-cyan-700" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('INSS Worker')}</p>
                                    <p className="text-2xl font-bold text-cyan-700">{formatCurrency(payroll.total_inss_employee || 0)}</p>
                                </div>
                            </div>
                        </div>

                    </CardContent>
                </Card>

                {/* Employee Salary Details */}
                <Card className="shadow-sm">
                    <CardHeader className="pb-6">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-gray-100 rounded-lg">
                                <Users className="h-5 w-5 text-gray-600" />
                            </div>
                            <div>
                                <CardTitle className="text-lg font-semibold text-gray-900">{t('Employee Salary Details')}</CardTitle>
                                <p className="text-sm text-gray-600 mt-1">{t('Detailed breakdown of employee salaries and deductions')}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[60vh] rounded-none w-full">
                            <div className="min-w-[1200px]">
                                <DataTable
                                    data={payroll.payroll_entries || []}
                                    columns={tableColumns}
                                    className="rounded-none"
                                    emptyState={
                                        <div className="flex flex-col items-center justify-center py-16">
                                            <div className="p-4 bg-gray-100 rounded-full mb-4">
                                                <Calculator className="h-12 w-12 text-gray-400" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900 mb-2">{t('No Salary Data')}</h3>
                                            <p className="text-gray-500 text-center max-w-md leading-relaxed">
                                                {t('No employee salary data found for this payroll. Run the payroll process to generate salary calculations.')}
                                            </p>
                                        </div>
                                    }
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Payroll Entry')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
            
            <PayslipModal
                open={isPayslipModalOpen}
                onOpenChange={closePayslipModal}
                payrollEntry={selectedPayrollEntry}
                payroll={payroll}
            />
        </AuthenticatedLayout>
    );
}
