import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TicketIcon, FolderIcon, CheckCircleIcon, LinkIcon, Copy, Clock, TrendingUp, Users, Calendar, Eye } from 'lucide-react';
import { router } from '@inertiajs/react';
import { PieChart, Pie, Cell, ResponsiveContainer, AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, Legend } from 'recharts';
import { toast } from 'sonner';
import { formatDateTime } from '@/utils/helpers';

interface DashboardProps {
  stats: {
    totalTickets: number;
    categories: number;
    openTickets: number;
    closedTickets: number;
    todayTickets: number;
    avgResponseTime: number;
    resolutionRate: number;
    knowledgeBase: number;
    faqs: number;
  };
  chartData: Array<{
    name: string;
    value: number;
    color: string;
  }>;
  monthlyData: Record<string, number>;
  recentTickets: Array<{
    id: number;
    ticket_id: string;
    name: string;
    email: string;
    subject: string;
    status: string;
    category: string;
    created_at: string;
  }>;
  statusData: Array<{
    name: string;
    value: number;
    color: string;
  }>;
  slug: string;
}

export default function Index({ stats, chartData, monthlyData, recentTickets, statusData, slug }: DashboardProps) {
  const { t } = useTranslation();

  const monthlyChartData = Object.entries(monthlyData).map(([month, value]) => ({
    month,
    tickets: value
  }));

  const copyToClipboard = async () => {    
    const ticketUrl = route('support-ticket.index',[slug]);
    await navigator.clipboard.writeText(ticketUrl);
    toast.success(t('Link copied to clipboard!'));
  };



  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Support Tickets Dashboard') }
      ]}
      pageTitle={t('Support Tickets Dashboard')}
    >
      <Head title={t('Support Tickets Dashboard')} />

      <div className="space-y-6">
        {/* Enhanced Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
          <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200 cursor-pointer hover:shadow-lg transition-all duration-200" onClick={() => router.get(route('support-tickets.index'))}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-blue-700">{t('Total Tickets')}</CardTitle>
              <TicketIcon className="h-6 w-6 text-blue-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-700">{stats.totalTickets}</div>
              <p className="text-xs text-blue-600 mt-1">{t('All time')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200 cursor-pointer hover:shadow-lg transition-all duration-200" onClick={() => router.get(route('support-tickets.index'))}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-orange-700">{t('Open Tickets')}</CardTitle>
              <Clock className="h-6 w-6 text-orange-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-orange-700">{stats.openTickets}</div>
              <p className="text-xs text-orange-600 mt-1">{t('Pending resolution')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-green-50 to-green-100 border-green-200 cursor-pointer hover:shadow-lg transition-all duration-200" onClick={() => router.get(route('support-tickets.index'))}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-green-700">{t('Closed Tickets')}</CardTitle>
              <CheckCircleIcon className="h-6 w-6 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-700">{stats.closedTickets}</div>
              <p className="text-xs text-green-600 mt-1">{stats.resolutionRate}% {t('resolution rate')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-purple-700">{t('Today\'s Tickets')}</CardTitle>
              <Calendar className="h-6 w-6 text-purple-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-purple-700">{stats.todayTickets}</div>
              <p className="text-xs text-purple-600 mt-1">{t('Created today')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-teal-50 to-teal-100 border-teal-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-teal-700">{t('Avg Response')}</CardTitle>
              <TrendingUp className="h-6 w-6 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-teal-700">{stats.avgResponseTime}h</div>
              <p className="text-xs text-teal-600 mt-1">{t('Response time')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-red-50 to-red-100 border-red-200 cursor-pointer hover:shadow-lg transition-all duration-200" onClick={() => router.get(route('ticket-category.index'))}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-red-700">{t('Categories')}</CardTitle>
              <FolderIcon className="h-6 w-6 text-red-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-red-700">{stats.categories}</div>
              <p className="text-xs text-red-600 mt-1">{t('Active categories')}</p>
            </CardContent>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="bg-gradient-to-r from-indigo-50 to-indigo-100 border-indigo-200">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-indigo-900">{t('Support Ticket System')}</h3>
                <p className="text-indigo-700 mt-1">{t('Manage customer inquiries efficiently with our comprehensive ticket system')}</p>
              </div>
              <div className="flex gap-3">
                <Button className="bg-indigo-600 hover:bg-indigo-700" onClick={copyToClipboard}>
                  <LinkIcon className="h-4 w-4 mr-2" />
                  {t('Ticket Link')}
                </Button>
                <Button variant="outline" className="border-indigo-300 text-indigo-700 hover:bg-indigo-50" onClick={() => router.get(route('support-tickets.create'))}>
                  <TicketIcon className="h-4 w-4 mr-2" />
                  {t('Create Ticket')}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Charts and Recent Activity */}
        <div className="grid grid-cols-1 xl:grid-cols-12 gap-6">
          {/* Monthly Tickets Chart */}
          <Card className="xl:col-span-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TrendingUp className="h-5 w-5" />
                {t('Ticket Trends - This Year')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={monthlyChartData}>
                    <defs>
                      <linearGradient id="colorTickets" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#3B82F6" stopOpacity={0.8}/>
                        <stop offset="95%" stopColor="#3B82F6" stopOpacity={0.1}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                    <XAxis dataKey="month" axisLine={false} tickLine={false} />
                    <YAxis axisLine={false} tickLine={false} />
                    <Tooltip contentStyle={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px' }} />
                    <Area type="monotone" dataKey="tickets" stroke="#3B82F6" fillOpacity={1} fill="url(#colorTickets)" strokeWidth={3} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>

          {/* Status Distribution */}
          <Card className="xl:col-span-4">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <CheckCircleIcon className="h-5 w-5" />
                {t('Status Distribution')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-80">
                {statusData && statusData.length > 0 ? (
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie data={statusData} cx="50%" cy="50%" innerRadius={60} outerRadius={120} paddingAngle={5} dataKey="value">
                        {statusData.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.color} />
                        ))}
                      </Pie>
                      <Tooltip />
                      <Legend />
                    </PieChart>
                  </ResponsiveContainer>
                ) : (
                  <div className="flex items-center justify-center h-full text-muted-foreground">
                    <p>{t('No status data available')}</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Recent Tickets */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                {t('Recent Tickets')}
              </CardTitle>
              <Button variant="outline" size="sm" onClick={() => router.get(route('support-tickets.index'))}>
                <Eye className="h-4 w-4 mr-2" />
                {t('View All')}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {recentTickets && recentTickets.length > 0 ? (
              <div className="space-y-4">
                {recentTickets.map((ticket) => (
                  <div key={ticket.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer" onClick={() => router.get(route('support-tickets.edit', ticket.id))}>
                    <div className="flex-1">
                      <div className="flex items-center gap-3">
                        <div className="font-medium text-gray-900">#{ticket.ticket_id}</div>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                          ticket.status === 'In Progress' ? 'bg-blue-100 text-blue-800' :
                          ticket.status === 'closed' || ticket.status === 'Closed' ? 'bg-red-100 text-red-800' :
                          ticket.status === 'On Hold' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-gray-100 text-gray-800'
                        }`}>
                          {ticket.status}
                        </span>
                      </div>
                      <div className="text-sm text-gray-600 mt-1">{ticket.subject}</div>
                      <div className="text-xs text-gray-500 mt-1">
                        {ticket.name} • {ticket.category} • {formatDateTime(ticket.created_at)}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <TicketIcon className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                <p>{t('No recent tickets found')}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}