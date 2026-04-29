import { Head, usePage, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar, Users, CheckCircle, Clock, AlertCircle, FileText, UserCheck, Target, Activity, Star } from "lucide-react";
import { formatDate } from '@/utils/helpers';

interface StaffDashboardProps {
    dashboardData: {
        overview: {
            assignedInterviews: number;
            pendingInterviews: number;
            assignedOnboardings: number;
            completedOnboardings: number;
            conductedAssessments: number;
            submittedFeedbacks: number;
        };
        taskStatus: {
            pendingInterviews: number;
            completedInterviews: number;
            pendingOnboardings: number;
            completedOnboardings: number;
        };
        calendarEvents: Array<{
            id: number;
            title: string;
            date: string;
            time: string;
            status: string;
        }>;
        recentActivities: {
            recentInterviews: Array<{
                id: number;
                candidate_name: string;
                job_title: string;
                scheduled_date: string;
                status: string;
            }>;
            recentOnboardings: Array<{
                id: number;
                candidate_name: string;
                checklist_name: string;
                start_date: string;
                status: string;
            }>;
        };
        alerts: {
            overdueInterviews: number;
            pendingFeedbacks: number;
            upcomingInterviews: number;
        };
    };
    userSlug: string;
}

export default function StaffDashboard() {
    const { t } = useTranslation();
    const { dashboardData } = usePage<StaffDashboardProps>().props;

    const getStatusColor = (status: string) => {
        switch(status) {
            case '0': return 'bg-blue-100 text-blue-800'; // Scheduled
            case '1': return 'bg-green-100 text-green-800'; // Completed
            case '2': return 'bg-red-100 text-red-800'; // Cancelled
            case '3': return 'bg-orange-100 text-orange-800'; // No-show
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = (status: string) => {
        const options: any = { "0": "Scheduled", "1": "Completed", "2": "Cancelled", "3": "No-show" };
        return t(options[status] || 'Scheduled');
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                { label: t('Dashboard') }
            ]}
            pageTitle={t('My Recruitment Dashboard')}
        >
            <Head title={t('Recruitment Dashboard')} />

            <div className="space-y-6">
                {/* Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link href={route('recruitment.interviews.index')}>
                        <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200 hover:shadow-md transition-shadow cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-green-700">{t('My Interviews')}</CardTitle>
                                <Calendar className="h-4 w-4 text-green-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-800">{dashboardData.overview.assignedInterviews}</div>
                                <p className="text-xs text-green-600">
                                    {dashboardData.overview.pendingInterviews} {t('pending')}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('recruitment.candidate-onboardings.index')}>
                        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-md transition-shadow cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-blue-700">{t('My Onboardings')}</CardTitle>
                                <UserCheck className="h-4 w-4 text-blue-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-blue-800">{dashboardData.overview.assignedOnboardings}</div>
                                <p className="text-xs text-blue-600">
                                    {dashboardData.overview.completedOnboardings} {t('completed')}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('recruitment.candidate-assessments.index')}>
                        <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-md transition-shadow cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-purple-700">{t('Assessments')}</CardTitle>
                                <Target className="h-4 w-4 text-purple-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-purple-800">{dashboardData.overview.conductedAssessments}</div>
                                <p className="text-xs text-purple-600">
                                    {t('conducted')}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('recruitment.interview-feedbacks.index')}>
                        <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-md transition-shadow cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-orange-700">{t('Feedbacks')}</CardTitle>
                                <Star className="h-4 w-4 text-orange-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-800">{dashboardData.overview.submittedFeedbacks}</div>
                                <p className="text-xs text-orange-600">
                                    {t('submitted')}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                {/* Alerts */}
                {(dashboardData.alerts.pendingFeedbacks > 0 || dashboardData.alerts.upcomingInterviews > 0) && (
                    <Card className="border-amber-200 bg-amber-50/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-amber-800">
                                <AlertCircle className="h-5 w-5" />
                                {t('Attention Required')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {dashboardData.alerts.pendingFeedbacks > 0 && (
                                    <div className="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-200">
                                        <div className="flex items-center gap-3">
                                            <Clock className="h-5 w-5 text-amber-600" />
                                            <div>
                                                <p className="font-medium text-amber-900">{t('Pending Interview Feedbacks')}</p>
                                                <p className="text-sm text-amber-700">{dashboardData.alerts.pendingFeedbacks} {t('feedbacks need submission')}</p>
                                            </div>
                                        </div>
                                        <Link href={route('recruitment.interview-feedbacks.index')}>
                                            <Button size="sm" variant="outline" className="border-amber-300 text-amber-700 hover:bg-amber-100">
                                                {t('Review')}
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                                {dashboardData.alerts.upcomingInterviews > 0 && (
                                    <div className="flex items-center justify-between p-3 bg-white rounded-lg border border-blue-200">
                                        <div className="flex items-center gap-3">
                                            <Calendar className="h-5 w-5 text-blue-600" />
                                            <div>
                                                <p className="font-medium text-blue-900">{t('Upcoming Interviews')}</p>
                                                <p className="text-sm text-blue-700">{dashboardData.alerts.upcomingInterviews} {t('interviews scheduled')}</p>
                                            </div>
                                        </div>
                                        <Link href={route('recruitment.interviews.index')}>
                                            <Button size="sm" variant="outline" className="border-blue-300 text-blue-700 hover:bg-blue-100">
                                                {t('View')}
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Interviews */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                {t('Recent Interviews')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {dashboardData.recentActivities.recentInterviews.length > 0 ? (
                                <div className="space-y-3">
                                    {dashboardData.recentActivities.recentInterviews.map((interview) => (
                                        <div key={interview.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <p className="font-medium">{interview.candidate_name}</p>
                                                <p className="text-sm text-muted-foreground">{interview.job_title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {interview.scheduled_date ? formatDate(interview.scheduled_date) : '-'}
                                                </p>
                                            </div>
                                            <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(interview.status)}`}>
                                                {getStatusText(interview.status)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-center py-4">{t('No recent interviews')}</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Onboardings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {t('Recent Onboardings')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {dashboardData.recentActivities.recentOnboardings.length > 0 ? (
                                <div className="space-y-3">
                                    {dashboardData.recentActivities.recentOnboardings.map((onboarding) => (
                                        <div key={onboarding.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <p className="font-medium">{onboarding.candidate_name}</p>
                                                <p className="text-sm text-muted-foreground">{onboarding.checklist_name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {onboarding.start_date ? formatDate(onboarding.start_date) : '-'}
                                                </p>
                                            </div>
                                            <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(onboarding.status)}`}>
                                                {getStatusText(onboarding.status)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-center py-4">{t('No recent onboardings')}</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Task Status Summary */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5 text-blue-600" />
                            {t('My Task Progress')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {/* Interview Progress */}
                            <div className="relative">
                                <div className="flex items-center justify-between p-4 bg-white rounded-lg border border-green-200">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-green-500 rounded-lg">
                                            <Calendar className="h-4 w-4 text-white" />
                                        </div>
                                        <div>
                                            <span className="font-medium text-green-900">{t('Interview Tasks')}</span>
                                            <p className="text-xs text-green-700">{t('Assigned interviews')}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="flex gap-2 mb-1">
                                            <span className="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">{dashboardData.taskStatus.pendingInterviews} {t('Pending')}</span>
                                            <span className="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">{dashboardData.taskStatus.completedInterviews} {t('Done')}</span>
                                        </div>
                                        <p className="text-xs text-green-700">{dashboardData.overview.assignedInterviews} {t('Total')}</p>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Onboarding Progress */}
                            <div className="relative">
                                <div className="flex items-center justify-between p-4 bg-white rounded-lg border border-blue-200">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-blue-500 rounded-lg">
                                            <UserCheck className="h-4 w-4 text-white" />
                                        </div>
                                        <div>
                                            <span className="font-medium text-blue-900">{t('Onboarding Tasks')}</span>
                                            <p className="text-xs text-blue-700">{t('Buddy assignments')}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="flex gap-2 mb-1">
                                            <span className="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">{dashboardData.taskStatus.pendingOnboardings} {t('Pending')}</span>
                                            <span className="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">{dashboardData.taskStatus.completedOnboardings} {t('Done')}</span>
                                        </div>
                                        <p className="text-xs text-blue-700">{dashboardData.overview.assignedOnboardings} {t('Total')}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}