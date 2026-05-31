import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { User, Calendar, FileText, UserX, CheckCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Termination } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';

interface ViewProps {
    termination: Termination;
}

export default function View({ termination }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <UserX className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Termination Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Employee')}
                        </label>
                        <p className="mt-1 font-medium">{termination.employee?.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Termination Type')}
                        </label>
                        <p className="mt-1 font-medium">{termination.termination_type?.termination_type || '-'}</p>
                    </div>
                </div>

                {(termination.legal_notice_required_days !== undefined ||
                    termination.legal_notice_provided_days !== undefined ||
                    termination.legal_notice_missing_days !== undefined) && (
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Legal Notice Required (days)')}</label>
                            <p className="mt-1 font-medium">{termination.legal_notice_required_days ?? '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Notice Provided (days)')}</label>
                            <p className="mt-1 font-medium">{termination.legal_notice_provided_days ?? '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Notice Missing (days)')}</label>
                            <p className="mt-1 font-medium">{termination.legal_notice_missing_days ?? '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Notice Compliance')}</label>
                            <p className="mt-1 font-medium">
                                {termination.legal_notice_compliant === undefined || termination.legal_notice_compliant === null
                                    ? '-'
                                    : termination.legal_notice_compliant ? t('Compliant') : t('Non-compliant')}
                            </p>
                        </div>
                    </div>
                )}

                {(termination.settlement_salary_until_exit_amount !== undefined ||
                    termination.settlement_gross_amount !== undefined ||
                    termination.settlement_net_amount !== undefined) && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Salary Until Exit')}</label>
                            <p className="mt-1 font-medium">{termination.settlement_salary_until_exit_amount !== undefined ? formatCurrency(termination.settlement_salary_until_exit_amount) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Settlement Gross')}</label>
                            <p className="mt-1 font-medium">{termination.settlement_gross_amount !== undefined ? formatCurrency(termination.settlement_gross_amount) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Settlement Net')}</label>
                            <p className="mt-1 font-medium">{termination.settlement_net_amount !== undefined ? formatCurrency(termination.settlement_net_amount) : '-'}</p>
                        </div>
                    </div>
                )}
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Notice Date')}
                        </label>
                        <p className="mt-1 font-medium">{termination.notice_date ? formatDate(termination.notice_date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Approved By')}
                        </label>
                        <p className="mt-1 font-medium">{termination.approved_by?.name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Termination Date')}
                        </label>
                        <p className="mt-1 font-medium">{termination.termination_date ? formatDate(termination.termination_date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" />
                            {t('Status')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                termination.is_cancelled ? 'bg-slate-200 text-slate-800' :
                                termination.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                termination.status === 'approved' ? 'bg-green-100 text-green-800' :
                                termination.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {termination.is_cancelled ? t('Cancelled') : t(termination.status?.charAt(0).toUpperCase() + termination.status?.slice(1) || 'Pending')}
                            </span>
                        </div>
                    </div>
                </div>
                
                {termination.reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Reason')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{termination.reason}</p>
                        </div>
                    </div>
                )}
                
                {termination.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{termination.description}</p>
                        </div>
                    </div>
                )}

                {(termination.offboarding_letter_delivered_at ||
                    termination.offboarding_assets_returned_at ||
                    termination.offboarding_access_revoked_at ||
                    termination.offboarding_final_payment_at ||
                    termination.offboarding_certificate_issued_at ||
                    termination.offboarding_inss_notified_at ||
                    termination.offboarding_migration_notified_at ||
                    termination.offboarding_archive_completed_at ||
                    termination.offboarding_completed_at) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Letter Delivered')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_letter_delivered_at ? formatDate(termination.offboarding_letter_delivered_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Assets Returned')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_assets_returned_at ? formatDate(termination.offboarding_assets_returned_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Access Revoked')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_access_revoked_at ? formatDate(termination.offboarding_access_revoked_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Final Payment')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_final_payment_at ? formatDate(termination.offboarding_final_payment_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Certificate Issued')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_certificate_issued_at ? formatDate(termination.offboarding_certificate_issued_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('INSS Notified')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_inss_notified_at ? formatDate(termination.offboarding_inss_notified_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Migration Notified')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_migration_notified_at ? formatDate(termination.offboarding_migration_notified_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Archive Completed')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_archive_completed_at ? formatDate(termination.offboarding_archive_completed_at) : '-'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-sm font-medium text-gray-500">{t('Offboarding Completed')}</label>
                            <p className="mt-1 font-medium">{termination.offboarding_completed_at ? formatDate(termination.offboarding_completed_at) : '-'}</p>
                        </div>
                    </div>
                )}

                {termination.offboarding_notes && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Offboarding Notes')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{termination.offboarding_notes}</p>
                        </div>
                    </div>
                )}

                {termination.is_cancelled && termination.cancellation_reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Cancellation Reason')}</label>
                        <div className="mt-2 p-3 bg-red-50 rounded-lg">
                            <p className="text-sm">{termination.cancellation_reason}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}
