import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
    User as UserIcon,
    BadgeCheck,
    Clock3,
    Gauge,
    Target,
    Sparkles,
    ArrowRight,
    Building2
} from 'lucide-react';
import { getImagePath,formatDate, formatTime,formatDateTime } from '@/utils/helpers';
import { cn } from '@/lib/utils';

interface HrmProps {
    message: string;
    configuration_progress?: {
        progress_percent: number;
        completed_total: number;
        pending_total: number;
        blocked_total: number;
        next_step: {
            key: string;
            label: string;
            description: string;
            href: string;
            state: string;
            state_label: string;
            available: boolean;
        } | null;
        steps: Array<{
            key: string;
            label: string;
            description: string;
            href: string;
            completed: boolean;
            available: boolean;
            order: number;
            state: string;
            state_label: string;
            evidence: string;
        }>;
    } | null;
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

export default function HrmIndex({ message, stats, configuration_progress }: HrmProps) {
    const { t } = useTranslation();
    const configurationProgress = configuration_progress ?? null;
    const orderedSteps = useMemo(
        () => [...(configurationProgress?.steps ?? [])].sort((left, right) => left.order - right.order),
        [configurationProgress]
    );
    const nextStep = useMemo(
        () => configurationProgress?.next_step ?? orderedSteps.find((step) => !step.completed) ?? null,
        [configurationProgress, orderedSteps]
    );
    const [isProgressOpen, setIsProgressOpen] = useState(Boolean(configurationProgress && configurationProgress.progress_percent < 100));

    const openStep = (href?: string | null) => {
        if (!href) {
            return;
        }

        window.location.href = href;
    };
    
    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('HRM Dashboard')}]}
            pageTitle={t('HRM Dashboard')}
        >
            <Head title={t('HRM Dashboard')} />
            
            <div className="space-y-6">
                {configurationProgress && (
                    <Card className="overflow-hidden border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-cyan-50 shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                                <div className="space-y-4">
                                    <div className="flex items-center gap-2 text-emerald-700">
                                        <Sparkles className="h-4 w-4" />
                                        <span className="text-xs font-semibold uppercase tracking-[0.24em]">{t('HRM setup')}</span>
                                    </div>
                                    <div className="space-y-2">
                                        <h2 className="text-2xl font-semibold tracking-tight text-slate-900">
                                            {t('Prepare the HRM module before using it')}
                                        </h2>
                                        <p className="max-w-3xl text-sm leading-6 text-slate-600">
                                            {t('Follow the recommended setup order to configure branches, teams, attendance, leave and payroll without breaking the flow for new users.')}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                        <Badge variant="outline" className="border-emerald-200 bg-emerald-50 text-emerald-700">
                                            {`${configurationProgress.progress_percent.toFixed(1)}% ${t('complete')}`}
                                        </Badge>
                                        <Badge variant="outline" className="border-slate-200 bg-white text-slate-700">
                                            {`${configurationProgress.completed_total} ${t('completed')}`}
                                        </Badge>
                                        <Badge variant="outline" className="border-slate-200 bg-white text-slate-700">
                                            {`${configurationProgress.pending_total} ${t('pending')}`}
                                        </Badge>
                                    </div>
                                    <div className="space-y-2">
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-white shadow-inner">
                                            <div
                                                className="h-full rounded-full bg-emerald-500 transition-all"
                                                style={{ width: `${Math.max(0, Math.min(100, configurationProgress.progress_percent))}%` }}
                                            />
                                        </div>
                                        <p className="text-xs text-slate-500">
                                            {nextStep
                                                ? `${t('Next recommended step')}: ${nextStep.label}`
                                                : t('All HRM setup steps are complete.')}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                                    <Button onClick={() => setIsProgressOpen(true)} className="bg-slate-900 text-white hover:bg-slate-800">
                                        <Target className="h-4 w-4 mr-2" />
                                        {t('View setup progress')}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => openStep(nextStep?.href ?? route('hrm.branches.index'))}
                                        disabled={!nextStep?.available || !nextStep?.href}
                                    >
                                        <ArrowRight className="h-4 w-4 mr-2" />
                                        {nextStep ? t('Continue setup') : t('Open setup')}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

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

                <Dialog open={isProgressOpen} onOpenChange={setIsProgressOpen}>
                    <DialogContent className="max-w-6xl">
                        <DialogHeader>
                            <div className="flex items-center gap-2">
                                <div className="rounded-xl bg-emerald-100 p-2 text-emerald-700">
                                    <Gauge className="h-5 w-5" />
                                </div>
                                <div>
                                    <DialogTitle>{t('HRM configuration progress')}</DialogTitle>
                                    <DialogDescription>
                                        {t('Complete the steps in priority order to prepare the module for daily use.')}
                                    </DialogDescription>
                                </div>
                            </div>
                        </DialogHeader>

                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">{t('Progress')}</p>
                                <p className="mt-2 text-3xl font-semibold text-slate-900">{configurationProgress ? `${configurationProgress.progress_percent.toFixed(1)}%` : '0.0%'}</p>
                                <p className="mt-1 text-xs text-slate-500">{t('Overall setup completion')}</p>
                            </div>
                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p className="text-xs font-medium uppercase tracking-[0.18em] text-emerald-700">{t('Completed')}</p>
                                <p className="mt-2 text-3xl font-semibold text-emerald-900">{configurationProgress?.completed_total ?? 0}</p>
                                <p className="mt-1 text-xs text-emerald-700">{t('Steps already configured')}</p>
                            </div>
                            <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <p className="text-xs font-medium uppercase tracking-[0.18em] text-amber-700">{t('Pending')}</p>
                                <p className="mt-2 text-3xl font-semibold text-amber-900">{configurationProgress?.pending_total ?? 0}</p>
                                <p className="mt-1 text-xs text-amber-700">{t('Steps still required')}</p>
                            </div>
                            <div className="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                                <p className="text-xs font-medium uppercase tracking-[0.18em] text-rose-700">{t('Blocked')}</p>
                                <p className="mt-2 text-3xl font-semibold text-rose-900">{configurationProgress?.blocked_total ?? 0}</p>
                                <p className="mt-1 text-xs text-rose-700">{t('Steps blocked by permissions')}</p>
                            </div>
                        </div>

                        <div className="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                            {orderedSteps.length > 0 ? orderedSteps.map((step, index) => (
                                <div
                                    key={step.key}
                                    className={cn(
                                        'rounded-2xl border p-4 transition',
                                        step.completed
                                            ? 'border-emerald-200 bg-emerald-50/60'
                                            : step.state === 'blocked'
                                                ? 'border-rose-200 bg-rose-50/60'
                                                : 'border-amber-200 bg-amber-50/60'
                                    )}
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex items-start gap-4">
                                            <div
                                                className={cn(
                                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold',
                                                    step.completed
                                                        ? 'bg-emerald-600 text-white'
                                                        : step.state === 'blocked'
                                                            ? 'bg-rose-600 text-white'
                                                            : 'bg-amber-500 text-white'
                                                )}
                                            >
                                                {String(index + 1).padStart(2, '0')}
                                            </div>
                                            <div className="space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="text-base font-semibold text-slate-900">{step.label}</h3>
                                                    <Badge
                                                        variant="outline"
                                                        className={cn(
                                                            step.completed
                                                                ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                                                                : step.state === 'blocked'
                                                                    ? 'border-rose-200 bg-rose-100 text-rose-700'
                                                                    : 'border-amber-200 bg-amber-100 text-amber-700'
                                                        )}
                                                    >
                                                        {step.completed ? (
                                                            <span className="inline-flex items-center gap-1">
                                                                <BadgeCheck className="h-3.5 w-3.5" />
                                                                {t('Completed')}
                                                            </span>
                                                        ) : step.state === 'blocked' ? (
                                                            <span className="inline-flex items-center gap-1">
                                                                <Clock className="h-3.5 w-3.5" />
                                                                {t('Blocked')}
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1">
                                                                <Clock3 className="h-3.5 w-3.5" />
                                                                {t('Pending')}
                                                            </span>
                                                        )}
                                                    </Badge>
                                                </div>
                                                <p className="max-w-3xl text-sm leading-6 text-slate-600">{step.description}</p>
                                                <p className="text-xs text-slate-500">{step.evidence}</p>
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-3 lg:items-end">
                                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                                <span className="font-medium uppercase tracking-[0.16em]">{t('Priority')}</span>
                                                <span>{String(index + 1).padStart(2, '0')}</span>
                                            </div>
                                            <Button
                                                size="sm"
                                                variant={step.completed ? 'outline' : 'default'}
                                                className={cn(
                                                    step.completed
                                                        ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'
                                                        : step.state === 'blocked'
                                                            ? 'border-rose-200 bg-rose-600 text-white hover:bg-rose-700'
                                                            : 'bg-slate-900 text-white hover:bg-slate-800'
                                                )}
                                                onClick={() => openStep(step.href)}
                                                disabled={!step.available || !step.href}
                                            >
                                                {step.available ? (
                                                    <>
                                                        {step.completed ? t('Open') : t('Go to step')}
                                                        <ArrowRight className="h-4 w-4 ml-2" />
                                                    </>
                                                ) : (
                                                    t('Permission required')
                                                )}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            )) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                                    {t('No HRM setup steps are available for this company.')}
                                </div>
                            )}
                        </div>
                    </DialogContent>
                </Dialog>

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
