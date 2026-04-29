import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Clock, FileText, Calendar, User, Moon, Sun } from 'lucide-react';
import { Shift } from './types';
import { formatTime,formatDate } from '@/utils/helpers';

interface ViewProps {
    shift: Shift;
}

export default function View({ shift }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Clock className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Shift Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{shift.shift_name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t('Shift Name')}
                            </label>
                            <p className="mt-1 font-medium">{shift.shift_name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('Start Time')}
                            </label>
                            <p className="mt-1 font-medium">{shift.start_time ? formatTime(shift.start_time) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('End Time')}
                            </label>
                            <p className="mt-1 font-medium">{shift.end_time ? formatTime(shift.end_time) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                {shift.is_night_shift ? <Moon className="h-4 w-4" /> : <Sun className="h-4 w-4" />}
                                {t('Night Shift')}
                            </label>
                            <div className="mt-1">
                                <span className={`px-2 py-1 rounded-full text-sm ${
                                    shift.is_night_shift ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
                                }`}>
                                    {shift.is_night_shift ? t('Yes') : t('No')}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('Break Start Time')}
                            </label>
                            <p className="mt-1 font-medium">{shift.break_start_time ? formatTime(shift.break_start_time) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                {t('Break End Time')}
                            </label>
                            <p className="mt-1 font-medium">{shift.break_end_time ? formatTime(shift.break_end_time) : '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <User className="h-4 w-4" />
                                {t('Created By')}
                            </label>
                            <p className="mt-1 font-medium">{shift.creator?.name || '-'}</p>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {t('Created At')}
                            </label>
                            <p className="mt-1 font-medium">{shift.created_at ? formatDate(shift.created_at): '-'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    );
}