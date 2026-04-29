import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Tag, User, Calendar, FileText, FileImage } from 'lucide-react';
import { Award } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';

interface ViewAwardProps {
    award: Award;
}

export default function View({ award }: ViewAwardProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Tag className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Award Details')}</DialogTitle>
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
                        <p className="mt-1 font-medium">{award.employee?.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Tag className="h-4 w-4" />
                            {t('Award Type')}
                        </label>
                        <p className="mt-1 font-medium">{award.award_type?.name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Award Date')}
                        </label>
                        <p className="mt-1 font-medium">{award.award_date ? formatDate(award.award_date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileImage className="h-4 w-4" />
                            {t('Certificate')}
                        </label>
                        <div className="mt-1">
                            {award.certificate ? (
                                <a href={getImagePath(award.certificate)} target="_blank" className="text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                    {t('View Certificate')}
                                </a>
                            ) : (
                                <p className="font-medium">-</p>
                            )}
                        </div>
                    </div>
                </div>
                
                {award.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{award.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}