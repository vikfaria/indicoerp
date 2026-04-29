import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Users, TrendingUp, BarChart3, Rocket, Calendar, Clock, CalendarDays, Phone, Target, Award } from 'lucide-react';
import CalendarView from '@/components/calendar-view';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';


import { formatDate } from '@/utils/helpers';

interface LeadProps {
    message: string;
    stats?: {
        total_leads: number;
        total_deals: number;
        total_users: number;
        total_clients: number;
        converted_leads: number;
        won_deals: number;
    };
    recentDeals?: any[];
    recentLeads?: any[];
    calendarEvents?: any[];
    dealCallsChart?: any[];
    dealStageChart?: any[];
    pipelines?: any[];
}

export default function CompanyDashboard({ message, stats, recentDeals, recentLeads, calendarEvents, dealCallsChart, dealStageChart, pipelines }: LeadProps) {
    const { t } = useTranslation();
    const [selectedPipeline, setSelectedPipeline] = useState(pipelines?.[0]?.id?.toString() || '');
    
    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('CRM Dashboard')}]}
            pageTitle={t('CRM Dashboard')}
        >
            <Head title={t('CRM Dashboard')} />
            
            <div className="space-y-6">
                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-md transition-shadow cursor-pointer" onClick={() => router.visit(route('lead.deals.index'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-blue-700">{t('Total Deals')}</CardTitle>
                            <Rocket className="h-5 w-5 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-800">{stats?.total_deals || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200 hover:shadow-md transition-shadow cursor-pointer" onClick={() => router.visit(route('lead.leads.index'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-green-700">{t('Total Leads')}</CardTitle>
                            <TrendingUp className="h-5 w-5 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-800">{stats?.total_leads || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-md transition-shadow cursor-pointer" onClick={() => router.visit(route('users.index'))}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-purple-700">{t('Total Users')}</CardTitle>
                            <Users className="h-5 w-5 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-800">{stats?.total_users || 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-md transition-shadow cursor-pointer" onClick={() => router.get(route('users.index'), { role: 'client' })}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <CardTitle className="text-sm font-medium text-orange-700">{t('Total Clients')}</CardTitle>
                            <Users className="h-5 w-5 text-orange-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-800">{stats?.total_clients || 0}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    {/* Lead Calendar */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                {t('Lead Tasks Calendar')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CalendarView
                                events={calendarEvents.map(event => {
                                    let color = '#16a34a'; // Default green for completed
                                    
                                    // Set color based on individual event status
                                    color = 'hsl(var(--primary))'; // Primary color
                                    
                                    return {
                                        id: event.id,
                                        title: event.title,
                                        startDate: event.startDate,
                                        endDate: event.endDate,
                                        time: event.time || '00:00',
                                        color,
                                        description: `${t('Task')}: ${event.title} - ${t('Deal')}: ${event.name || ''} - ${t('Status')}: ${t(event.status?.charAt(0).toUpperCase() + event.status?.slice(1) || 'Unknown')}`,
                                        type: 'Deal Task',
                                    };
                                })}
                                onEventClick={(event) => { }}
                                onDateClick={(date) => { }}
                            />
                        </CardContent>
                    </Card>

                    {/* Charts Column */}
                    <div className="space-y-4">
                        {/* Deal Calls Chart */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2">
                                    <Phone className="h-5 w-5 text-primary" />
                                    {t('Deal & Lead Calls by Day')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {dealCallsChart && dealCallsChart.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={380}>
                                        <PieChart>
                                            <Pie
                                                data={dealCallsChart}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={60}
                                                outerRadius={120}
                                                dataKey="value"
                                                nameKey="name"
                                            >
                                                {dealCallsChart?.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={index === 0 ? '#3b82f6' : '#10b77f'} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-[380px] flex items-center justify-center text-gray-500">
                                        <p className="text-sm">{t('No call data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Deals by Stage Chart */}
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2">
                                        <BarChart3 className="h-5 w-5 text-green-600" />
                                        {t('Deals by Stage')}
                                    </CardTitle>
                                    <Select 
                                        value={selectedPipeline || pipelines?.[0]?.id?.toString() || ''} 
                                        onValueChange={(value) => {
                                            setSelectedPipeline(value);
                                            router.get(route('lead.index'), {
                                                pipeline_id: value
                                            }, {
                                                preserveState: true,
                                                preserveScroll: true,
                                                only: ['dealStageChart']
                                            });
                                        }}
                                    >
                                        <SelectTrigger className="w-40">
                                            <SelectValue placeholder={t('Select Pipeline')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {pipelines?.map((pipeline: any) => (
                                                <SelectItem key={pipeline.id} value={pipeline.id.toString()}>
                                                    {pipeline.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {dealStageChart && dealStageChart.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={380}>
                                        <BarChart data={dealStageChart}>
                                            <CartesianGrid strokeDasharray="3 3" className="opacity-30" />
                                            <XAxis dataKey="name" className="text-xs" type="category" />
                                            <YAxis className="text-xs" allowDecimals={false} />
                                            <Tooltip />
                                            <Bar 
                                                dataKey="deals" 
                                                fill="#3b82f6" 
                                                name="Deals"
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-[380px] flex items-center justify-center text-gray-500">
                                        <p className="text-sm">{t('No stage data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>                        
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Deals */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5 text-primary" />
                                {t('Recently Created Deals')}
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
                                    <p className="text-sm font-medium">{t('No recent deals')}</p>
                                    <p className="text-xs">{t('New deals will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Leads */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-primary" />
                                {t('Recently Created Leads')}
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
                                    <p className="text-sm font-medium">{t('No recent leads')}</p>
                                    <p className="text-xs">{t('New leads will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                
            </div>
        </AuthenticatedLayout>
    );
}