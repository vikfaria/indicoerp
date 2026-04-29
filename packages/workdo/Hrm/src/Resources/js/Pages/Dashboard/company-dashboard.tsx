import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { LineChart, PieChart, BarChart } from '@/components/charts';
import CalendarView from "@/components/calendar-view";
import { 
    Users, 
    UserCheck, 
    UserX, 
    Clock, 
    Calendar, 
    DollarSign, 
    TrendingUp, 
    TrendingDown,
    Award,
    AlertTriangle,
    FileText,
    Building,
    Briefcase,
    CalendarDays,
    CreditCard,
    ArrowUpRight,
    ArrowDownRight,
    MoreHorizontal,
    User as UserIcon
} from 'lucide-react';
import { getImagePath,formatDate, formatTime,formatDateTime } from '@/utils/helpers';

interface HrmProps {
    message: string;
    stats: {
        total_employees: number;
        present_today: number;
        absent_today: number;
        absent_yesterday: number;
        on_leave: number;
        pending_leaves: number;
        total_branches: number;
        total_departments: number;
        total_promotions: number;
        terminations: number;
        department_distribution: Array<{
            name: string;
            value: number;
        }>;
        calendar_events: Array<{
            id: number;
            title: string;
            startDate: string;
            endDate: string;
            time: string;
            description: string;
            type: string;
            color: string;
        }>;
        recent_leave_applications: Array<{
            id: number;
            employee_name: string;
            leave_type: string;
            start_date: string;
            end_date: string;
            total_days: number;
            status: string;
            created_at: string;
        }>;
        recent_announcements: Array<{
            id: number;
            title: string;
            description: string;
            created_at: string;
        }>;
        employees_on_leave_today: Array<{
            name: string;
            leave_type: string;
            days: number;
            profile?: string;
        }>;
        employees_without_attendance: Array<{
            name: string;
            department: string;
            profile?: string;
        }>;
    };
}

export default function HrmIndex({ message, stats }: HrmProps) {
    const { t } = useTranslation();
    
    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('HRM Dashboard')}]}
            pageTitle={t('HRM Dashboard')}
        >
            <Head title={t('HRM Dashboard')} />
            
            <div className="space-y-6">
                {/* Key Metrics Row */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div onClick={() => window.location.href = route('hrm.employees.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-blue-700">{t('Total Employees')}</CardTitle>
                                <Users className="h-5 w-5 text-blue-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-blue-900">{stats.total_employees}</div>
                                <div className="flex items-center text-xs text-blue-600 mt-1">
                                    <span>{t('Active employees')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.attendances.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-green-700">{t('Present Today')}</CardTitle>
                                <UserCheck className="h-5 w-5 text-green-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-green-900">{stats.present_today}</div>
                                <div className="flex items-center text-xs text-green-600 mt-1">
                                    <span>{((stats.present_today / stats.total_employees) * 100).toFixed(1)}% {t('attendance rate')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.attendances.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-red-50 to-red-100 border-red-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-red-700">{t('Absent Today')}</CardTitle>
                                <UserX className="h-5 w-5 text-red-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-red-900">{stats.absent_today}</div>
                                <div className="flex items-center text-xs text-red-600 mt-1">
                                    {stats.absent_today > stats.absent_yesterday ? (
                                        <ArrowUpRight className="h-3 w-3 mr-1" />
                                    ) : (
                                        <ArrowDownRight className="h-3 w-3 mr-1" />
                                    )}
                                    <span>{stats.absent_today - stats.absent_yesterday > 0 ? '+' : ''}{stats.absent_today - stats.absent_yesterday} {t('from yesterday')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.leave-applications.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-purple-700">{t('On Leave')}</CardTitle>
                                <Calendar className="h-5 w-5 text-purple-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-purple-900">{stats.on_leave}</div>
                                <div className="flex items-center text-xs text-purple-600 mt-1">
                                    <span>{stats.pending_leaves} {t('pending approvals')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Secondary Metrics Row */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div onClick={() => window.location.href = route('hrm.branches.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-teal-50 to-teal-100 border-teal-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-teal-700">{t('Total Branch')}</CardTitle>
                                <Building className="h-5 w-5 text-teal-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-teal-900">{stats.total_branches}</div>
                                <div className="flex items-center text-xs text-teal-600 mt-1">
                                    <span>{t('Active branches')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.departments.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-indigo-50 to-indigo-100 border-indigo-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-indigo-700">{t('Total Department')}</CardTitle>
                                <Briefcase className="h-5 w-5 text-indigo-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-indigo-900">{stats.total_departments}</div>
                                <div className="flex items-center text-xs text-indigo-600 mt-1">
                                    <span>{t('Across all branches')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.promotions.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-emerald-700">{t('Total Promotions')}</CardTitle>
                                <TrendingUp className="h-5 w-5 text-emerald-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-emerald-900">{stats.total_promotions}</div>
                                <div className="flex items-center text-xs text-emerald-600 mt-1">
                                    <span>{t('This year')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <div onClick={() => window.location.href = route('hrm.terminations.index')} className="cursor-pointer">
                        <Card className="bg-gradient-to-r from-rose-50 to-rose-100 border-rose-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-semibold text-rose-700">{t('Terminations')}</CardTitle>
                                <TrendingDown className="h-5 w-5 text-rose-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold text-rose-900">{stats.terminations}</div>
                                <div className="flex items-center text-xs text-rose-600 mt-1">
                                    <span>{t('This month')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Attendance Trends Chart */}
                {/* <Card>
                    <CardHeader>
                        <CardTitle>{t('Attendance Trends')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            data={[
                                { month: 'Jan', present: 230, absent: 17, leave: 15 },
                                { month: 'Feb', present: 235, absent: 12, leave: 18 },
                                { month: 'Mar', present: 240, absent: 7, leave: 20 },
                                { month: 'Apr', present: 238, absent: 9, leave: 16 },
                                { month: 'May', present: 242, absent: 5, leave: 14 },
                                { month: 'Jun', present: 234, absent: 13, leave: 18 },
                                { month: 'Jul', present: 245, absent: 8, leave: 12 },
                                { month: 'Aug', present: 241, absent: 6, leave: 16 },
                                { month: 'Sep', present: 239, absent: 11, leave: 19 },
                                { month: 'Oct', present: 243, absent: 4, leave: 15 },
                                { month: 'Nov', present: 237, absent: 10, leave: 17 },
                                { month: 'Dec', present: 234, absent: 13, leave: 18 }
                            ]}
                            height={300}
                            showTooltip={true}
                            showGrid={true}
                            lines={[
                                { dataKey: 'present', color: '#10b77f', name: 'Present' },
                                { dataKey: 'absent', color: '#ef4444', name: 'Absent' },
                                { dataKey: 'leave', color: '#f59e0b', name: 'On Leave' }
                            ]}
                            xAxisKey="month"
                            showLegend={true}
                        />
                    </CardContent>
                </Card> */}

                {/* Charts and Analytics */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Department Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Building className="h-5 w-5" />
                                {t('Department Distribution')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-4 pr-2">
                                {stats.department_distribution && stats.department_distribution.length > 0 ? (
                                    stats.department_distribution.map((dept, index) => {
                                        const maxValue = Math.max(...stats.department_distribution.map(d => d.value));
                                        const percentage = (dept.value / maxValue) * 100;
                                        const colors = ['#3b82f6', '#10b77f', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#84cc16'];
                                        
                                        return (
                                            <div key={index} className="space-y-2">
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm font-medium text-gray-700">{dept.name}</span>
                                                    <span className="text-sm font-bold text-gray-900">{dept.value}</span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className="h-2 rounded-full transition-all duration-300" 
                                                        style={{ 
                                                            width: `${percentage}%`, 
                                                            backgroundColor: colors[index % 8] 
                                                        }}
                                                    ></div>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Briefcase className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No departments found')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>



                    {/* Quick Actions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Briefcase className="h-5 w-5" />
                                {t('Quick Actions')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.employees.create')}
                                >
                                    <Users className="h-4 w-4 mr-2" />
                                    {t('Add New Employee')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.attendances.index')}
                                >
                                    <Clock className="h-4 w-4 mr-2" />
                                    {t('Mark Attendance')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.leave-applications.index')}
                                >
                                    <Calendar className="h-4 w-4 mr-2" />
                                    {t('Apply for Leave')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.payrolls.index')}
                                >
                                    <CreditCard className="h-4 w-4 mr-2" />
                                    {t('Process Payroll')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.promotions.index')}
                                >
                                    <TrendingUp className="h-4 w-4 mr-2" />
                                    {t('Create Promotion')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.resignations.index')}
                                >
                                    <TrendingDown className="h-4 w-4 mr-2" />
                                    {t('Create Resignation')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.holidays.index')}
                                >
                                    <CalendarDays className="h-4 w-4 mr-2" />
                                    {t('Create Holiday')}
                                </Button>
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => window.location.href = route('hrm.warnings.index')}
                                >
                                    <AlertTriangle className="h-4 w-4 mr-2" />
                                    {t('Create Warning')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Employee Status Sections */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Employees on Leave Today */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Calendar className="h-5 w-5" />
                                {t('Employees on Leave')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.employees_on_leave_today && stats.employees_on_leave_today.length > 0 ? (
                                    stats.employees_on_leave_today.map((employee, index) => {
                                        const colors = ['bg-purple-500', 'bg-blue-500', 'bg-green-500', 'bg-orange-500', 'bg-pink-500'];
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 border flex-shrink-0">
                                                        {employee.profile ? (
                                                            <img
                                                                src={getImagePath(employee.profile)}
                                                                alt={employee.name}
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            <div className={`w-full h-full ${colors[index % 5]} flex items-center justify-center text-white text-sm font-medium`}>
                                                                {employee.name.charAt(0).toUpperCase()}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{employee.name}</p>
                                                        <p className="text-xs text-gray-500">{employee.leave_type}</p>
                                                    </div>
                                                </div>
                                                <div className="text-xs text-gray-600">
                                                    {employee.days} {t('days')}
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <Calendar className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('No employees on leave today')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Employees Without Attendance */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <UserX className="h-5 w-5" />
                                {t('Missing Attendance Today')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3 pr-2">
                                {stats.employees_without_attendance && stats.employees_without_attendance.length > 0 ? (
                                    stats.employees_without_attendance.map((employee, index) => {
                                        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-pink-500', 'bg-rose-500'];
                                        return (
                                            <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 border flex-shrink-0">
                                                        {employee.profile ? (
                                                            <img
                                                                src={getImagePath(employee.profile)}
                                                                alt={employee.name}
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            <div className={`w-full h-full ${colors[index % 5]} flex items-center justify-center text-white text-sm font-medium`}>
                                                                {employee.name.charAt(0).toUpperCase()}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{employee.name}</p>
                                                        <p className="text-xs text-gray-500">{employee.employee_id}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex items-center justify-center h-40 text-gray-500">
                                        <div className="text-center">
                                            <UserCheck className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                            <p className="text-sm">{t('All employees marked attendance')}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Calendar and Recent Activities */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Calendar View */}
                    <Card className="lg:col-span-8">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <CalendarDays className="h-5 w-5" />
                                {t('Events & Holidays Calendar')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CalendarView
                                events={stats.calendar_events}
                                height={400}
                            />
                        </CardContent>
                    </Card>

                    {/* Recent Activities & Notifications */}
                    <div className="lg:col-span-4 space-y-6">
                        {/* Recent Leave Applications */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                    <Calendar className="h-5 w-5" />
                                    {t('Recent Leave Applications')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3">
                                    {stats.recent_leave_applications && stats.recent_leave_applications.length > 0 ? (
                                        stats.recent_leave_applications.map((leave, index) => {
                                            const getStatusColor = (status: string) => {
                                                switch (status.toLowerCase()) {
                                                    case 'pending': return { icon: 'bg-yellow-500', badge: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
                                                    case 'approved': return { icon: 'bg-green-500', badge: 'bg-green-100 text-green-800 border-green-200' };
                                                    case 'rejected': return { icon: 'bg-red-500', badge: 'bg-red-100 text-red-800 border-red-200' };
                                                    default: return { icon: 'bg-blue-500', badge: 'bg-blue-100 text-blue-800 border-blue-200' };
                                                }
                                            };
                                            const colors = getStatusColor(leave.status);
                                            return (
                                                <div key={index} className="flex items-start justify-between p-3 bg-white rounded-lg border border-gray-200">
                                                    <div className="flex items-start space-x-3">
                                                        <div className={`${colors.icon} rounded-full p-1.5`}>
                                                            <Calendar className="h-3 w-3 text-white" />
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-medium">{leave.employee_name} - {leave.leave_type}</p>
                                                            <p className="text-xs text-gray-600">
                                                                {leave.start_date === leave.end_date 
                                                                    ? `${formatDate(leave.start_date)} (${leave.total_days} day${leave.total_days > 1 ? 's' : ''})`
                                                                    : `${formatDate(leave.start_date)} - ${formatDate(leave.end_date)} (${leave.total_days} day${leave.total_days > 1 ? 's' : ''})`
                                                                }
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <span className={`px-2 py-1 rounded-full text-sm ${colors.badge}`}>
                                                        {t(leave.status.charAt(0).toUpperCase() + leave.status.slice(1))}
                                                    </span>
                                                </div>
                                            );
                                        })
                                    ) : (
                                        <div className="flex items-center justify-center h-40 text-gray-500">
                                            <div className="text-center">
                                                <Calendar className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                                                <p className="text-sm">{t('No recent leave applications')}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Recent Announcements */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                    <FileText className="h-5 w-5" />
                                    {t('Announcements')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 space-y-3">
                                    {stats.recent_announcements && stats.recent_announcements.length > 0 ? (
                                        stats.recent_announcements.map((announcement, index) => {
                                            const colors = ['bg-purple-500', 'bg-blue-500', 'bg-green-500', 'bg-orange-500', 'bg-red-500', 'bg-indigo-500'];
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


            </div>
        </AuthenticatedLayout>
    );
}