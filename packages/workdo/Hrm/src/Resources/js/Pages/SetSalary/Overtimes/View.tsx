import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Clock, FileText, Calendar, Tag, AlertCircle, Hash } from 'lucide-react';
import { formatCurrency, formatDate } from '@/utils/helpers';

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
    approval_status?: string | null;
    approved_at?: string | null;
    rejected_at?: string | null;
    rejection_reason?: string | null;
}

interface ViewOvertimeProps {
    overtime: Overtime;
}

export default function View({ overtime }: ViewOvertimeProps) {
    const { t } = useTranslation();
    const approvalStatus = overtime.approval_status ?? (overtime.status === 'active' ? 'approved' : 'rejected');

    const badgeClassByStatus: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-orange-100 rounded-lg">
                        <Clock className="h-5 w-5 text-orange-600" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Overtime Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{overtime.title}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Title')}
                            </label>
                            <p className="mt-1 font-medium">{overtime.title || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Hash className="h-4 w-4" />
                                {t('Total Days')}
                            </label>
                            <p className="mt-1 font-medium">{overtime.total_days || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('Hours')}
                            </label>
                            <p className="mt-1 font-medium">{overtime.hours || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Tag className="h-4 w-4" />
                                {t('Rate')}
                            </label>
                            <p className="mt-1 font-medium text-lg">
                                {formatCurrency(overtime.rate) || '0'}
                            </p>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Start Date')}
                            </label>
                            <p className="mt-1 font-medium">
                                {overtime.start_date ? formatDate(overtime.start_date) : '-'}
                            </p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('End Date')}
                            </label>
                            <p className={`mt-1 font-medium ${overtime.end_date && new Date(overtime.end_date) < new Date() ? 'text-red-600' : ''}`}>
                                {overtime.end_date ? formatDate(overtime.end_date) : '-'}
                            </p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <AlertCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <div className="mt-1">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                    badgeClassByStatus[approvalStatus] ?? 'bg-gray-100 text-gray-800'
                                }`}>
                                    {t(approvalStatus.charAt(0).toUpperCase() + approvalStatus.slice(1))}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {overtime.approved_at && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">
                            {t('Approved At')}
                        </label>
                        <p className="mt-1 text-sm">{formatDate(overtime.approved_at)}</p>
                    </div>
                )}

                {overtime.rejected_at && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">
                            {t('Rejected At')}
                        </label>
                        <p className="mt-1 text-sm">{formatDate(overtime.rejected_at)}</p>
                    </div>
                )}
                
                {overtime.notes && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Notes')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{overtime.notes}</p>
                        </div>
                    </div>
                )}

                {overtime.rejection_reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Rejection Reason')}
                        </label>
                        <div className="mt-2 p-3 bg-red-50 rounded-lg border border-red-100">
                            <p className="text-sm">{overtime.rejection_reason}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}
