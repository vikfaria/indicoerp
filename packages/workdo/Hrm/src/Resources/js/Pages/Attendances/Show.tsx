import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Clock, User, Calendar, Timer, FileText, CheckCircle } from 'lucide-react';
import { Attendance } from './types';
    import { formatDate, formatTime, formatDateTime, getCurrencySymbol } from '@/utils/helpers';

interface ViewAttendanceProps {
    attendance: Attendance;
    onSuccess: () => void;
}

export default function View({ attendance, onSuccess }: ViewAttendanceProps) {
    const { t } = useTranslation();

    const formatStatus = (status: string) => {
        return status.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Clock className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Attendance Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{attendance.user?.name || 'N/A'}</p>
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
                        <p className="mt-1 font-medium">{attendance.user?.name || '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Timer className="h-4 w-4" />
                            {t('Shift')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.shift?.shift_name || '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {t('Date')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.date ? formatDate(attendance.date) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" />
                            {t('Status')}
                        </label>
                        <div className="mt-1">
                            <span className={`px-2 py-1 rounded-full text-sm ${
                                attendance.status === 'present' ? 'bg-green-100 text-green-800' :
                                attendance.status === 'half day' ? 'bg-yellow-100 text-yellow-800' :
                                attendance.status === 'absent' ? 'bg-red-100 text-red-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {formatStatus(attendance.status || 'Unknown')}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            {t('Clock In Time')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.clock_in ? formatDateTime(attendance.clock_in) : '-'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            {t('Clock Out Time')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.clock_out ? formatDateTime(attendance.clock_out) : '-'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Timer className="h-4 w-4" />
                            {t('Break Hours')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.break_hour ? `${attendance.break_hour}h` : '0h'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Timer className="h-4 w-4" />
                            {t('Total Hours')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.total_hour ? `${attendance.total_hour}h` : '0h'}</p>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Timer className="h-4 w-4" />
                            {t('Overtime Hours')}
                        </label>
                        <p className="mt-1 font-medium">{attendance.overtime_hours ? `${attendance.overtime_hours}h` : '0h'}</p>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <Timer className="h-4 w-4" />
                            {t('Overtime Amount')}
                        </label>
                        <p className="mt-1 font-medium">
                            {attendance.overtime_amount ? `${getCurrencySymbol()}${attendance.overtime_amount}` : `${getCurrencySymbol()}0.00`}
                        </p>
                    </div>
                </div>
                
                {attendance.notes && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            {t('Notes')}
                        </label>
                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p className="text-sm">{attendance.notes}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}