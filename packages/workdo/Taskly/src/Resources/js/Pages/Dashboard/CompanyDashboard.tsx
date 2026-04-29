import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, usePage, router } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { LineChart, PieChart } from '@/components/charts';
import { FolderKanban, CheckSquare, Bug, Users, UserCheck } from 'lucide-react';

interface Task {
    id: number;
    title: string;
    priority: string;
    project: string;
    stage: string;
    stage_color?: string;
    assignee: string;
    created_at: string;
    is_completed: boolean;
}

interface ChartData {
    name: string;
    value: number;
    color?: string;
}

interface TeamMember {
    name: string;
    total_tasks: number;
    completed_tasks: number;
    completion_rate: number;
}

interface CompanyDashboardProps {
    stats: {
        total_projects: number;
        total_tasks: number;
        total_bugs: number;
        total_users: number;
        total_clients: number;
        completed_tasks: number;
        completion_rate: number;
        overdue_projects: number;
    };
    recentTasks: Task[];
    projectStatus: ChartData[];
    taskPriority: ChartData[];
    teamPerformance: TeamMember[];
    monthlyProgress: Array<{ month: string; created: number; completed: number }>;
    bugStats: { open: number; resolved: number };
}

export default function CompanyDashboard() {
    const { t } = useTranslation();
    const { stats, recentTasks, projectStatus, taskPriority, teamPerformance, monthlyProgress, bugStats } = usePage<CompanyDashboardProps>().props;

    const getPriorityColor = (priority: string) => {
        switch (priority.toLowerCase()) {
            case 'high': return 'bg-red-500 text-white';
            case 'medium': return 'bg-yellow-500 text-white';
            case 'low': return 'bg-green-500 text-white';
            default: return 'bg-gray-500 text-white';
        }
    };

    const getStageColor = (stage: string) => {
        switch (stage.toLowerCase()) {
            case 'done': return 'bg-green-100 text-green-800';
            case 'in progress': return 'bg-blue-100 text-blue-800';
            case 'review': return 'bg-purple-100 text-purple-800';
            case 'todo': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const StatCard = ({ title, value, subtitle, color = "blue", icon: Icon }: any) => {
        const colorClasses = {
            blue: "bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200",
            green: "bg-gradient-to-r from-green-50 to-green-100 border-green-200",
            red: "bg-gradient-to-r from-red-50 to-red-100 border-red-200",
            purple: "bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200",
            orange: "bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200"
        };
        const textColors = {
            blue: "text-blue-700",
            green: "text-green-700",
            red: "text-red-700",
            purple: "text-purple-700",
            orange: "text-orange-700"
        };
        return (
            <Card className={`relative overflow-hidden ${colorClasses[color as keyof typeof colorClasses]}`}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className={`text-sm font-medium ${textColors[color as keyof typeof textColors]}`}>{title}</CardTitle>
                    {Icon && <Icon className={`h-8 w-8 ${textColors[color as keyof typeof textColors]} opacity-80`} />}
                </CardHeader>
                <CardContent>
                    <div className={`text-2xl font-bold ${textColors[color as keyof typeof textColors]}`}>{value}</div>
                    {subtitle && (
                        <p className={`text-xs ${textColors[color as keyof typeof textColors]} opacity-80 mt-1`}>{subtitle}</p>
                    )}
                </CardContent>
            </Card>
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Project Dashboard') }]}
            pageTitle={t('Project Dashboard')}
        >
            <Head title={t('Project Dashboard')} />

            <div className="space-y-6">
                {/* Company Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <div onClick={() => router.get(route('project.index'))} className="cursor-pointer">
                        <StatCard
                            title={t('Total Projects')}
                            value={stats.total_projects}
                            subtitle={stats.overdue_projects > 0 ? `${stats.overdue_projects} overdue` : 'All on track'}
                            color="blue"
                            icon={FolderKanban}
                        />
                    </div>
                    <div onClick={() => router.get(route('project.tasks.index'))} className="cursor-pointer">
                        <StatCard
                            title={t('Task Completion')}
                            value={`${stats.completion_rate}%`}
                            subtitle={`${stats.completed_tasks}/${stats.total_tasks} completed`}
                            color="green"
                            icon={CheckSquare}
                        />
                    </div>
                    <div onClick={() => router.get(route('project.bugs.index'))} className="cursor-pointer">
                        <StatCard
                            title={t('Active Bugs')}
                            value={bugStats.open}
                            subtitle={`${bugStats.resolved} resolved`}
                            color="red"
                            icon={Bug}
                        />
                    </div>
                    <div onClick={() => router.get(route('users.index'))} className="cursor-pointer">
                        <StatCard
                            title={t('Team Members')}
                            value={stats.total_users}
                            subtitle="Staff members"
                            color="purple"
                            icon={Users}
                        />
                    </div>
                    <div className="cursor-pointer">
                        <StatCard
                            title={t('Total Clients')}
                            value={stats.total_clients}
                            subtitle="Active clients"
                            color="orange"
                            icon={UserCheck}
                        />
                    </div>
                </div>

                {/* Company Progress Overview */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">{t('Company Monthly Progress')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            data={monthlyProgress}
                            height={300}
                            showTooltip={true}
                            showGrid={true}
                            lines={[
                                { dataKey: 'created', color: '#3b82f6', name: 'Tasks Created' },
                                { dataKey: 'completed', color: '#10b77f', name: 'Tasks Completed' }
                            ]}
                            xAxisKey="month"
                            showLegend={true}
                        />
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Project Status Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{t('Project Status')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <PieChart
                                    data={projectStatus.filter(item => item.value > 0)}
                                    dataKey="value"
                                    nameKey="name"
                                    height={200}
                                    donut={true}
                                    showTooltip={true}
                                />
                                <div className="space-y-2">
                                    {projectStatus.filter(item => item.value > 0).map((item, index) => (
                                        <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: item.color }}
                                                ></div>
                                                <span className="text-sm font-medium">{item.name}</span>
                                            </div>
                                            <span className="text-base font-bold">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Task Priority Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{t('Task Priority')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <PieChart
                                    data={taskPriority.filter(item => item.value > 0)}
                                    dataKey="value"
                                    nameKey="name"
                                    height={200}
                                    donut={true}
                                    showTooltip={true}
                                />
                                <div className="space-y-2">
                                    {taskPriority.filter(item => item.value > 0).map((item, index) => (
                                        <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: item.color }}
                                                ></div>
                                                <span className="text-sm font-medium">{item.name}</span>
                                            </div>
                                            <span className="text-base font-bold">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Team Performance */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{t('Team Performance')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {teamPerformance.slice(0, 5).map((member, index) => (
                                <div key={index} className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="font-medium">{member.name}</span>
                                        <span className="text-muted-foreground">
                                            {member.completed_tasks}/{member.total_tasks}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                        <div className="bg-blue-600 h-2 rounded-full" style={{width: `${member.completion_rate}%`}}></div>
                                    </div>
                                    <div className="text-xs text-muted-foreground text-right">
                                        {member.completion_rate}% {t('completed')}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Company Tasks */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">{t('Recent Company Tasks')}</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {stats.completed_tasks} {t('of')} {stats.total_tasks} {t('tasks completed across all projects')}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {recentTasks.map((task) => (
                                <div key={task.id} className="border rounded-lg p-4 space-y-3">
                                    <div className="flex items-start justify-between">
                                        <h4 className="font-medium text-sm truncate">{task.title}</h4>
                                        {task.is_completed && (
                                            <span className="text-green-500 text-xs">✓</span>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground">{t('Priority')}:</span>
                                            <Badge size="sm" className={`${getPriorityColor(task.priority)} hover:!bg-current hover:!text-current pointer-events-none`}>
                                                {task.priority}
                                            </Badge>
                                        </div>
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground">{t('Stage')}:</span>
                                            <Badge
                                                size="sm"
                                                variant="secondary"
                                                className={!task.stage_color ? getStageColor(task.stage) : ''}
                                                style={task.stage_color ? { backgroundColor: task.stage_color, color: '#fff' } : {}}
                                            >
                                                {task.stage}
                                            </Badge>
                                        </div>
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground">{t('Assignee')}:</span>
                                            <span className="font-medium">{task.assignee}</span>
                                        </div>
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground">{t('Project')}:</span>
                                            <span className="font-medium truncate">{task.project}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
