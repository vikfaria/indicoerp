import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, usePage, router } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatDate } from '@/utils/helpers';
import { FolderKanban, ListTodo, CheckSquare, Clock } from 'lucide-react';


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

interface Project {
    id: number;
    name: string;
    status: string;
    start_date: string;
    end_date: string;
}

interface ProjectProgress {
    name: string;
    progress: number;
    total_tasks: number;
    completed_tasks: number;
    status: string;
}

interface ClientDashboardProps {
    stats: {
        total_projects: number;
        total_tasks: number;
        completed_tasks: number;
        completion_rate: number;
        pending_tasks: number;
    };
    recentTasks: Task[];
    projectProgress: ProjectProgress[];
    clientProjects: Project[];
}

export default function ClientDashboard() {
    const { t } = useTranslation();
    const { stats, recentTasks, projectProgress, clientProjects } = usePage<ClientDashboardProps>().props;

    const getPriorityColor = (priority: string) => {
        switch (priority.toLowerCase()) {
            case 'high': return 'bg-red-500 text-white';
            case 'medium': return 'bg-yellow-500 text-white';
            case 'low': return 'bg-green-500 text-white';
            default: return 'bg-gray-500 text-white';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'finished': return 'bg-green-100 text-green-800';
            case 'ongoing': return 'bg-blue-100 text-blue-800';
            case 'onhold': return 'bg-yellow-100 text-yellow-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const StatCard = ({ title, value, subtitle, color = "blue", icon: Icon }: any) => {
        const colorClasses = {
            blue: "bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200",
            green: "bg-gradient-to-r from-green-50 to-green-100 border-green-200",
            purple: "bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200",
            orange: "bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200"
        };
        const textColors = {
            blue: "text-blue-700",
            green: "text-green-700",
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
            breadcrumbs={[{ label: t('Client Dashboard') }]}
            pageTitle={t('Client Dashboard')}
        >
            <Head title={t('Client Dashboard')} />

            <div className="space-y-6">
                {/* Client Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title={t('Projects')}
                        value={stats.total_projects}
                        subtitle="Active projects"
                        color="blue"
                        icon={FolderKanban}
                    />
                    <StatCard
                        title={t('Total Tasks')}
                        value={stats.total_tasks}
                        subtitle="All project tasks"
                        color="purple"
                        icon={ListTodo}
                    />
                    <StatCard
                        title={t('Completed')}
                        value={stats.completed_tasks}
                        subtitle="Tasks finished"
                        color="green"
                        icon={CheckSquare}
                    />
                    <StatCard
                        title={t('Pending')}
                        value={stats.pending_tasks}
                        subtitle="Tasks remaining"
                        color="orange"
                        icon={Clock}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Project Progress */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{t('Project Progress')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {projectProgress.length > 0 ? (
                                projectProgress.map((project, index) => (
                                    <div key={index} className="space-y-2">
                                        <div className="flex justify-between items-center">
                                            <div>
                                                <span className="font-medium text-sm">{project.name}</span>
                                                <Badge
                                                    size="sm"
                                                    className={`ml-2 ${getStatusColor(project.status)}`}
                                                >
                                                    {project.status}
                                                </Badge>
                                            </div>
                                            <span className="text-sm text-muted-foreground">
                                                {project.completed_tasks}/{project.total_tasks}
                                            </span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-blue-600 h-2 rounded-full"
                                                style={{width: `${project.progress}%`}}
                                            ></div>
                                        </div>
                                        <div className="text-xs text-muted-foreground text-right">
                                            {project.progress}% {t('completed')}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-gray-500 text-center py-4">{t('No projects assigned')}</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Projects List */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{t('Projects')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {clientProjects.length > 0 ? (
                                    clientProjects.map((project) => (
                                        <div key={project.id} className="border rounded-lg p-3 space-y-2">
                                            <div className="flex justify-between items-start">
                                                <h4 className="font-medium text-sm">{project.name}</h4>
                                                <Badge
                                                    size="sm"
                                                    className={getStatusColor(project.status)}
                                                >
                                                    {project.status}
                                                </Badge>
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                <div>{t('Start')}: {formatDate(project.start_date)}</div>
                                                <div>{t('End')}: {formatDate(project.end_date)}</div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-gray-500 text-center py-4">{t('No projects found')}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Project Updates */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">{t('Recent Project Updates')}</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {t('Latest activities and progress in your projects')}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {recentTasks.length > 0 ? (
                                recentTasks.map((task) => (
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
                                                <Badge size="sm" className={getPriorityColor(task.priority)}>
                                                    {task.priority}
                                                </Badge>
                                            </div>
                                            <div className="flex justify-between text-xs">
                                                <span className="text-muted-foreground">{t('Stage')}:</span>
                                                <Badge
                                                    size="sm"
                                                    variant="secondary"
                                                    style={task.stage_color ? { backgroundColor: task.stage_color, color: '#fff' } : {}}
                                                >
                                                    {task.stage}
                                                </Badge>
                                            </div>
                                            <div className="flex justify-between text-xs">
                                                <span className="text-muted-foreground">{t('Assignee')}:</span>
                                                <span className="font-medium truncate">{task.assignee}</span>
                                            </div>
                                            <div className="flex justify-between text-xs">
                                                <span className="text-muted-foreground">{t('Project')}:</span>
                                                <span className="font-medium truncate">{task.project}</span>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="col-span-full text-center py-8">
                                    <p className="text-gray-500">{t('No tasks found in your projects')}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
