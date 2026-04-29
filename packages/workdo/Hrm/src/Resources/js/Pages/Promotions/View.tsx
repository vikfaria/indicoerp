import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { ArrowRight, User, Building, Users, Calendar, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Promotion } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';

const getStatusBadge = (status: string) => {
    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    };
    return statusColors[status as keyof typeof statusColors] || statusColors.pending;
};

interface ViewProps {
    promotion: Promotion;
}

export default function View({ promotion }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <DialogTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <User className="h-5 w-5" />
                        {t('Promotion Details')}
                    </div>
                    <span className={`px-2 py-1 rounded-full text-sm font-semibold ${getStatusBadge(promotion.status || 'pending')}`}>
                        {t(promotion.status?.charAt(0).toUpperCase() + promotion.status?.slice(1) || 'Pending')}
                    </span>
                </DialogTitle>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                {/* Employee Info */}
                <div className="bg-gray-50 p-4 rounded-lg">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary/10 rounded-lg">
                            <User className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-lg">{promotion.employee?.name || '-'}</h3>
                            <p className="text-sm text-gray-600">{t('Employee')}</p>
                        </div>
                    </div>
                </div>

                {/* Promotion Timeline */}
                <div className="bg-gradient-to-r from-purple-50 to-blue-50 p-6 rounded-lg">
                    <h4 className="font-semibold mb-6 text-center text-xl text-gray-800">{t('Career Progression')}</h4>
                    
                    <div className="relative">
                        {/* Timeline Line */}
                        <div className="absolute left-1/2 top-8 bottom-8 w-0.5 bg-gradient-to-b from-purple-400 to-blue-400 transform -translate-x-1/2"></div>
                        
                        {/* Previous Position */}
                        <div className="flex items-center mb-8">
                            <div className="w-1/2 pr-8 text-right">
                                <div className="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-400">
                                    <h5 className="font-semibold text-red-700 mb-2">{t('Previous Position')}</h5>
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p>{promotion.previous_designation?.designation_name || '-'}</p>
                                        <p>{promotion.previous_department?.department_name || '-'}</p>
                                        <p>{promotion.previous_branch?.branch_name || '-'}</p>
                                    </div>
                                </div>
                            </div>
                            <div className="w-8 h-8 bg-red-400 rounded-full flex items-center justify-center z-10">
                                <Building className="h-4 w-4 text-white" />
                            </div>
                            <div className="w-1/2 pl-8"></div>
                        </div>
                        
                        {/* Current Position */}
                        <div className="flex items-center">
                            <div className="w-1/2 pr-8"></div>
                            <div className="w-8 h-8 bg-green-400 rounded-full flex items-center justify-center z-10">
                                <Building className="h-4 w-4 text-white" />
                            </div>
                            <div className="w-1/2 pl-8">
                                <div className="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-400">
                                    <h5 className="font-semibold text-green-700 mb-2">{t('Current Position')}</h5>
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p>{promotion.current_designation?.designation_name || '-'}</p>
                                        <p>{promotion.current_department?.department_name || '-'}</p>
                                        <p>{promotion.current_branch?.branch_name || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Additional Details */}
                <div className="bg-white p-6 rounded-lg border">
                    <h4 className="font-semibold mb-4 text-gray-800">{t('Promotion Details')}</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                                <Calendar className="h-5 w-5 text-blue-600" />
                                <div>
                                    <p className="text-sm font-medium text-gray-600">{t('Effective Date')}</p>
                                    <p className="font-semibold text-gray-800">{promotion.effective_date ? formatDate(promotion.effective_date) : '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="space-y-4">
                            {promotion.document && (
                                <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                                    <FileText className="h-5 w-5 text-green-600" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-gray-600">{t('Document')}</p>
                                        <Button 
                                            variant="link" 
                                            size="sm"
                                            onClick={() => {
                                                const link = document.createElement('a');
                                                link.href = getImagePath(promotion.document);
                                                link.download = promotion.document?.split('/').pop() || 'promotion-document';
                                                link.click();
                                            }}
                                            className="p-0 h-auto font-semibold text-green-700"
                                        >
                                            {t('Download Document')}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {promotion.reason && (
                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                            <p className="text-sm font-medium text-gray-600 mb-2">{t('Reason for Promotion')}</p>
                            <p className="text-gray-800">{promotion.reason}</p>
                        </div>
                    )}
                    
                    {promotion.approved_by && (
                        <div className="mt-4 p-4 bg-green-50 rounded-lg">
                            <p className="text-sm font-medium text-green-600 mb-2">{t('Approved By')}</p>
                            <p className="text-green-800 font-semibold">{promotion.approved_by.name}</p>
                        </div>
                    )}
                </div>
            </div>
        </DialogContent>
    );
}