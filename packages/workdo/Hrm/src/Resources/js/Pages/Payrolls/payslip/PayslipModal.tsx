import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { User, Calendar, DollarSign, Calculator } from 'lucide-react';

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
    attendance_overtime_rate: number;
    working_days: number;
    present_days: number;
    half_days: number;
    absent_days: number;
    paid_leave_days: number;
    unpaid_leave_days: number;
    manual_overtime_hours: number;
    attendance_overtime_hours: number;
    overtime_hours: number;
    per_day_salary: number;
    unpaid_leave_deduction: number;
    half_day_deduction: number;
    absent_day_deduction: number;
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
}

interface PayslipModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    payrollEntry: PayrollEntry | null;
    payroll: Payroll;
}

export function PayslipModal({ open, onOpenChange, payrollEntry, payroll }: PayslipModalProps) {
    const { t } = useTranslation();

    if (!payrollEntry) return null;

    const employeeName = payrollEntry.employee?.user?.name || payrollEntry.employee?.name;
    const employeeEmail = payrollEntry.employee?.user?.email || payrollEntry.employee?.email;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <User className="h-5 w-5" />
                        {t('Payslip')} - {employeeName}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-6 overflow-y-auto max-h-[calc(90vh-8rem)]">
                    {/* Header Info */}
                    <Card>
                        <CardHeader className="pb-4">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-lg font-semibold">{employeeName}</h3>
                                    <p className="text-sm text-gray-600">{employeeEmail}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-medium">{payroll.title}</p>
                                    <p className="text-sm text-gray-600">
                                        {formatDate(payroll.pay_period_start)} - {formatDate(payroll.pay_period_end)}
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                    </Card>

                    {/* Attendance Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Attendance Summary')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-6 gap-3">
                                <div className="text-center p-3 bg-blue-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Working Days')}</p>
                                    <p className="text-xl font-bold text-blue-600">{payrollEntry.working_days}</p>
                                </div>
                                <div className="text-center p-3 bg-green-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Present Days')}</p>
                                    <p className="text-xl font-bold text-green-600">{payrollEntry.present_days}</p>
                                </div>
                                <div className="text-center p-3 bg-yellow-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Half Days')}</p>
                                    <p className="text-xl font-bold text-yellow-600">{payrollEntry.half_days}</p>
                                </div>
                                <div className="text-center p-3 bg-red-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Absent Days')}</p>
                                    <p className="text-xl font-bold text-red-600">{payrollEntry.absent_days}</p>
                                </div>
                                <div className="text-center p-3 bg-purple-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Manual OT Hours')}</p>
                                    <p className="text-xl font-bold text-purple-600">{payrollEntry.manual_overtime_hours}</p>
                                </div>
                                <div className="text-center p-3 bg-indigo-50 rounded-lg">
                                    <p className="text-sm text-gray-600">{t('Attendance OT Hours')}</p>
                                    <p className="text-xl font-bold text-indigo-600">{payrollEntry.attendance_overtime_hours}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Earnings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-green-600">
                                <DollarSign className="h-4 w-4" />
                                {t('Earnings')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between">
                                <span>{t('Basic Salary')}</span>
                                <span className="font-medium">{formatCurrency(payrollEntry.basic_salary)}</span>
                            </div>
                            
                            {payrollEntry.total_allowances > 0 && (
                                <>
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between font-medium">
                                            <span>{t('Allowances')}</span>
                                            <span>{formatCurrency(payrollEntry.total_allowances)}</span>
                                        </div>
                                        {Object.entries(payrollEntry.allowances_breakdown || {}).map(([name, amount]) => (
                                            <div key={name} className="flex justify-between text-sm text-gray-600 ml-4">
                                                <span>• {name}</span>
                                                <span>{formatCurrency(amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}

                            {payrollEntry.total_manual_overtimes > 0 && (
                                <>
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between font-medium">
                                            <span>{t('Manual Overtime')}</span>
                                            <span>{formatCurrency(payrollEntry.total_manual_overtimes)}</span>
                                        </div>
                                        {Object.entries(payrollEntry.manual_overtimes_breakdown || {}).map(([name, amount]) => (
                                            <div key={name} className="flex justify-between text-sm text-gray-600 ml-4">
                                                <span>• {name}</span>
                                                <span>{formatCurrency(amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}

                            {payrollEntry.attendance_overtime_amount > 0 && (
                                <>
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between font-medium">
                                            <span>{t('Attendance Overtime')}</span>
                                            <span>{formatCurrency(payrollEntry.attendance_overtime_amount)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm text-gray-600 ml-4">
                                            <span>• {payrollEntry.attendance_overtime_hours} hrs @ {formatCurrency(payrollEntry.attendance_overtime_rate)}/hr</span>
                                            <span>{formatCurrency(payrollEntry.attendance_overtime_amount)}</span>
                                        </div>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Leave Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-orange-600">
                                <Calendar className="h-4 w-4" />
                                {t('Leave Details')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="font-medium">{t('Paid Leave Days')}</span>
                                <div className="flex items-center gap-4">
                                    <span className="font-medium text-green-600">{payrollEntry.paid_leave_days} days</span>
                                    <span className="text-sm text-gray-500 w-24 text-right">{t('No deduction')}</span>
                                </div>
                            </div>
                            
                            <Separator />
                            
                            <div className="flex justify-between items-center">
                                <span className="font-medium">{t('Unpaid Leave Days')}</span>
                                <div className="flex items-center gap-4">
                                    <span className="font-medium text-red-600">{payrollEntry.unpaid_leave_days} days</span>
                                    <span className="font-medium text-red-600 w-24 text-right">{formatCurrency(payrollEntry.unpaid_leave_deduction)}</span>
                                </div>
                            </div>
                            
                            <Separator />
                            
                            <div className="flex justify-between items-center">
                                <span className="font-medium">{t('Half Days')}</span>
                                <div className="flex items-center gap-4">
                                    <span className="font-medium text-yellow-600">{payrollEntry.half_days} days</span>
                                    <span className="font-medium text-red-600 w-24 text-right">{formatCurrency(payrollEntry.half_day_deduction)}</span>
                                </div>
                            </div>
                            
                            <Separator />
                            
                            <div className="flex justify-between items-center">
                                <span className="font-medium">{t('Absent Days')}</span>
                                <div className="flex items-center gap-4">
                                    <span className="font-medium text-red-600">{payrollEntry.absent_days} days</span>
                                    <span className="font-medium text-red-600 w-24 text-right">{formatCurrency(payrollEntry.absent_day_deduction)}</span>
                                </div>
                            </div>
                            
                            <Separator className="border-t-2" />
                            
                            <div className="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                <span className="font-bold">{t('Total Leave Deductions')}</span>
                                
                                <span className="font-bold text-red-600">{formatCurrency(Number(payrollEntry.unpaid_leave_deduction) + Number(payrollEntry.half_day_deduction) + Number(payrollEntry.absent_day_deduction))}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Deductions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-red-600">
                                <Calculator className="h-4 w-4" />
                                {t('Other Deductions')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">

                            {payrollEntry.total_deductions > 0 && (
                                <>
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between font-medium">
                                            <span>{t('Other Deductions')}</span>
                                            <span className="text-red-600">{formatCurrency(payrollEntry.total_deductions)}</span>
                                        </div>
                                        {Object.entries(payrollEntry.deductions_breakdown || {}).map(([name, amount]) => (
                                            <div key={name} className="flex justify-between text-sm text-gray-600 ml-4">
                                                <span>• {name}</span>
                                                <span className="text-red-600">{formatCurrency(amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}

                            {payrollEntry.total_loans > 0 && (
                                <>
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between font-medium">
                                            <span>{t('Loans')}</span>
                                            <span className="text-red-600">{formatCurrency(payrollEntry.total_loans)}</span>
                                        </div>
                                        {Object.entries(payrollEntry.loans_breakdown || {}).map(([name, amount]) => (
                                            <div key={name} className="flex justify-between text-sm text-gray-600 ml-4">
                                                <span>• {name}</span>
                                                <span className="text-red-600">{formatCurrency(amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Summary */}
                    <Card className="border-2 border-primary/20">
                        <CardContent className="pt-6">
                            <div className="space-y-4">
                                <div className="flex justify-between text-lg">
                                    <div>
                                        <span className="font-medium">{t('Gross Pay')}</span>
                                        <p className="text-xs text-gray-500 mt-1">
                                            (Basic + Allowances + Overtimes) - Leave Deductions
                                        </p>
                                    </div>
                                    <span className="font-bold text-green-600">{formatCurrency(payrollEntry.gross_pay)}</span>
                                </div>
                                <Separator />
                                <div className="flex justify-between text-xl">
                                    <div>
                                        <span className="font-bold">{t('Net Pay')}</span>
                                        <p className="text-xs text-gray-500 mt-1">
                                            Gross Pay - (Other Deductions + Loans)
                                        </p>
                                    </div>
                                    <span className="font-bold text-blue-600">{formatCurrency(payrollEntry.net_pay)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    );
}