import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CalendarView from "@/components/calendar-view";
import {
    Clock,
    Calendar,
    CalendarDays,
    FileText,
    User,
    CheckCircle,
    XCircle,
    AlertCircle,
    TrendingUp,
    Award,
    Play,
    Square,
    Shield,
    MessageSquare
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { formatDate, formatTime,formatDateTime } from '@/utils/helpers';

interface EmployeeDashboardProps {
    message: string;
    auth: any;
    stats: {
        my_attendance: number;
        total_approved_leave_year: number;
        total_approved_leave_month: number;
        pending_requests: number;
        total_absent_days: number;
        total_awards: number;
        total_warnings: number;
        total_complaints: number;
        calendar_events?: Array<{
            id: number;
            title: string;
            startDate: string;
            endDate: string;
            time: string;
            description: string;
            type: string;
            color: string;
        }>;
        recent_announcements?: Array<{
            id: number;
            title: string;
            description: string;
            created_at: string;
        }>;
        recent_leave_applications?: Array<{
            id: number;
            leave_type: string;
            start_date: string;
            end_date: string;
            total_days: number;
            status: string;
            created_at: string;
        }>;
        recent_awards?: Array<{
            id: number;
            award_type: string;
            award_date: string;
            created_at: string;
        }>;
        recent_warnings?: Array<{
            id: number;
            warning_type: string;
            warning_date: string;
            created_at: string;
        }>;
    };
}

export default function EmployeeDashboard({ message, stats }: EmployeeDashboardProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;

    const [isClockedIn, setIsClockedIn] = useState(stats.attendance_data?.is_clocked_in || false);
    const [clockTime, setClockTime] = useState(stats.attendance_data?.is_clocked_in ? stats.attendance_data?.clock_in_time : '--:--');
    const [clockInTime, setClockInTime] = useState(stats.attendance_data?.clock_in_time || '');
    const [clockOutTime, setClockOutTime] = useState(stats.attendance_data?.clock_out_time || '');
    const [totalWorkingHours, setTotalWorkingHours] = useState(stats.attendance_data?.total_working_hours || '');


    useEffect(() => {
        // Initialize state from props data
        const attendanceData = stats.attendance_data;
        if (attendanceData) {
            setIsClockedIn(attendanceData.is_clocked_in);
            setClockTime(attendanceData.is_clocked_in ? attendanceData.clock_in_time : '--:--');
            setClockInTime(attendanceData.clock_in_time || '');
            setClockOutTime(attendanceData.clock_out_time || '');
            setTotalWorkingHours(attendanceData.total_working_hours || '');
        }
    }, [stats.attendance_data]);

    const handleClockAction = () => {
        const endpoint = isClockedIn ? route('hrm.attendances.clock-out') : route('hrm.attendances.clock-in');
        router.post(endpoint, {}, {
            onSuccess: () => {
                fetch(route('hrm.attendances.clock-status'))
                    .then(response => response.json())
                    .then(data => {
                        setIsClockedIn(data.is_clocked_in);
                        setClockTime(data.is_clocked_in ? data.clock_in_time : '--:--');
                        setClockInTime(data.clock_in_time || '');
                        setClockOutTime(data.clock_out_time || '');
                        setTotalWorkingHours(data.total_working_hours || '');
                    });
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Employee Dashboard') }]}
            pageTitle={t('Employee Dashboard')}
        >
            <Head title={t('Employee Dashboard')} />

            <div className="space-y-6">
                {/* Employee Quick Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div onClick={() => window.location.href = route('hrm.attendances.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-blue-700">{t('My Attendance')}</CardTitle>
                                <Clock className="h-5 w-5 text-blue-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-blue-900">{stats.my_attendance}</div>
                                <p className="text-xs text-blue-600 mt-1">{t('Days this month')}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div onClick={() => window.location.href = route('hrm.leave-applications.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-green-700">{t('Total Approved Leave')}</CardTitle>
                                <Calendar className="h-5 w-5 text-green-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-green-900">{stats.total_approved_leave_month}</div>
                                <p className="text-xs text-green-600 mt-1">{t('Current Month Leave')}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div onClick={() => window.location.href = route('hrm.leave-applications.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-purple-700">{t('Pending Requests')}</CardTitle>
                                <AlertCircle className="h-5 w-5 text-purple-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-purple-900">{stats.pending_requests}</div>
                                <p className="text-xs text-purple-600 mt-1">{t('Awaiting approval')}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div onClick={() => window.location.href = route('hrm.attendances.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-orange-700">{t('Total Absent Days')}</CardTitle>
                                <XCircle className="h-5 w-5 text-orange-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-orange-900">{stats.total_absent_days}</div>
                                <p className="text-xs text-orange-600 mt-1">{t('This Month')}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Employee Records */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div onClick={() => window.location.href = route('hrm.awards.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-emerald-700">{t('Total Awards')}</CardTitle>
                                <Award className="h-5 w-5 text-emerald-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-emerald-900">{stats.total_awards}</div>
                                <p className="text-xs text-emerald-600 mt-1">{t('This Month')}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div onClick={() => window.location.href = route('hrm.warnings.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-yellow-700">{t('Total Warnings')}</CardTitle>
                                <Shield className="h-5 w-5 text-yellow-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-yellow-900">{stats.total_warnings}</div>
                                <p className="text-xs text-yellow-600 mt-1">{t('This Year')}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div onClick={() => window.location.href = route('hrm.complaints.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-red-50 to-red-100 border-red-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-red-700">{t('Total Complaints')}</CardTitle>
                                <MessageSquare className="h-5 w-5 text-red-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-red-900">{stats.total_complaints}</div>
                                <p className="text-xs text-red-600 mt-1">{t('This Year')}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Clock In/Out Section */}
                {(auth.user?.permissions?.includes('clock-in') || auth.user?.permissions?.includes('clock-out')) && (
                    <div className="grid grid-cols-1 gap-6">
                        <Card className="bg-gradient-to-r from-slate-50 to-slate-100 border-slate-200">
                            <CardContent className="p-6">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    {/* Left Side - Clock In/Out */}
                                    <div>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className={`p-3 rounded-full ${isClockedIn ? 'bg-green-500' : 'bg-gray-400'}`}>
                                                    <Clock className="h-6 w-6 text-white" />
                                                </div>
                                                <div>
                                                    {clockOutTime ? (
                                                        <div>
                                                            <h3 className="text-lg font-semibold text-gray-900">{t('Today\'s Attendance')}</h3>
                                                            <div className="mt-3 space-y-2 text-sm text-gray-600">
                                                                <p><strong>{t('Clock In')}:</strong> {formatDateTime(clockInTime)}</p>
                                                                <p><strong>{t('Clock Out')}:</strong> {formatDateTime(clockOutTime)}</p>
                                                                <p><strong>{t('Total Working Hours')}:</strong> {totalWorkingHours}</p>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <div>
                                                            <h3 className="text-lg font-semibold text-gray-900">
                                                                {isClockedIn ? t('Clocked In') : t('Not Clocked In')}
                                                            </h3>
                                                            <p className="text-sm text-gray-600">
                                                                {isClockedIn ? `${t('Since')}: ${formatDateTime(clockTime)}` : t('Ready to start your day?')}
                                                            </p>
                                                            {!clockOutTime && !stats.attendance_data?.can_clock && (
                                                                <div className="mt-2">
                                                                    
                                                                    {(() => {
                                                                        if (stats.attendance_data?.is_on_leave) {
                                                                            const today = new Date().toISOString().split('T')[0];
                                                                            const todayLeave = stats.recent_leave_applications?.find(leave => {
                                                                                const leaveStart = leave.start_date.split('T')[0];
                                                                                const leaveEnd = leave.end_date.split('T')[0];
                                                                                return leaveStart <= today && leaveEnd >= today && leave.status === 'approved';
                                                                            });
                                                                            return (
                                                                                <div className="text-sm">
                                                                                    <p className="font-bold text-orange-600">{t('You are on approved leave today')}</p>
                                                                                    {todayLeave && (
                                                                                        <div className="mt-3 space-y-2 text-xs text-gray-600">
                                                                                            <p><strong>{t('Leave Type')}:</strong> {todayLeave.leave_type}</p>
                                                                                            <p><strong>{t('Status')}:</strong> <span className="text-green-600">{todayLeave.status}</span></p>
                                                                                            <p><strong>{t('Date')}:</strong> {formatDate(todayLeave.start_date)} - {formatDate(todayLeave.end_date)} ({todayLeave.total_days} day{todayLeave.total_days > 1 ? 's' : ''})</p>
                                                                                        </div>
                                                                                    )}
                                                                                </div>
                                                                            );
                                                                        } else if (stats.attendance_data?.is_holiday) {
                                                                            const today = new Date().toISOString().split('T')[0];
                                                                            const todayHoliday = stats.calendar_events?.find(event => {
                                                                                const eventStart = event.startDate.split('T')[0];
                                                                                const eventEnd = event.endDate.split('T')[0];
                                                                                return eventStart <= today && eventEnd >= today && event.type === 'holiday';
                                                                            });
                                                                            return (
                                                                                <div className="text-sm">
                                                                                    <p className="font-bold text-red-600">{t('Today is a holiday')}</p>
                                                                                    {todayHoliday && (
                                                                                        <div className="mt-3 space-y-2 text-xs text-gray-600">
                                                                                            <p><strong>{t('Title')}:</strong> {todayHoliday.title}</p>
                                                                                            <p><strong>{t('Date')}:</strong> {formatDate(todayHoliday.startDate)} - {formatDate(todayHoliday.endDate)} ({Math.ceil((new Date(todayHoliday.endDate) - new Date(todayHoliday.startDate)) / (1000 * 60 * 60 * 24)) + 1} day{Math.ceil((new Date(todayHoliday.endDate) - new Date(todayHoliday.startDate)) / (1000 * 60 * 60 * 24)) + 1 > 1 ? 's' : ''})</p>
                                                                                        </div>
                                                                                    )}
                                                                                </div>
                                                                            );
                                                                        } else if (stats.attendance_data?.is_non_working_day) {
                                                                            return (
                                                                                <div className="text-sm">
                                                                                    <p className="font-bold text-red-600">{t('Today is not a working day')}</p>
                                                                                </div>
                                                                            );
                                                                        }
                                                                        return null;
                                                                    })()}
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            {!clockOutTime && stats.attendance_data?.can_clock && ((isClockedIn && auth.user?.permissions?.includes('clock-out')) || (!isClockedIn && auth.user?.permissions?.includes('clock-in'))) && (
                                                <Button
                                                    onClick={handleClockAction}
                                                    className={`px-6 py-3 ${isClockedIn ? 'bg-red-500 hover:bg-red-600' : 'bg-green-100 text-green-800 hover:bg-green-200'}`}
                                                >
                                                    {isClockedIn ? (
                                                        <>
                                                            <Square className="h-4 w-4 mr-2" />
                                                            {t('Clock Out')}
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Play className="h-4 w-4 mr-2" />
                                                            {t('Clock In')}
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                    
                                    {/* Right Side - Important Notes */}
                                    <div className="lg:col-span-1 border rounded-lg p-4 bg-blue-50">
                                        <h4 className="font-medium mb-3 text-blue-900">
                                            {t('Important Notes')}
                                        </h4>
                                        <div className="space-y-2 text-sm text-blue-800">
                                            <div className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">○</span>
                                                <span>{t('You can clock in and clock out only once per day')}</span>
                                            </div>
                                            <div className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">○</span>
                                                <span>{t('If you forget to clock out, the system will automatically clock you out at shift end time when you clock in next day')}</span>
                                            </div>
                                            <div className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">○</span>
                                                <span>{t('Your shift timing')}: <span className="font-bold">{stats.attendance_data?.shift_start_time ? formatTime(stats.attendance_data.shift_start_time) : '--:--'} - {stats.attendance_data?.shift_end_time ? formatTime(stats.attendance_data.shift_end_time) : '--:--'}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Employee Actions & Info */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Attendance */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Clock className="h-5 w-5" />
                                {t('Recent Attendance')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.recent_attendance && stats.recent_attendance.length > 0 ? (
                                    stats.recent_attendance.map((attendance, index) => {
                                        const getStatusIcon = (status) => {
                                            switch (status) {
                                                case 'present': return <CheckCircle className="h-5 w-5 text-green-500" />;
                                                case 'absent': return <XCircle className="h-5 w-5 text-red-500" />;
                                                case 'half day': return <AlertCircle className="h-5 w-5 text-yellow-500" />;
                                                default: return <Clock className="h-5 w-5 text-gray-500" />;
                                            }
                                        };
                                        
                                        const getStatusBadge = (status) => {
                                            const statusColors = {
                                                'present': 'bg-green-100 text-green-800',
                                                'absent': 'bg-red-100 text-red-800',
                                                'half day': 'bg-yellow-100 text-yellow-800'
                                            };
                                            return statusColors[status] || 'bg-gray-100 text-gray-800';
                                        };
                                        

                                        
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div className="flex items-center gap-3">
                                                    {getStatusIcon(attendance.status)}
                                                    <div>
                                                        <p className="text-sm font-medium">{formatDate(attendance.date)}</p>
                                                        <p className="text-xs text-gray-500">
                                                            {attendance.clock_in && attendance.clock_out 
                                                                ? `${formatDateTime(attendance.clock_in)} - ${formatDateTime(attendance.clock_out)}`
                                                                : attendance.clock_in 
                                                                ? `${formatDateTime(attendance.clock_in)} - --:--`
                                                                : 'No attendance'
                                                            }
                                                        </p>
                                                    </div>
                                                </div>
                                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusBadge(attendance.status)}`}>
                                                    {t(attendance.status?.charAt(0).toUpperCase() + attendance.status?.slice(1) || 'Unknown')}
                                                </span>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Clock className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No attendance records found')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Leave Requests */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Calendar className="h-5 w-5" />
                                {t('My Leave Requests')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.recent_leave_applications && stats.recent_leave_applications.length > 0 ? (
                                    stats.recent_leave_applications.map((leave, index) => {
                                        const getStatusColor = (status: string) => {
                                            const statusColors = {
                                                pending: 'bg-yellow-100 text-yellow-800',
                                                approved: 'bg-green-100 text-green-800',
                                                rejected: 'bg-red-100 text-red-800'
                                            };
                                            return statusColors[status.toLowerCase() as keyof typeof statusColors] || statusColors.pending;
                                        };
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div>
                                                    <p className="text-sm font-medium">{leave.leave_type}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {leave.start_date === leave.end_date
                                                            ? `${formatDate(leave.start_date)} (${leave.total_days} day${leave.total_days > 1 ? 's' : ''})`
                                                            : `${formatDate(leave.start_date)} - ${formatDate(leave.end_date)} (${leave.total_days} day${leave.total_days > 1 ? 's' : ''})`
                                                        }
                                                    </p>
                                                </div>
                                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(leave.status)}`}>
                                                    {t(leave.status.charAt(0).toUpperCase() + leave.status.slice(1))}
                                                </span>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Calendar className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No leave applications found')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Additional Employee Info */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* My Awards */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Award className="h-5 w-5" />
                                {t('My Awards')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.recent_awards && stats.recent_awards.length > 0 ? (
                                    stats.recent_awards.map((award, index) => {
                                        const colors = ['bg-green-500', 'bg-blue-500', 'bg-purple-500', 'bg-orange-500', 'bg-red-500', 'bg-indigo-500'];
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div className="flex items-center gap-3">
                                                    <div className={`${colors[index % 6]} rounded-full p-1.5`}>
                                                        <Award className="h-3 w-3 text-white" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium">{award.award_type}</p>
                                                        <p className="text-xs text-gray-500">{formatDate(award.award_date)}</p>
                                                    </div>
                                                </div>
                                                <span className="px-2 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                                    {t('Received')}
                                                </span>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Award className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No awards found')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* My Warnings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Shield className="h-5 w-5" />
                                {t('My Warnings')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.recent_warnings && stats.recent_warnings.length > 0 ? (
                                    stats.recent_warnings.map((warning, index) => {
                                        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-pink-500', 'bg-rose-500'];
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div className="flex items-center gap-3">
                                                    <div className={`${colors[index % 5]} rounded-full p-1.5`}>
                                                        <Shield className="h-3 w-3 text-white" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium">{warning.warning_type}</p>
                                                        <p className="text-xs text-gray-500">{formatDate(warning.warning_date)}</p>
                                                    </div>
                                                </div>
                                                <span className="px-2 py-1 rounded-full text-sm bg-red-100 text-red-800">
                                                    {t('Warning')}
                                                </span>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Shield className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No warnings found')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Calendar and Announcements */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Calendar */}
                    <Card className="lg:col-span-8">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <CalendarDays className="h-5 w-5" />
                                {t('Company Calendar')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CalendarView
                                events={stats.calendar_events || []}
                                height={350}
                            />
                        </CardContent>
                    </Card>

                    {/* Announcements */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <FileText className="h-5 w-5" />
                                {t('Announcements')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 max-h-[700px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                                {stats.recent_announcements && stats.recent_announcements.length > 0 ? (
                                    stats.recent_announcements.map((announcement, index) => {
                                        const colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-red-500', 'bg-indigo-500'];
                                        const timeAgo = formatDate(announcement.created_at);
                                        return (
                                            <div key={index} className="flex items-start space-x-3 p-3 bg-white rounded-lg border border-gray-200">
                                                <div className={`${colors[index % 6]} rounded-full p-1.5`}>
                                                    <FileText className="h-3 w-3 text-white" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium">{announcement.title}</p>
                                                    <p className="text-xs text-gray-600">{announcement.description}</p>
                                                    <p className="text-xs text-gray-500">{timeAgo}</p>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <FileText className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No active announcements')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}