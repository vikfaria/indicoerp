import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PieChart } from '@/components/charts/PieChart';
import { BarChart } from '@/components/charts/BarChart';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { CalendarDays, Users, CheckCircle, Clock, BarChart3, PieChart as PieChartIcon } from 'lucide-react';

interface ProjectReportViewProps {
    project: {
        id: number;
        name: string;
        description?: string;
        start_date?: string;
        end_date?: string;
        status: string;
        budget?: number;
    };
    taskStatusData: Array<{
        name: string;
        value: number;
        color?: string;
    }>;
    taskPriorityData: Array<{
        name: string;
        value: number;
    }>;
    projectStats: {
        total_tasks: number;
        completed_tasks: number;
        in_progress_tasks: number;
        team_members: number;
    };
    usersData: Array<{
        id: number;
        name: string;
        assigned_tasks: number;
        done_tasks: number;
    }>;
    milestonesData: Array<{
        id: number;
        name: string;
        progress: number;
        cost: number;
        status: string;
        start_date?: string;
        end_date?: string;
    }>;
}

export default function View() {
    const { t } = useTranslation();
    const { project, taskStatusData, taskPriorityData, projectStats, usersData, milestonesData } = usePage<ProjectReportViewProps>().props;

    const getStatusColor = (status: string) => {
        const colors = {
            'active': 'bg-green-100 text-green-800',
            'completed': 'bg-blue-100 text-blue-800',
            'on_hold': 'bg-yellow-100 text-yellow-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const completionPercentage = projectStats.total_tasks > 0
        ? Math.round((projectStats.completed_tasks / projectStats.total_tasks) * 100)
        : 0;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Project'), url: route('project.dashboard.index')},
                { label: t('Project Report'), url: route('project.report.index') },
                { label: project.name }
            ]}
            pageTitle={`${t('Project Report')}: ${project.name}`}
            backUrl={route('project.report.index')}
        >
            <Head title={`${t('Project Report')}: ${project.name}`} />

            <div className="space-y-4">
                {/* Project Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Project Info Card */}
                    <Card className="shadow-sm">
                        <CardContent className="p-4">
                            <h3 className="font-semibold text-base mb-2">{project.name}</h3>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-medium">{t('Status')} :</span>
                                    <span className={`text-xs px-2 py-1 rounded-full ${
                                        project.status === 'Finished' ? 'bg-green-100 text-green-800' :
                                        project.status === 'Ongoing' ? 'bg-blue-100 text-blue-800' :
                                        project.status === 'Onhold' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-gray-100 text-gray-800'
                                    }`}>
                                        {t(project.status.replace('_', ' '))}
                                    </span>
                                </div>
                                {project.budget && (
                                    <div className="text-xs">
                                        {t('Budget')} : {formatCurrency(project.budget)}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Dates Card */}
                    <Card className="shadow-sm">
                        <CardContent className="p-4">
                            <h4 className="font-semibold text-sm mb-2 flex items-center gap-1">
                                <CalendarDays className="h-3 w-3" />
                                {t('Timeline')}
                            </h4>
                            <div className="space-y-1">
                                <div className="text-xs">
                                    <span className="font-medium">{t('Start')} :</span> {formatDate(project.start_date) || t('Not set')}
                                </div>
                                <div className="text-xs">
                                    <span className="font-medium">{t('End')} :</span> {formatDate(project.end_date) || t('Not set')}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Task Stats Card */}
                    <Card className="shadow-sm">
                        <CardContent className="p-4">
                            <h4 className="font-semibold text-sm mb-2 flex items-center gap-1">
                                <CheckCircle className="h-3 w-3" />
                                {t('Tasks')}
                            </h4>
                            <div className="space-y-1">
                                <div className="text-xs flex justify-between">
                                    <span>{t('Total')} :</span>
                                    <span className="font-semibold">{projectStats.total_tasks}</span>
                                </div>
                                <div className="text-xs flex justify-between">
                                    <span>{t('Completed')} :</span>
                                    <span className="font-semibold text-green-600">{projectStats.completed_tasks}</span>
                                </div>
                                <div className="text-xs flex justify-between">
                                    <span>{t('In Progress')} :</span>
                                    <span className="font-semibold text-orange-600">{projectStats.in_progress_tasks}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Progress Card */}
                    <Card className="shadow-sm">
                        <CardContent className="p-4">
                            <h4 className="font-semibold text-sm mb-2 flex items-center gap-1">
                                <Users className="h-3 w-3" />
                                {t('Progress')}
                            </h4>
                            <div className="space-y-2">
                                <div className="text-xs flex justify-between">
                                    <span>{t('Team Member')} :</span>
                                    <span className="font-semibold">{projectStats.team_members}</span>
                                </div>
                                <div className="space-y-1">
                                    <div className="flex justify-between text-xs">
                                        <span>{t('Complete')} :</span>
                                        <span className="font-semibold">{completionPercentage}%</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-1.5">
                                        <div
                                            className="bg-green-600 h-1.5 rounded-full transition-all duration-300"
                                            style={{ width: `${completionPercentage}%` }}
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Task Status Pie Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <PieChartIcon className="h-4 w-4" />
                                {t('Task Status Distribution')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-3">
                            {taskStatusData.length > 0 ? (
                                <div className="flex items-start justify-between">
                                    {/* Pie Chart - Left Side */}
                                    <div className="flex-1">
                                        <PieChart
                                            data={taskStatusData}
                                            dataKey="value"
                                            nameKey="name"
                                            height={350}
                                            outerRadius={120}
                                            showLabels={false}
                                            showLegend={false}
                                            showTooltip={true}
                                            colors={taskStatusData.map(item => item.color)}
                                        />
                                    </div>

                                    {/* Status List - Right Side */}
                                    <div className="w-44 space-y-3 mt-8">
                                        {taskStatusData.map((item, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div className="flex items-center gap-3">
                                                    <div
                                                        className="w-4 h-4 rounded-full"
                                                        style={{ backgroundColor: item.color || ['#3b82f6', '#10b77f', '#f59e0b', '#ef4444', '#8b5cf6'][index % 5] }}
                                                    ></div>
                                                    <span className="text-sm font-medium">{item.name}</span>
                                                </div>
                                                <span className="text-base font-bold">{item.value}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-64 text-muted-foreground">
                                    <div className="text-center">
                                        <PieChartIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No task data available')}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Task Priority Bar Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <BarChart3 className="h-4 w-4" />
                                {t('Task Priority Distribution')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-0 flex justify-center">
                            {taskPriorityData.length > 0 ? (
                                <div className="w-full">
                                    <BarChart
                                        data={taskPriorityData}
                                        dataKey="value"
                                        xAxisKey="name"
                                        color="#3b82f6"
                                        height={320}
                                        showGrid={true}
                                        showTooltip={true}
                                    />
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-48 text-muted-foreground">
                                    <div className="text-center">
                                        <BarChart3 className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No priority data available')}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Users and Milestones Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Users Table */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">{t('Users')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className={`overflow-x-auto ${usersData?.length > 4 ? 'max-h-48 overflow-y-auto' : ''}`}>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2 font-medium">{t('NAME')}</th>
                                            <th className="text-left py-2 font-medium">{t('ASSIGNED TASKS')}</th>
                                            <th className="text-left py-2 font-medium">{t('DONE TASKS')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {usersData?.length > 0 ? (
                                            usersData.map((user) => (
                                                <tr key={user.id} className="border-b last:border-b-0">
                                                    <td className="py-3">{user.name}</td>
                                                    <td className="py-3">{user.assigned_tasks}</td>
                                                    <td className="py-3">{user.done_tasks}</td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={3} className="py-6 text-center text-muted-foreground">
                                                    {t('No users assigned to this project')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Milestones Table */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">{t('Milestones')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className={`overflow-x-auto ${milestonesData?.length > 4 ? 'max-h-48 overflow-y-auto' : ''}`}>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2 font-medium">{t('NAME')}</th>
                                            <th className="text-left py-2 font-medium">{t('PROGRESS')}</th>
                                            <th className="text-left py-2 font-medium">{t('COST')}</th>
                                            <th className="text-left py-2 font-medium">{t('STATUS')}</th>
                                            <th className="text-left py-2 font-medium">{t('START DATE')}</th>
                                            <th className="text-left py-2 font-medium">{t('END DATE')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {milestonesData?.length > 0 ? (
                                            milestonesData.map((milestone) => (
                                                <tr key={milestone.id} className="border-b last:border-b-0">
                                                    <td className="py-3">{milestone.name}</td>
                                                    <td className="py-3">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-16 bg-gray-200 rounded-full h-2">
                                                                <div className="bg-blue-600 h-2 rounded-full" style={{width: `${milestone.progress}%`}}></div>
                                                            </div>
                                                            <span className="text-xs">{milestone.progress}%</span>
                                                        </div>
                                                    </td>
                                                    <td className="py-3">{formatCurrency(milestone.cost)}</td>
                                                    <td className="py-3">
                                                        <span className={`px-2 py-1 rounded-full text-xs ${
                                                            milestone.status === 'Complete' ? 'bg-green-100 text-green-800' :
                                                            milestone.status === 'Ongoing' ? 'bg-yellow-100 text-yellow-800' :
                                                            'bg-red-100 text-red-800'
                                                        }`}>
                                                            {t(milestone.status)}
                                                        </span>
                                                    </td>
                                                    <td className="py-3">{formatDate(milestone.start_date) || '-'}</td>
                                                    <td className="py-3">{formatDate(milestone.end_date) || '-'}</td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={6} className="py-6 text-center text-muted-foreground">
                                                    {t('No milestones found for this project')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
