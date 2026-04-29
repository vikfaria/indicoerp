import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TicketIcon, CheckCircleIcon, LinkIcon, BookOpenIcon, HelpCircleIcon, TruckIcon, Clock, TrendingUp, Users, Calendar, Eye } from 'lucide-react';
import { router } from '@inertiajs/react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { toast } from 'sonner';
import { formatDateTime } from '@/utils/helpers';

interface VendorDashboardProps {
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

export default function VendorDashboard({ stats, monthlyData, recentTickets, statusData, slug }: VendorDashboardProps) {
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
        { label: t('Vendor Dashboard') }
      ]}
      pageTitle={t('Vendor Dashboard')}
    >
      <Head title={t('Vendor Dashboard')} />

      <div className="space-y-6">
        {/* Main Dashboard Card */}
        <Card className="relative overflow-hidden">
          <div className="absolute inset-0 bg-gradient-to-r from-orange-50 to-orange-100 opacity-50"></div>
          <CardContent className="relative p-8">
            <div className="flex items-center justify-between">
              <div className="space-y-4">
                <h2 className="text-2xl font-bold text-gray-900">
                  {t('Vendor Support Portal')}
                </h2>
                <p className="text-gray-600 max-w-md">
                  {t('Manage your vendor-related support requests and track service issues.')}
                </p>
                <div className="flex gap-3">
                  <Button 
                    className="bg-orange-600 hover:bg-orange-700"
                    onClick={handleCreateTicket}
                  >
                    <TicketIcon className="h-4 w-4 mr-2" />
                    {t('Create Support Ticket')}
                  </Button>
                </div>
              </div>
              <div className="hidden md:block">
                <div className="w-16 h-16 bg-orange-600 rounded-full flex items-center justify-center">
                  <TruckIcon className="h-8 w-8 text-white" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Enhanced Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-blue-700">{t('Total Requests')}</CardTitle>
              <TicketIcon className="h-6 w-6 text-blue-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-700">{stats.totalTickets}</div>
              <p className="text-xs text-blue-600 mt-1">{t('All time')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-orange-700">{t('In Progress')}</CardTitle>
              <Clock className="h-6 w-6 text-orange-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-orange-700">{stats.openTickets}</div>
              <p className="text-xs text-orange-600 mt-1">{t('Active requests')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-green-50 to-green-100 border-green-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-green-700">{t('Resolved')}</CardTitle>
              <CheckCircleIcon className="h-6 w-6 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-700">{stats.closedTickets}</div>
              <p className="text-xs text-green-600 mt-1">{stats.resolutionRate}% {t('resolution rate')}</p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-purple-700">{t('Today\'s Requests')}</CardTitle>
              <Calendar className="h-6 w-6 text-purple-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-purple-700">{stats.todayTickets}</div>
              <p className="text-xs text-purple-600 mt-1">{t('Created today')}</p>
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
                {t('My Request Trends - This Year')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={monthlyChartData}>
                    <defs>
                      <linearGradient id="colorTickets" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#ea580c" stopOpacity={0.8}/>
                        <stop offset="95%" stopColor="#ea580c" stopOpacity={0.1}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                    <XAxis dataKey="month" axisLine={false} tickLine={false} />
                    <YAxis axisLine={false} tickLine={false} />
                    <Tooltip contentStyle={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px' }} />
                    <Area type="monotone" dataKey="tickets" stroke="#ea580c" fillOpacity={1} fill="url(#colorTickets)" strokeWidth={3} />
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
                {t('My Request Status')}
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
                    <p>{t('No request data available')}</p>
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
                {t('My Recent Requests')}
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
                <p>{t('No recent requests found')}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}