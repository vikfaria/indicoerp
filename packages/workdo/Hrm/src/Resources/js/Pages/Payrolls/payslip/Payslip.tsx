import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import html2pdf from 'html2pdf.js';
import { formatCurrency, formatDate, getCompanySetting , getImagePath} from '@/utils/helpers';

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
    payroll: {
        id: number;
        title: string;
        pay_period_start: string;
        pay_period_end: string;
        pay_date: string;
    };
    basic_salary: number;
    total_allowances: number;
    total_manual_overtimes: number;
    total_deductions: number;
    total_loans: number;
    taxable_income: number;
    irps_amount: number;
    inss_employee_rate: number;
    inss_employee_amount: number;
    inss_employer_rate: number;
    inss_employer_amount: number;
    statutory_deductions_total: number;
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
    statutory_deductions_breakdown: Record<string, number>;
    manual_overtimes_breakdown: Record<string, number>;
    loans_breakdown: Record<string, number>;
    minimum_wage_required?: number | null;
    minimum_wage_compliant?: boolean;
    minimum_wage_gap?: number;
    payroll_sector_code?: string | null;
}

interface PayslipProps {
    payrollEntry: PayrollEntry;
}

export default function Payslip() {
    const { t } = useTranslation();
    const { payrollEntry } = usePage<PayslipProps>().props;
    const [isDownloading, setIsDownloading] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === 'pdf') {
            downloadPDF();
        }
    }, []);

    const downloadPDF = async () => {
        setIsDownloading(true);

        const printContent = document.querySelector('.payslip-container');
        if (printContent) {
            const opt = {
                margin: 0.25,
                filename: `payslip-${payrollEntry.employee?.user?.name || payrollEntry.employee?.name}-${formatDate(payrollEntry.payroll.pay_period_start)}.pdf`,
                image: { type: 'jpeg' as const, quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' as const }
            };

            try {
                await html2pdf().set(opt).from(printContent as HTMLElement).save();
                setTimeout(() => window.close(), 1000);
            } catch (error) {
                console.error('PDF generation failed:', error);
            }
        }

        setIsDownloading(false);
    };

    const employeeName = payrollEntry.employee?.user?.name || payrollEntry.employee?.name;
    const employeeEmail = payrollEntry.employee?.user?.email || payrollEntry.employee?.email;

    return (
        <div className="min-h-screen bg-white">
            <Head title={t('Payslip')} />

            {isDownloading && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg shadow-lg">
                        <div className="flex items-center space-x-3">
                            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                            <p className="text-lg font-semibold text-gray-700">{t('Generating PDF...')}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="payslip-container bg-white max-w-4xl mx-auto p-8">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-xl font-bold">{payrollEntry.payroll.title}</h1>
                    </div>
                    <div className="text-right">
                        <h1 className="text-xl font-bold">{getCompanySetting('company_name') || 'YOUR COMPANY'}</h1>
                        <p className="text-sm text-gray-600">{getCompanySetting('company_address')}</p>
                    </div>
                </div>

                {/* Main Payslip Container */}
                <div className="border-2 border-black">
                    {/* Employee Details Grid */}
                    <div className="p-1 pl-1">
                        <div className="grid grid-cols-2 gap-x-8 gap-y-2 mb-4">
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Name')} :</span>
                                <span>{employeeName}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Designation')} :</span>
                                <span>{payrollEntry.employee?.designation?.designation_name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Working Days')} :</span>
                                <span>{payrollEntry.working_days}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Employee ID')} :</span>
                                <span>{payrollEntry.employee.employee_id}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Days Worked')} :</span>
                                <span>{payrollEntry.present_days}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Bank Name')} :</span>
                                <span>{payrollEntry.employee?.bank_name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Paid Leave')} :</span>
                                <span>{payrollEntry.paid_leave_days}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Bank A/C No')} :</span>
                                <span>{payrollEntry.employee?.account_number || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('Unpaid Leave')} :</span>
                                <span>{payrollEntry.unpaid_leave_days}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium">{t('IFSC No')} :</span>
                                <span>{payrollEntry.employee?.ifsc_code || 'N/A'}</span>
                            </div>
                        </div>

                        {/* Earnings and Deductions Table */}
                        <div className="grid grid-cols-2 gap-0 border border-black mt-1">
                            {/* Earnings Column */}
                            <div className="border-r border-black">
                                <div className="bg-gray-100 px-3 py-2 border-b border-black">
                                    <h3 className="font-bold">{t('Earnings')}</h3>
                                </div>
                                <div className="p-3 space-y-1">
                                    <div className="flex justify-between">
                                        <span>{t('Basic Salary')}</span>
                                        <span>{formatCurrency(payrollEntry.basic_salary)}</span>
                                    </div>
                                    
                                    {/* Allowances Section */}
                                    {Object.keys(payrollEntry.allowances_breakdown || {}).length > 0 && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600 pt-1">{t('Allowances')}:</div>
                                            {Object.entries(payrollEntry.allowances_breakdown || {}).map(([name, amount]) => (
                                                <div key={name} className="flex justify-between pl-2">
                                                    <span className="text-sm">{name}</span>
                                                    <span className="text-sm">{formatCurrency(Number(amount))}</span>
                                                </div>
                                            ))}
                                        </>
                                    )}
                                    
                                    {/* Manual Overtime Section */}
                                    {Object.keys(payrollEntry.manual_overtimes_breakdown || {}).length > 0 && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600 pt-1">{t('Manual Overtime')}:</div>
                                            {Object.entries(payrollEntry.manual_overtimes_breakdown || {}).map(([name, amount]) => (
                                                <div key={name} className="flex justify-between pl-2">
                                                    <span className="text-sm">{name}</span>
                                                    <span className="text-sm">{formatCurrency(Number(amount))}</span>
                                                </div>
                                            ))}
                                        </>
                                    )}
                                    
                                    {/* Attendance Overtime Section */}
                                    {payrollEntry.attendance_overtime_amount > 0 && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600 pt-1">{t('Attendance Overtime')}:</div>
                                            <div className="flex justify-between pl-2">
                                                <span className="text-sm">{t('OT Hours')} ({payrollEntry.attendance_overtime_hours}h)</span>
                                                <span className="text-sm">{formatCurrency(payrollEntry.attendance_overtime_amount)}</span>
                                            </div>
                                        </>
                                    )}
                                    
                                </div>
                            </div>

                            {/* Deductions Column */}
                            <div>
                                <div className="bg-gray-100 px-3 py-2 border-b border-black">
                                    <h3 className="font-bold">{t('Deduction')}</h3>
                                </div>
                                <div className="p-3 space-y-1">
                                    {/* Leave Deductions Section */}
                                    {(payrollEntry.unpaid_leave_deduction > 0 || payrollEntry.half_day_deduction > 0 || payrollEntry.absent_day_deduction > 0) && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600">{t('Leave Deductions')}:</div>
                                            {payrollEntry.unpaid_leave_deduction > 0 && (
                                                <div className="flex justify-between pl-2">
                                                    <span className="text-sm">{t('Unpaid Leave')} ({payrollEntry.unpaid_leave_days}d)</span>
                                                    <span className="text-sm">{formatCurrency(payrollEntry.unpaid_leave_deduction)}</span>
                                                </div>
                                            )}
                                            {payrollEntry.half_day_deduction > 0 && (
                                                <div className="flex justify-between pl-2">
                                                    <span className="text-sm">{t('Half Days')} ({payrollEntry.half_days}d)</span>
                                                    <span className="text-sm">{formatCurrency(payrollEntry.half_day_deduction)}</span>
                                                </div>
                                            )}
                                            {payrollEntry.absent_day_deduction > 0 && (
                                                <div className="flex justify-between pl-2">
                                                    <span className="text-sm">{t('Absent Days')} ({payrollEntry.absent_days}d)</span>
                                                    <span className="text-sm">{formatCurrency(payrollEntry.absent_day_deduction)}</span>
                                                </div>
                                            )}
                                        </>
                                    )}
                                    
                                    {/* Other Deductions Section */}
                                    {Object.keys(payrollEntry.deductions_breakdown || {}).length > 0 && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600 pt-1">{t('Other Deductions')}:</div>
                                            {Object.entries(payrollEntry.deductions_breakdown || {}).map(([name, amount]) => (
                                                <div key={name} className="flex justify-between pl-2">
                                                    <span className="text-sm">{name}</span>
                                                    <span className="text-sm">{formatCurrency(Number(amount))}</span>
                                                </div>
                                            ))}
                                        </>
                                    )}
                                    
                                    {/* Loans Section */}
                                    {Object.keys(payrollEntry.loans_breakdown || {}).length > 0 && (
                                        <>
                                            <div className="text-xs font-semibold text-gray-600 pt-1">{t('Loans')}:</div>
                                            {Object.entries(payrollEntry.loans_breakdown || {}).map(([name, amount]) => (
                                                <div key={name} className="flex justify-between pl-2">
                                                    <span className="text-sm">{name}</span>
                                                    <span className="text-sm">{formatCurrency(Number(amount))}</span>
                                                </div>
                                            ))}
                                        </>
                                    )}
                                    
                                </div>
                            </div>
                        </div>

                        {/* Gross Pay and Net Pay Summary */}
                        <div className="mt-2 space-y-2">
                            <div className="px-3 py-3 flex justify-between items-center">
                                <span><span className="font-bold">{t('Gross Pay')}</span> (Basic + Allowances + Overtimes - Leave Deductions)</span>
                                <span className="font-bold">{formatCurrency(payrollEntry.gross_pay)}</span>
                            </div>
                            <div className="px-3 py-3 flex justify-between items-center">
                                <span><span className="font-bold">{t('Net Pay')}</span> (Gross - Deductions)</span>
                                <span className="font-bold">{formatCurrency(payrollEntry.net_pay)}</span>
                            </div>
                            <div className="px-3 py-1 flex justify-between items-center text-sm text-gray-700">
                                <span>{t('IRPS')}</span>
                                <span>{formatCurrency(payrollEntry.irps_amount || 0)}</span>
                            </div>
                            <div className="px-3 py-1 flex justify-between items-center text-sm text-gray-700">
                                <span>{t('INSS Worker')}</span>
                                <span>{formatCurrency(payrollEntry.inss_employee_amount || 0)}</span>
                            </div>
                            <div className="px-3 py-1 flex justify-between items-center text-sm text-gray-700">
                                <span>{t('INSS Employer')}</span>
                                <span>{formatCurrency(payrollEntry.inss_employer_amount || 0)}</span>
                            </div>
                            {payrollEntry.minimum_wage_required !== null && payrollEntry.minimum_wage_required !== undefined && (
                                <div className="px-3 py-1 flex justify-between items-center text-sm text-gray-700">
                                    <span>{t('Minimum Wage Compliance')}</span>
                                    <span>{payrollEntry.minimum_wage_compliant ? t('Compliant') : t('Non-compliant')}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Net Salary Box */}
                <div className="mt-4 w-48">
                    <div className="bg-white border border-black rounded-lg p-3 flex items-center justify-center min-h-[4rem] text-center">
                        <div>
                            <div className="text-sm text-black">{t('Net Salary')}</div>
                            <div className="text-xl font-bold text-black">{formatCurrency(payrollEntry.net_pay)}</div>
                        </div>
                    </div>
                </div>

                {/* Footer with Company Details */}
                <div className="mt-6 flex justify-between items-end">
                    <div className="text-center flex-1">
                        <p className="text-xs text-gray-600">{t('This is a computer generated payslip and does not require signature.')}</p>
                    </div>
                    <div className="text-right text-sm text-gray-600 max-w-xs">
                        <div className="font-semibold">{getCompanySetting('company_name') || 'YOUR COMPANY'}</div>
                        {getCompanySetting('company_address') && <div>{getCompanySetting('company_address')}</div>}
                        {(getCompanySetting('company_city') || getCompanySetting('company_state') || getCompanySetting('company_zipcode')) && (
                            <div>
                                {getCompanySetting('company_city')}{getCompanySetting('company_state') && `, ${getCompanySetting('company_state')}`} {getCompanySetting('company_zipcode')}
                            </div>
                        )}
                        {getCompanySetting('company_country') && <div>{getCompanySetting('company_country')}</div>}
                        {getCompanySetting('company_telephone') && <div>{t('Ph')}: {getCompanySetting('company_telephone')}</div>}
                        {getCompanySetting('company_email') && <div>{getCompanySetting('company_email')}</div>}
                    </div>
                </div>
            </div>

            <style>{`
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                    font-family: Arial, sans-serif;
                }

                @page {
                    margin: 0.25in;
                    size: A4;
                }

                .payslip-container {
                    max-width: 100%;
                    margin: 0;
                    box-shadow: none;
                }

                @media print {
                    body {
                        background: white;
                    }

                    .payslip-container {
                        box-shadow: none;
                    }
                }
            `}</style>
        </div>
    );
}
