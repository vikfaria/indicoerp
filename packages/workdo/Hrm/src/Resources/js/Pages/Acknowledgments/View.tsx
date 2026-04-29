import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { FileCheck, User, FileText, Calendar, CheckCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Acknowledgment } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    acknowledgment: Acknowledgment;
}

export default function View({ acknowledgment }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <FileCheck className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Acknowledgment Details')}</DialogTitle>
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
                            <p className="mt-1 font-medium">{acknowledgment.employee?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Document')}
                            </label>
                            <p className="mt-1 font-medium">{acknowledgment.document?.title || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <div className="mt-1">
                                <span className={`px-2 py-1 rounded-full text-sm ${
                                    acknowledgment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    acknowledgment.status === 'acknowledged' ? 'bg-green-100 text-green-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {t(acknowledgment.status === 'pending' ? 'Pending' : acknowledgment.status === 'acknowledged' ? 'Acknowledged' : acknowledgment.status || '-')}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <User className="h-4 w-4" />
                                {t('Assigned By')}
                            </label>
                            <p className="mt-1 font-medium">{acknowledgment.assigned_by?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Acknowledged At')}
                            </label>
                            <p className="mt-1 font-medium">{acknowledgment.acknowledged_at ? formatDate(acknowledgment.acknowledged_at) : '-'}</p>
                        </div>
                    </div>
                </div>
                
                {acknowledgment.acknowledgment_note && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Acknowledgment Note')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{acknowledgment.acknowledgment_note}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}