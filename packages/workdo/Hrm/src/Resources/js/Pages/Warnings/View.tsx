import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { User, Calendar, FileText, AlertOctagon, CheckCircle } from 'lucide-react';
import { formatDate, getImagePath } from '@/utils/helpers';

interface WarningViewProps {
    warning: any;
    onClose: () => void;
}

export default function WarningView({ warning, onClose }: WarningViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <AlertOctagon className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Warning Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Employee Name')}
                        </label>
                        <p className="mt-1 font-medium">{warning.employee?.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Warning By')}
                        </label>
                        <p className="mt-1 font-medium">{warning.warning_by?.name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Warning Type')}
                        </label>
                        <p className="mt-1 font-medium">{warning.warning_type?.warning_type_name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Warning Date')}
                        </label>
                        <p className="mt-1 font-medium">{warning.warning_date ? formatDate(warning.warning_date) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <AlertOctagon className="h-4 w-4" />
                            {t('Severity')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                warning.severity === 'Minor' ? 'bg-green-100 text-green-800' :
                                warning.severity === 'Moderate' ? 'bg-yellow-100 text-yellow-800' :
                                warning.severity === 'Major' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {t(warning.severity || '-')}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" />
                            {t('Status')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                warning.is_cancelled ? 'bg-slate-200 text-slate-800' :
                                warning.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                warning.status === 'approved' ? 'bg-green-100 text-green-800' :
                                warning.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {warning.is_cancelled ? t('Cancelled') : t(warning.status ? warning.status.charAt(0).toUpperCase() + warning.status.slice(1) : 'Pending')}
                            </span>
                        </div>
                    </div>
                </div>
                
                {warning.subject && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Subject')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{warning.subject}</p>
                        </div>
                    </div>
                )}
                
                {warning.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{warning.description}</p>
                        </div>
                    </div>
                )}

                {(warning.note_of_culpa_issued_at || warning.note_of_culpa_delivered_at || warning.response_deadline_at || warning.decision_deadline_at) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Note of Charge Issued')}</label>
                            <p className="mt-1 font-medium">{warning.note_of_culpa_issued_at ? formatDate(warning.note_of_culpa_issued_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Note of Charge Delivered')}</label>
                            <p className="mt-1 font-medium">{warning.note_of_culpa_delivered_at ? formatDate(warning.note_of_culpa_delivered_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Response Deadline')}</label>
                            <p className="mt-1 font-medium">{warning.response_deadline_at ? formatDate(warning.response_deadline_at) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Decision Deadline')}</label>
                            <p className="mt-1 font-medium">{warning.decision_deadline_at ? formatDate(warning.decision_deadline_at) : '-'}</p>
                        </div>
                    </div>
                )}

                {warning.worker_refused_note_of_culpa && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Signature Refusal Witnesses')}</label>
                        <div className="mt-2 p-3 bg-yellow-50 rounded-lg text-sm space-y-1">
                            <p>{warning.refusal_witness_one_name || '-'}</p>
                            <p>{warning.refusal_witness_two_name || '-'}</p>
                        </div>
                    </div>
                )}

                {(warning.disciplinary_sanction || warning.disciplinary_decision_at) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Sanction')}</label>
                            <p className="mt-1 font-medium">{warning.disciplinary_sanction ? t(warning.disciplinary_sanction) : '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Decision Date')}</label>
                            <p className="mt-1 font-medium">{warning.disciplinary_decision_at ? formatDate(warning.disciplinary_decision_at) : '-'}</p>
                        </div>
                    </div>
                )}
                
                {warning.employee_response && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Employee Response')}
                        </label>
                        <div className="mt-2 p-3 bg-blue-50 rounded-lg">
                            <p className="text-sm">{warning.employee_response}</p>
                        </div>
                    </div>
                )}

                {warning.is_cancelled && warning.cancellation_reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Cancellation Reason')}</label>
                        <div className="mt-2 p-3 bg-red-50 rounded-lg">
                            <p className="text-sm">{warning.cancellation_reason}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}
