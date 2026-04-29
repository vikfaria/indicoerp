import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { FileText, Calendar, User, Clock, CheckCircle, MessageSquare, Tag } from 'lucide-react';
import { LeaveApplication } from './types';
import { formatDate, formatDateTime } from '@/utils/helpers';

interface ViewProps {
    leaveapplication: LeaveApplication;
}

export default function View({ leaveapplication }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <FileText className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Leave Application Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{leaveapplication.employee?.name || 'Unknown Employee'}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <User className="h-4 w-4" />
                                {t('Employee')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.employee?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Tag className="h-4 w-4" />
                                {t('Leave Type')}
                            </label>
                            <div className="mt-1 flex items-center gap-2">
                                <div 
                                    className="w-3 h-3 rounded-full" 
                                    style={{ backgroundColor: leaveapplication.leave_type?.color || '#gray' }}
                                ></div>
                                <p className="font-medium">{leaveapplication.leave_type?.name || '-'}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Start Date')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.start_date ? formatDate(leaveapplication.start_date) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('End Date')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.end_date ? formatDate(leaveapplication.end_date) : '-'}</p>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('Total Days')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.total_days || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <div className="mt-1">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                                    leaveapplication.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    leaveapplication.status === 'approved' ? 'bg-green-100 text-green-800' :
                                    leaveapplication.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {t(leaveapplication.status?.charAt(0).toUpperCase() + leaveapplication.status?.slice(1) || 'Unknown')}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <User className="h-4 w-4" />
                                {t('Approved By')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.approved_by?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Approved At')}
                            </label>
                            <p className="mt-1 font-medium">{leaveapplication.approved_at ? formatDateTime(leaveapplication.approved_at) : '-'}</p>
                        </div>
                    </div>
                </div>
                
                {leaveapplication.reason && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Reason')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{leaveapplication.reason}</p>
                        </div>
                    </div>
                )}
                
                <div>
                    <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                        <MessageSquare className="h-4 w-4" />
                        {t('Approver Comment')}
                    </label>
                    <div className="mt-2 p-3 bg-blue-50 rounded-lg">
                        <p className="text-sm">{leaveapplication.approver_comment || '-'}</p>
                    </div>
                </div>
            </div>
        </DialogContent>
    );
}