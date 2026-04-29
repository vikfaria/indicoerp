import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Calendar, FileText, User, Clock, MapPin, CheckCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Event } from './types';
import { formatDate, formatTime } from '@/utils/helpers';

interface ViewProps {
    event: Event;
}

export default function View({ event }: ViewProps) {
    const { t } = useTranslation();

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Calendar className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Event Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Title')}
                        </label>
                        <p className="mt-1 font-medium">{event.title || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Event Type')}
                        </label>
                        <p className="mt-1 font-medium">{event.event_type?.event_type || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Start Date')}
                        </label>
                        <p className="mt-1 font-medium">{event.start_date ? formatDate(event.start_date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('End Date')}
                        </label>
                        <p className="mt-1 font-medium">{event.end_date ? formatDate(event.end_date) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            {t('Start Time')}
                        </label>
                        <p className="mt-1 font-medium">{event.start_time ? formatTime(event.start_time) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            {t('End Time')}
                        </label>
                        <p className="mt-1 font-medium">{event.end_time ? formatTime(event.end_time) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <MapPin className="h-4 w-4" />
                            {t('Location')}
                        </label>
                        <p className="mt-1 font-medium">{event.location || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Approved By')}
                        </label>
                        <p className="mt-1 font-medium">{event.approved_by?.name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" />
                            {t('Status')}
                        </label>
                        <div className="mt-1">
                            <Badge className={`${
                                event.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                event.status === 'approved' ? 'bg-green-100 text-green-800' :
                                event.status === 'reject' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {event.status ? event.status.charAt(0).toUpperCase() + event.status.slice(1) : '-'}
                            </Badge>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <User className="h-4 w-4" />
                            {t('Departments')}
                        </label>
                        <div className="mt-1">
                            {event.departments && event.departments.length > 0 ? (
                                <div className="flex flex-wrap gap-1">
                                    {event.departments.map((dept: any) => (
                                        <Badge key={dept.id} variant="outline" className="text-xs">
                                            {dept.department_name} ({dept.branch?.branch_name || 'No Branch'})
                                        </Badge>
                                    ))}
                                </div>
                            ) : '-'}
                        </div>
                    </div>
                </div>
                
                {event.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Description')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{event.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}