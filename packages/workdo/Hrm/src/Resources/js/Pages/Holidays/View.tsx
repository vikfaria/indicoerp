import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Calendar, Tag, FileText, Globe } from 'lucide-react';
import { Holiday } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    holiday: Holiday;
}

export default function View({ holiday }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Calendar className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Holiday Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Holiday Name')}
                        </label>
                        <p className="mt-1 font-medium">{holiday.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Tag className="h-4 w-4" />
                            {t('Holiday Type')}
                        </label>
                        <p className="mt-1 font-medium">{holiday.holiday_type?.holiday_type || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Start Date')}
                        </label>
                        <p className="mt-1 font-medium">{holiday.start_date ? formatDate(holiday.start_date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('End Date')}
                        </label>
                        <p className="mt-1 font-medium">{holiday.end_date ? formatDate(holiday.end_date) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Tag className="h-4 w-4" />
                            {t('Paid')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                holiday.is_paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }`}>
                                {holiday.is_paid ? t('Yes') : t('No')}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Globe className="h-4 w-4" />
                            {t('Google Calendar Sync')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                holiday.is_sync_google_calendar ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }`}>
                                {holiday.is_sync_google_calendar ? t('Yes') : t('No')}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Globe className="h-4 w-4" />
                            {t('Outlook Calendar Sync')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                holiday.is_sync_outlook_calendar ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }`}>
                                {holiday.is_sync_outlook_calendar ? t('Yes') : t('No')}
                            </span>
                        </div>
                    </div>
                </div>
                
                {holiday.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{holiday.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}