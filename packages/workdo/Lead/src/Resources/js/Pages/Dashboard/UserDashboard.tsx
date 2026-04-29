import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp, Rocket, Calendar, Clock, Target, CheckCircle } from 'lucide-react';
import CalendarView from '@/components/calendar-view';
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';

import { formatDate } from '@/utils/helpers';

interface UserDashboardProps {
    message: string;
    stats?: {
        assigned_leads: number;
        assigned_deals: number;
        completed_tasks: number;
        pending_tasks: number;
    };
    recentDeals?: any[];
    recentLeads?: any[];
    calendarEvents?: any[];
    taskStatusChart?: any[];
}

function UserDashboard({ message, stats, recentDeals, recentLeads, calendarEvents, taskStatusChart }: UserDashboardProps) {
    const { t } = useTranslation();
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];
    
    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Dashboard')}]}
            pageTitle={t('User Dashboard')}
        >
            <Head title={t('User Dashboard')} />
            
            <div className="space-y-6">
                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-md transition-shadow">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-blue-700">{t('Assigned Deals')}</CardTitle>
                            <Rocket className="h-5 w-5 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-800">{stats?.assigned_deals || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200 hover:shadow-md transition-shadow">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-green-700">{t('Assigned Leads')}</CardTitle>
                            <TrendingUp className="h-5 w-5 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-800">{stats?.assigned_leads || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-md transition-shadow">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-purple-700">{t('Completed Tasks')}</CardTitle>
                            <CheckCircle className="h-5 w-5 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-800">{stats?.completed_tasks || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-md transition-shadow">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-orange-700">{t('Pending Tasks')}</CardTitle>
                            <Target className="h-5 w-5 text-orange-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-800">{stats?.pending_tasks || 0}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    {/* Calendar */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                {t('Tasks Calendar')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CalendarView
                                events={calendarEvents?.map(event => ({
                                    id: event.id,
                                    title: event.title,
                                    startDate: event.startDate,
                                    endDate: event.endDate,
                                    time: event.time || '00:00',
                                    color: 'hsl(var(--primary))',
                                    description: `${t('Task')}: ${event.title} - ${t('Deal')}: ${event.name || ''} - ${t('Status')}: ${t(event.status?.charAt(0).toUpperCase() + event.status?.slice(1) || 'Unknown')}`,
                                    type: 'Task',
                                })) || []}
                                onEventClick={(event) => { }}
                                onDateClick={(date) => { }}
                            />
                        </CardContent>
                    </Card>

                    {/* Charts */}
                    <div className="space-y-4 h-full flex flex-col">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Target className="h-5 w-5 text-primary" />
                                    {t('Task Status')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {taskStatusChart && taskStatusChart.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={200}>
                                        <PieChart>
                                            <Pie
                                                data={taskStatusChart}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={40}
                                                outerRadius={80}
                                                dataKey="value"
                                                nameKey="name"
                                            >
                                                {taskStatusChart.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-[200px] flex items-center justify-center text-gray-500">
                                        <p className="text-sm">{t('No task data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                        <Card className="flex-1">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Rocket className="h-5 w-5 text-primary" />
                                    {t('Assignment Overview')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <span className="text-sm font-medium text-blue-700">{t('Deals')}</span>
                                        <span className="text-lg font-bold text-blue-800">{stats?.assigned_deals || 0}</span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                        <span className="text-sm font-medium text-green-700">{t('Leads')}</span>
                                        <span className="text-lg font-bold text-green-800">{stats?.assigned_leads || 0}</span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                        <span className="text-sm font-medium text-purple-700">{t('Total Tasks')}</span>
                                        <span className="text-lg font-bold text-purple-800">{(stats?.completed_tasks || 0) + (stats?.pending_tasks || 0)}</span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                        <span className="text-sm font-medium text-orange-700">{t('Completion Rate')}</span>
                                        <span className="text-lg font-bold text-orange-800">
                                            {((stats?.completed_tasks || 0) + (stats?.pending_tasks || 0)) > 0 
                                                ? Math.round(((stats?.completed_tasks || 0) / ((stats?.completed_tasks || 0) + (stats?.pending_tasks || 0))) * 100)
                                                : 0}%
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                        <span className="text-sm font-medium text-red-700">{t('Total Assigned')}</span>
                                        <span className="text-lg font-bold text-red-800">{(stats?.assigned_deals || 0) + (stats?.assigned_leads || 0)}</span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                                        <span className="text-sm font-medium text-indigo-700">{t('Total Amount')}</span>
                                        <span className="text-lg font-bold text-indigo-800">${stats?.total_amount || 0}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Assigned Deals */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5 text-primary" />
                                {t('Recent Assigned Deals')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentDeals && recentDeals.length > 0 ? (
                                <div className="space-y-3 max-h-80 overflow-y-auto">
                                    {recentDeals.map((deal) => (
                                        <div key={deal.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div className="flex-1">
                                                <h4 className="font-medium text-sm text-gray-900">{deal.name}</h4>
                                                <p className="text-xs text-gray-600 mt-1">{deal.stage?.name}</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-xs text-gray-500">{formatDate(deal.created_at)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500">
                                    <Clock className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No assigned deals')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Assigned Leads */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-primary" />
                                {t('Recent Assigned Leads')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentLeads && recentLeads.length > 0 ? (
                                <div className="space-y-3 max-h-80 overflow-y-auto">
                                    {recentLeads.map((lead) => (
                                        <div key={lead.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div className="flex-1">
                                                <h4 className="font-medium text-sm text-gray-900">{lead.name}</h4>
                                                <p className="text-xs text-gray-600 mt-1">{lead.subject}</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-xs text-gray-500">{formatDate(lead.created_at)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500">
                                    <TrendingUp className="h-12 w-12 mx-auto mb-3 opacity-30 text-primary" />
                                    <p className="text-sm font-medium">{t('No assigned leads')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

export default UserDashboard;