import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TicketIcon, CheckCircleIcon, LinkIcon, BookOpenIcon, HelpCircleIcon, Clock, TrendingUp, Users, Calendar, Eye } from 'lucide-react';
import { router } from '@inertiajs/react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { toast } from 'sonner';
import { formatDateTime } from '@/utils/helpers';

interface ClientDashboardProps {
  stats: {
    totalTickets: number;
    openTickets: number;
    closedTickets: number;
    todayTickets: number;
    resolutionRate: number;
  };
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

export default function ClientDashboard({ stats, monthlyData, recentTickets, statusData, slug }: ClientDashboardProps) {
  const { t } = useTranslation();

  const monthlyChartData = Object.entries(monthlyData).map(([month, value]) => ({
    month,
    tickets: value
  }));

  const handleCreateTicket = () => {
    router.visit(route('support-tickets.create'));
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Client Dashboard') }
      ]}
      pageTitle={t('Client Dashboard')}
    >
      <Head title={t('Client Dashboard')} />

      <div className="space-y-6">
        {/* Main Dashboard Card */}
        <Card className="relative overflow-hidden">
          <div className="absolute inset-0 bg-gradient-to-r from-blue-50 to-blue-100 opacity-50"></div>
          <CardContent className="relative p-8">
            <div className="flex items-center justify-between">
              <div className="space-y-4">
                <h2 className="text-2xl font-bold text-gray-900">
                  {t('My Support Tickets')}
                </h2>
                <p className="text-gray-600 max-w-md">
                  {t('Track your support requests and get help from our team.')}
                </p>
                <div className="flex gap-3">
                  <Button 
                    className="bg-blue-600 hover:bg-blue-700"
                    onClick={handleCreateTicket}
                  >
                    <TicketIcon className="h-4 w-4 mr-2" />
                    {t('Create New Ticket')}
                  </Button>
                </div>
              </div>
              <div className="hidden md:block">
                <div className="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
                  <TicketIcon className="h-8 w-8 text-white" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Client-Specific Stats with Rounded Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="bg-white shadow-lg rounded-2xl border-0 hover:shadow-xl transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{t('My Tickets')}</p>
                  <p className="text-3xl font-bold text-blue-600 mt-2">{stats.totalTickets}</p>
                  <p className="text-xs text-gray-500 mt-1">{t('Total submitted')}</p>
                </div>
                <div className="bg-blue-100 p-3 rounded-full">
                  <TicketIcon className="h-8 w-8 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-white shadow-lg rounded-2xl border-0 hover:shadow-xl transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{t('In Progress')}</p>
                  <p className="text-3xl font-bold text-amber-600 mt-2">{stats.openTickets}</p>
                  <p className="text-xs text-gray-500 mt-1">{t('Being resolved')}</p>
                </div>
                <div className="bg-amber-100 p-3 rounded-full">
                  <Clock className="h-8 w-8 text-amber-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-white shadow-lg rounded-2xl border-0 hover:shadow-xl transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{t('Resolved')}</p>
                  <p className="text-3xl font-bold text-emerald-600 mt-2">{stats.closedTickets}</p>
                  <p className="text-xs text-gray-500 mt-1">{stats.resolutionRate}% {t('success rate')}</p>
                </div>
                <div className="bg-emerald-100 p-3 rounded-full">
                  <CheckCircleIcon className="h-8 w-8 text-emerald-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-white shadow-lg rounded-2xl border-0 hover:shadow-xl transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{t('Today')}</p>
                  <p className="text-3xl font-bold text-violet-600 mt-2">{stats.todayTickets}</p>
                  <p className="text-xs text-gray-500 mt-1">{t('New requests')}</p>
                </div>
                <div className="bg-violet-100 p-3 rounded-full">
                  <Calendar className="h-8 w-8 text-violet-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Charts and Recent Activity */}
        <div className="grid grid-cols-1 xl:grid-cols-12 gap-6">
          {/* Monthly Tickets Chart */}
          <Card className="xl:col-span-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TrendingUp className="h-5 w-5" />
                {t('My Ticket Trends - This Year')}
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
                {t('My Ticket Status')}
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
                    <p>{t('No ticket data available')}</p>
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
                {t('My Recent Tickets')}
              </CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            {recentTickets && recentTickets.length > 0 ? (
              <div className="space-y-4">
                {recentTickets.map((ticket) => (
                  <div key={ticket.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
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
                        {ticket.category} • {formatDateTime(ticket.created_at)}
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