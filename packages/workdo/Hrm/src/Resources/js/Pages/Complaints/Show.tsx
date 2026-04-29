import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { User, Calendar, FileText, MessageSquareWarning, CheckCircle } from 'lucide-react';
import { Complaint } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';

interface ShowComplaintProps {
    complaint: Complaint;
    onClose: () => void;
}

export default function Show({ complaint, onClose }: ShowComplaintProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <MessageSquareWarning className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Complaint Details')}</DialogTitle>
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
                        <p className="mt-1 font-medium">{complaint.employee?.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Against Employee')}
                        </label>
                        <p className="mt-1 font-medium">{complaint.against_employee?.name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Complaint Type')}
                        </label>
                        <p className="mt-1 font-medium">{complaint.complaint_type?.complaint_type || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Complaint Date')}
                        </label>
                        <p className="mt-1 font-medium">{complaint.complaint_date ? formatDate(complaint.complaint_date) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" />
                            {t('Status')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                complaint.status?.toLowerCase() === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                complaint.status?.toLowerCase() === 'in review' ? 'bg-blue-100 text-blue-800' :
                                complaint.status?.toLowerCase() === 'assigned' ? 'bg-purple-100 text-purple-800' :
                                complaint.status?.toLowerCase() === 'in progress' ? 'bg-orange-100 text-orange-800' :
                                complaint.status?.toLowerCase() === 'resolved' ? 'bg-green-100 text-green-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {t(complaint.status?.charAt(0).toUpperCase() + complaint.status?.slice(1) || '-')}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Resolved By')}
                        </label>
                        <p className="mt-1 font-medium">{complaint.resolved_by?.name || '-'}</p>
                    </div>
                </div>
                
                {complaint.resolution_date && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Resolution Date')}
                            </label>
                            <p className="mt-1 font-medium">{formatDate(complaint.resolution_date)}</p>
                        </div>
                    </div>
                )}
                
                {complaint.subject && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Subject')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{complaint.subject}</p>
                        </div>
                    </div>
                )}
                
                {complaint.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{complaint.description}</p>
                        </div>
                    </div>
                )}
                
                {complaint.document && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Document')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <a
                                href={getImagePath(complaint.document)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-blue-600 hover:text-blue-700 text-sm"
                            >
                                {t('View Document')}
                            </a>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}