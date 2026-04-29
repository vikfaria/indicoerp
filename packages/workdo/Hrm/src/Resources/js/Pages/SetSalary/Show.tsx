import { useState, useEffect } from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { useTranslation } from 'react-i18next';

import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { DataTable } from "@/components/ui/data-table";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DollarSign, UserIcon, Edit, Save, X, Plus, Trash2, Eye } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { ConfirmationDialog } from "@/components/ui/confirmation-dialog";
import { getImagePath, getCurrencySymbol , formatCurrency, formatDate} from '@/utils/helpers';
import CreateAllowance from './Allowances/Create';
import EditAllowance from './Allowances/Edit';
import CreateDeduction from './Deductions/Create';
import EditDeduction from './Deductions/Edit';
import CreateLoan from './Loans/Create';
import EditLoan from './Loans/Edit';
import ViewLoan from './Loans/View';
import CreateOvertime from './Overtimes/Create';
import EditOvertime from './Overtimes/Edit';
import ViewOvertime from './Overtimes/View';

interface Allowance {
    id: number;
    allowance_type_id: number;
    type: string;
    amount: number;
    allowance_type?: {
        name: string;
    };
}

interface Employee {
    id: number;
    employee_id: string;
    basic_salary?: number;
    user?: {
        id: number;
        name: string;
        avatar?: string;
    };
    branch?: {
        branch_name: string;
    };
    department?: {
        department_name: string;
    };
    designation?: {
        designation_name: string;
    };
}

interface Deduction {
    id: number;
    deduction_type_id: number;
    type: string;
    amount: number;
    deduction_type?: {
        name: string;
    };
}

interface Loan {
    id: number;
    loan_type_id: number;
    type: string;
    amount: number;
    start_date?: string;
    end_date?: string;
    loan_type?: {
        name: string;
    };
}

interface Overtime {
    id: number;
    title: string;
    total_days: number;
    hours: number;
    rate: number;
    start_date?: string;
    end_date?: string;
    notes?: string;
    status: string;
}

interface SetSalaryShowProps {
    employee: Employee;
    allowanceTypes: any[];
    allowances: Allowance[];
    deductionTypes: any[];
    deductions: Deduction[];
    loanTypes: any[];
    loans: Loan[];
    overtimes: Overtime[];
    auth: any;
}

export default function Show() {
    const { t } = useTranslation();
    const { employee, allowanceTypes, allowances: initialAllowances, deductionTypes, deductions: initialDeductions, loanTypes, loans: initialLoans, overtimes: initialOvertimes, auth } = usePage<SetSalaryShowProps>().props;
    const [isEditing, setIsEditing] = useState(false);
    const [allowances, setAllowances] = useState<Allowance[]>(initialAllowances || []);
    const [deductions, setDeductions] = useState<Deduction[]>(initialDeductions || []);
    const [loans, setLoans] = useState<Loan[]>(initialLoans || []);
    const [overtimes, setOvertimes] = useState<Overtime[]>(initialOvertimes || []);

    // Update local state when props change (after redirect)
    useEffect(() => {
        setAllowances(initialAllowances || []);
        setDeductions(initialDeductions || []);
        setLoans(initialLoans || []);
        setOvertimes(initialOvertimes || []);
    }, [initialAllowances, initialDeductions, initialLoans, initialOvertimes]);
    const [allowanceModalState, setAllowanceModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: Allowance | null;
    }>({ isOpen: false, mode: '', data: null });

    const [deductionModalState, setDeductionModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: Deduction | null;
    }>({ isOpen: false, mode: '', data: null });

    const [loanModalState, setLoanModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: Loan | null;
    }>({ isOpen: false, mode: '', data: null });

    const [overtimeModalState, setOvertimeModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: Overtime | null;
    }>({ isOpen: false, mode: '', data: null });
    


    const { data, setData, put, processing, errors } = useForm({
        basic_salary: employee.basic_salary?.toString() || '0',

    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.allowances.destroy',
        defaultMessage: t('Are you sure you want to delete this allowance?')
    });

    const { deleteState: deductionDeleteState, openDeleteDialog: openDeductionDeleteDialog, closeDeleteDialog: closeDeductionDeleteDialog, confirmDelete: confirmDeductionDelete } = useDeleteHandler({
        routeName: 'hrm.deductions.destroy',
        defaultMessage: t('Are you sure you want to delete this deduction?')
    });

    const { deleteState: loanDeleteState, openDeleteDialog: openLoanDeleteDialog, closeDeleteDialog: closeLoanDeleteDialog, confirmDelete: confirmLoanDelete } = useDeleteHandler({
        routeName: 'hrm.loans.destroy',
        defaultMessage: t('Are you sure you want to delete this loan?')
    });

    const { deleteState: overtimeDeleteState, openDeleteDialog: openOvertimeDeleteDialog, closeDeleteDialog: closeOvertimeDeleteDialog, confirmDelete: confirmOvertimeDelete } = useDeleteHandler({
        routeName: 'hrm.overtimes.destroy',
        defaultMessage: t('Are you sure you want to delete this overtime?')
    });





    const openAllowanceModal = (mode: string, data: Allowance | null = null) => {
        setAllowanceModalState({ isOpen: true, mode, data });
    };

    const closeAllowanceModal = () => {
        setAllowanceModalState({ isOpen: false, mode: '', data: null });
    };

    const openDeductionModal = (mode: string, data: Deduction | null = null) => {
        setDeductionModalState({ isOpen: true, mode, data });
    };

    const closeDeductionModal = () => {
        setDeductionModalState({ isOpen: false, mode: '', data: null });
    };

    const openLoanModal = (mode: string, data: Loan | null = null) => {
        setLoanModalState({ isOpen: true, mode, data });
    };

    const closeLoanModal = () => {
        setLoanModalState({ isOpen: false, mode: '', data: null });
    };

    const openOvertimeModal = (mode: string, data: Overtime | null = null) => {
        setOvertimeModalState({ isOpen: true, mode, data });
    };

    const closeOvertimeModal = () => {
        setOvertimeModalState({ isOpen: false, mode: '', data: null });
    };



    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.set-salary.update', employee.id), {
            onSuccess: () => {
                setIsEditing(false);
            }
        });
    };

    const cancelEdit = () => {
        setIsEditing(false);
        setData('basic_salary', employee.basic_salary?.toString() || '0');
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                { label: t('Set Salary'), url: route('hrm.set-salary.index') },
                { label: t('View Salary') }
            ]}
            pageTitle={t('Employee Salary Details')}
            backUrl={route('hrm.set-salary.index')}
        >
            <Head title={t('View Salary')} />

            {/* Employee Basic Salary Card */}
            <Card className="shadow-sm mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-3">
                        <div className="flex items-center gap-3">
                            {employee.user?.avatar ? (
                                <img
                                    src={getImagePath(employee.user.avatar)}
                                    alt="Avatar"
                                    className="w-12 h-12 rounded-full object-cover"
                                />
                            ) : (
                                <div className="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                    <UserIcon className="w-6 h-6 text-gray-400" />
                                </div>
                            )}
                            <div>
                                <h2 className="text-xl font-semibold">{employee.user?.name}</h2>
                                <p className="text-sm text-muted-foreground">{employee.employee_id}</p>
                            </div>
                        </div>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="flex items-center gap-3 p-4 bg-green-50 rounded-lg">
                            <div className="p-2 bg-green-100 rounded-lg">
                                <DollarSign className="h-6 w-6 text-green-600" />
                            </div>
                            <div className="flex-1">
                                <p className="text-sm text-muted-foreground">{t('Basic Salary')}</p>
                                {isEditing ? (
                                    <form onSubmit={handleSubmit} className="flex items-center gap-2 mt-1">
                                        <div className="flex-1">
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={data.basic_salary}
                                                onChange={(e) => setData('basic_salary', e.target.value)}
                                                className="text-lg font-bold"
                                                autoFocus
                                            />
                                            <InputError message={errors.basic_salary} />
                                        </div>
                                        <div className="flex gap-1">
                                            <TooltipProvider>
                                                <Tooltip delayDuration={0}>
                                                    <TooltipTrigger asChild>
                                                        <Button type="submit" size="sm" disabled={processing}>
                                                            <Save className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>{t('Save')}</p></TooltipContent>
                                                </Tooltip>
                                                <Tooltip delayDuration={0}>
                                                    <TooltipTrigger asChild>
                                                        <Button type="button" variant="outline" size="sm" onClick={cancelEdit}>
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>{t('Cancel')}</p></TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="flex items-center gap-2">
                                        <p className="text-2xl font-bold text-green-600">
                                            {formatCurrency(employee.basic_salary ?? 0)}
                                        </p>
                                        {auth.user?.permissions?.includes('edit-set-salary') && (
                                            <TooltipProvider>
                                                <Tooltip delayDuration={0}>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setIsEditing(true)}
                                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-sm text-muted-foreground mb-1">{t('Branch')}</p>
                            <p className="font-medium">{employee.branch?.branch_name || '-'}</p>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-sm text-muted-foreground mb-1">{t('Department')}</p>
                            <p className="font-medium">{employee.department?.department_name || '-'}</p>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-sm text-muted-foreground mb-1">{t('Designation')}</p>
                            <p className="font-medium">{employee.designation?.designation_name || '-'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Allowances, Deductions, Loans & Overtimes */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Allowances Section */}
                <Card className="shadow-sm">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-green-600" />
                                {t('Allowances')}
                            </CardTitle>
                            {auth.user?.permissions?.includes('create-allowances') && (
                                <TooltipProvider>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button size="sm" onClick={() => openAllowanceModal('add')}>
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Create')}</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto max-h-80 overflow-y-auto">
                            <DataTable
                                data={allowances}
                                columns={[
                                    {
                                        key: 'allowance_type',
                                        header: t('Allowance Type'),
                                        sortable: false,
                                        render: (_: any, row: Allowance) => row.allowance_type?.name || '-'
                                    },
                                    {
                                        key: 'type',
                                        header: t('Type'),
                                        sortable: false,
                                        render: (_: any, row: Allowance) => t(row.type === 'fixed' ? 'Fixed' : 'Percentage')
                                    },
                                    {
                                        key: 'amount',
                                        header: t('Amount'),
                                        sortable: false,
                                        render: (_: any, row: Allowance) => {
                                            if (row.type === 'fixed') {
                                                return formatCurrency(row.amount) || '0';
                                            } else {
                                                return `${row.amount || '0'}%`;
                                            }
                                        }
                                    },
                                    ...(auth.user?.permissions?.some((p: string) => ['edit-allowances', 'delete-allowances'].includes(p)) ? [{
                                        key: 'actions',
                                        header: t('Actions'),
                                        render: (_: any, allowance: Allowance) => (
                                            <div className="flex gap-1">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('edit-allowances') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openAllowanceModal('edit', allowance)}
                                                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-allowances') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog([allowance.id, employee.id])}
                                                                    className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Delete')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TooltipProvider>
                                            </div>
                                        )
                                    }] : [])
                                ]}
                                className="rounded-none"
                                emptyState={
                                    <div className="text-center py-8">
                                        <p className="text-muted-foreground">{t('No allowances found')}</p>
                                    </div>
                                }
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Deductions Section */}
                <Card className="shadow-sm">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-red-600" />
                                {t('Deductions')}
                            </CardTitle>
                            {auth.user?.permissions?.includes('create-deductions') && (
                                <TooltipProvider>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button size="sm" onClick={() => openDeductionModal('add')}>
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Create')}</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto max-h-80 overflow-y-auto">
                            <DataTable
                                data={deductions}
                                columns={[
                                    {
                                        key: 'deduction_type',
                                        header: t('Deduction Type'),
                                        sortable: false,
                                        render: (_: any, row: Deduction) => row.deduction_type?.name || '-'
                                    },
                                    {
                                        key: 'type',
                                        header: t('Type'),
                                        sortable: false,
                                        render: (_: any, row: Deduction) => t(row.type === 'fixed' ? 'Fixed' : 'Percentage')
                                    },
                                    {
                                        key: 'amount',
                                        header: t('Amount'),
                                        sortable: false,
                                        render: (_: any, row: Deduction) => {
                                            if (row.type === 'fixed') {
                                                return formatCurrency(row.amount) || '0';
                                            } else {
                                                return `${row.amount || '0'}%`;
                                            }
                                        }
                                    },
                                    ...(auth.user?.permissions?.some((p: string) => ['edit-deductions', 'delete-deductions'].includes(p)) ? [{
                                        key: 'actions',
                                        header: t('Actions'),
                                        render: (_: any, deduction: Deduction) => (
                                            <div className="flex gap-1">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('edit-deductions') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeductionModal('edit', deduction)}
                                                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-deductions') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeductionDeleteDialog([deduction.id, employee.id])}
                                                                    className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Delete')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TooltipProvider>
                                            </div>
                                        )
                                    }] : [])
                                ]}
                                className="rounded-none"
                                emptyState={
                                    <div className="text-center py-8">
                                        <p className="text-muted-foreground">{t('No deductions found')}</p>
                                    </div>
                                }
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Loans Section */}
                <Card className="shadow-sm">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-blue-600" />
                                {t('Loans')}
                            </CardTitle>
                            {auth.user?.permissions?.includes('create-loans') && (
                                <TooltipProvider>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button size="sm" onClick={() => openLoanModal('add')}>
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Create')}</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto max-h-80 overflow-y-auto">
                            <DataTable
                                data={loans}
                                columns={[
                                    {
                                        key: 'loan_type',
                                        header: t('Type'),
                                        sortable: false,
                                        render: (_: any, row: Loan) => row.loan_type?.name || '-'
                                    },
                                    {
                                        key: 'amount',
                                        header: t('Amount'),
                                        sortable: false,
                                        render: (_: any, row: Loan) => {
                                            if (row.type === 'fixed') {
                                                return formatCurrency(row.amount) || '0';
                                            } else {
                                                return `${row.amount || '0'}%`;
                                            }
                                        }
                                    },
                                    {
                                        key: 'start_date',
                                        header: t('Start Date'),
                                        sortable: false,
                                        render: (_: any, row: Loan) => formatDate(row.start_date)
                                    },
                                    {
                                        key: 'end_date',
                                        header: t('End Date'),
                                        sortable: false,
                                        render: (_: any, row: Loan) => formatDate(row.end_date)
                                    },
                                    ...(auth.user?.permissions?.some((p: string) => ['edit-loans', 'delete-loans'].includes(p)) ? [{
                                        key: 'actions',
                                        header: t('Actions'),
                                        render: (_: any, loan: Loan) => (
                                            <div className="flex gap-1">
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => openLoanModal('view', loan)}
                                                                className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{t('View')}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    {auth.user?.permissions?.includes('edit-loans') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openLoanModal('edit', loan)}
                                                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-loans') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openLoanDeleteDialog([loan.id, employee.id])}
                                                                    className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Delete')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TooltipProvider>
                                            </div>
                                        )
                                    }] : [])
                                ]}
                                className="rounded-none"
                                emptyState={
                                    <div className="text-center py-8">
                                        <p className="text-muted-foreground">{t('No loans found')}</p>
                                    </div>
                                }
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Overtimes Section */}
                <Card className="shadow-sm">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5 text-orange-600" />
                                {t('Overtimes')}
                            </CardTitle>
                            {auth.user?.permissions?.includes('create-overtimes') && (
                                <TooltipProvider>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button size="sm" onClick={() => openOvertimeModal('add')}>
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>{t('Create')}</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto max-h-80 overflow-y-auto">
                            <DataTable
                                data={overtimes}
                                columns={[
                                    {
                                        key: 'title',
                                        header: t('Title'),
                                        sortable: false,
                                        render: (_: any, row: Overtime) => row.title || '-'
                                    },
                                    {
                                        key: 'total_days',
                                        header: t('Days'),
                                        sortable: false,
                                        render: (_: any, row: Overtime) => row.total_days || '-'
                                    },
                                    {
                                        key: 'hours',
                                        header: t('Hours'),
                                        sortable: false,
                                        render: (_: any, row: Overtime) => row.hours || '-'
                                    },
                                    {
                                        key: 'rate',
                                        header: t('Rate'),
                                        sortable: false,
                                        render: (_: any, row: Overtime) => formatCurrency(row.rate) || '0'                                       
                                    },
                                    {
                                        key: 'status',
                                        header: t('Status'),
                                        sortable: false,
                                        render: (_: any, row: Overtime) => (
                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                                row.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            }`}>
                                                {t(row.status === 'active' ? 'Active' : 'Expired')}
                                            </span>
                                        )
                                    },
                                    ...(auth.user?.permissions?.some((p: string) => ['edit-overtimes', 'delete-overtimes'].includes(p)) ? [{
                                        key: 'actions',
                                        header: t('Actions'),
                                        render: (_: any, overtime: Overtime) => (
                                            <div className="flex gap-1">
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => openOvertimeModal('view', overtime)}
                                                                className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{t('View')}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    {auth.user?.permissions?.includes('edit-overtimes') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openOvertimeModal('edit', overtime)}
                                                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-overtimes') && (
                                                        <Tooltip delayDuration={0}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openOvertimeDeleteDialog([overtime.id, employee.id])}
                                                                    className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Delete')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TooltipProvider>
                                            </div>
                                        )
                                    }] : [])
                                ]}
                                className="rounded-none"
                                emptyState={
                                    <div className="text-center py-8">
                                        <p className="text-muted-foreground">{t('No overtimes found')}</p>
                                    </div>
                                }
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Allowance Modals */}
            <Dialog open={allowanceModalState.isOpen} onOpenChange={closeAllowanceModal}>
                {allowanceModalState.mode === 'add' && (
                    <CreateAllowance
                        employeeId={employee.id}
                        allowanceTypes={allowanceTypes}
                        onSuccess={closeAllowanceModal}
                    />
                )}
                {allowanceModalState.mode === 'edit' && allowanceModalState.data && (
                    <EditAllowance
                        allowance={allowanceModalState.data}
                        allowanceTypes={allowanceTypes}
                        onSuccess={closeAllowanceModal}
                    />
                )}
            </Dialog>

            {/* Deduction Modals */}
            <Dialog open={deductionModalState.isOpen} onOpenChange={closeDeductionModal}>
                {deductionModalState.mode === 'add' && (
                    <CreateDeduction
                        employeeId={employee.id}
                        deductionTypes={deductionTypes}
                        onSuccess={closeDeductionModal}
                    />
                )}
                {deductionModalState.mode === 'edit' && deductionModalState.data && (
                    <EditDeduction
                        deduction={deductionModalState.data}
                        deductionTypes={deductionTypes}
                        onSuccess={closeDeductionModal}
                    />
                )}
            </Dialog>

            {/* Loan Modals */}
            <Dialog open={loanModalState.isOpen} onOpenChange={closeLoanModal}>
                {loanModalState.mode === 'add' && (
                    <CreateLoan
                        employeeId={employee.id}
                        loanTypes={loanTypes}
                        onSuccess={closeLoanModal}
                    />
                )}
                {loanModalState.mode === 'edit' && loanModalState.data && (
                    <EditLoan
                        loan={loanModalState.data}
                        loanTypes={loanTypes}
                        onSuccess={closeLoanModal}
                    />
                )}
                {loanModalState.mode === 'view' && loanModalState.data && (
                    <ViewLoan
                        loan={loanModalState.data}
                    />
                )}
            </Dialog>

            {/* Overtime Modals */}
            <Dialog open={overtimeModalState.isOpen} onOpenChange={closeOvertimeModal}>
                {overtimeModalState.mode === 'add' && (
                    <CreateOvertime
                        employeeId={employee.id}
                        onSuccess={closeOvertimeModal}
                    />
                )}
                {overtimeModalState.mode === 'edit' && overtimeModalState.data && (
                    <EditOvertime
                        overtime={overtimeModalState.data}
                        onSuccess={closeOvertimeModal}
                    />
                )}
                {overtimeModalState.mode === 'view' && overtimeModalState.data && (
                    <ViewOvertime
                        overtime={overtimeModalState.data}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Allowance')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            <ConfirmationDialog
                open={deductionDeleteState.isOpen}
                onOpenChange={closeDeductionDeleteDialog}
                title={t('Delete Deduction')}
                message={deductionDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDeductionDelete}
                variant="destructive"
            />

            <ConfirmationDialog
                open={loanDeleteState.isOpen}
                onOpenChange={closeLoanDeleteDialog}
                title={t('Delete Loan')}
                message={loanDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmLoanDelete}
                variant="destructive"
            />

            <ConfirmationDialog
                open={overtimeDeleteState.isOpen}
                onOpenChange={closeOvertimeDeleteDialog}
                title={t('Delete Overtime')}
                message={overtimeDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmOvertimeDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}