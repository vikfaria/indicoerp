import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { User, Calendar, FileText, Tag, CheckCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Resignation } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';

interface ViewProps {
    resignation: Resignation;
}

export default function View({ resignation }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Tag className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Resignation Details')}</DialogTitle>
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
                            <p className="mt-1 font-medium">{resignation.employee?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Last Working Date')}
                            </label>
                            <p className="mt-1 font-medium">{resignation.last_working_date ? formatDate(resignation.last_working_date) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <div className="mt-1">
                                <Badge className={`${
                                    resignation.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    resignation.status === 'accepted' ? 'bg-green-100 text-green-800' :
                                    'bg-red-100 text-red-800'
                                }`}>
                                    {resignation.status?.charAt(0).toUpperCase() + resignation.status?.slice(1) || 'Pending'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Reason')}
                            </label>
                            <p className="mt-1 font-medium">{resignation.reason || '-'}</p>
                        </div>

                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <User className="h-4 w-4" />
                                {t('Approved By')}
                            </label>
                            <p className="mt-1 font-medium">{resignation.approved_by?.name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                {resignation.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{resignation.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}