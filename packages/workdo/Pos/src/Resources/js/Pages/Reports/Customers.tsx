import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { Users, TrendingUp, DollarSign, ShoppingCart } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart as RechartsPieChart, Cell, Pie } from 'recharts';
import { DataTable } from "@/components/ui/data-table";
import NoRecordsFound from '@/components/no-records-found';
import { BarChart as BarChartComponent } from '@/components/charts/BarChart';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658', '#FF7C7C', '#8DD1E1', '#D084D0'];

interface CustomersReportProps {
    customerData: Array<{
        customer_id: number | null;
        total_orders: number;
        total_spent: number;
        avg_order_value: number;
        last_order_date: string;
        customer?: { name: string };
    }>;
}

export default function CustomersReport({ customerData }: CustomersReportProps) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
            { label: t('POS'), url: route('pos.index') },
            { label: t('Customer Report') }
            ]}
            pageTitle={t('Customer Report')}
        >
            <Head title={t('Customer Report')} />

            <div className="space-y-6">
                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="relative overflow-hidden bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                        <div className="absolute top-2 right-2">
                            <Users className="h-5 w-5 text-blue-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-blue-700">{customerData?.length || 0}</div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-blue-700">{t('Total Customers')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                        <div className="absolute top-2 right-2">
                            <DollarSign className="h-5 w-5 text-green-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-green-700">
                                {formatCurrency(customerData?.reduce((sum, c) => sum + (Number(c.total_spent) || 0), 0) || 0)}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-green-700">{t('Total Revenue')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                        <div className="absolute top-2 right-2">
                            <ShoppingCart className="h-5 w-5 text-purple-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-purple-700">
                                {customerData?.reduce((sum, c) => sum + (Number(c.total_orders) || 0), 0) || 0}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-purple-700">{t('Total Orders')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
                        <div className="absolute top-2 right-2">
                            <TrendingUp className="h-5 w-5 text-orange-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-orange-700">
                                {formatCurrency(
                                    customerData?.length > 0 
                                        ? customerData.reduce((sum, c) => sum + (Number(c.avg_order_value) || 0), 0) / customerData.length 
                                        : 0
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-orange-700">{t('Avg Order Value')}</CardTitle>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Customers Bar Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <DollarSign className="h-4 w-4" />
                                {t('Top 10 Customers by Spending')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-0 flex justify-center">
                            {customerData?.length > 0 ? (
                                <div className="w-full">
                                    <ResponsiveContainer width="100%" height={400}>
                                        <BarChart data={customerData?.slice(0, 10).map(customer => ({ 
                                            name: (customer.name || customer.customer?.name || 'Walk-in').substring(0, 10), 
                                            value: Number(customer.total_spent) || 0
                                        })) || []} margin={{ bottom: 60 }}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis 
                                                dataKey="name" 
                                                angle={-45} 
                                                textAnchor="end" 
                                                height={60}
                                                interval={0}
                                            />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#3b82f6" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-48 text-muted-foreground">
                                    <div className="text-center">
                                        <DollarSign className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No spending data available')}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Customer Order Distribution Pie Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Users className="h-4 w-4" />
                                {t('Order Distribution')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-3">
                            {customerData?.length > 0 ? (
                                <div className="w-full">
                                    <ResponsiveContainer width="100%" height={320}>
                                        <RechartsPieChart>
                                            <Tooltip 
                                                formatter={(value) => [value, t('Orders')]}
                                            />
                                            <Pie
                                                data={customerData?.slice(0, 10) || []}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ customer }) => customer?.name || 'Walk-in'}
                                                outerRadius={120}
                                                fill="#8884d8"
                                                dataKey="total_orders"
                                            >
                                                {(customerData?.slice(0, 10) || []).map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                        </RechartsPieChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-48 text-muted-foreground">
                                    <div className="text-center">
                                        <Users className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No order data available')}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Data Table */}
                <Card className="shadow-sm">
                    <CardHeader className="p-6 border-b bg-gray-50/50">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <Users className="h-5 w-5 text-blue-600" />
                            {t('Customer Performance Report')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 h-96 rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={customerData || []}
                                    columns={[
                                        {
                                            key: 'customer',
                                            header: t('Customer'),
                                            render: (value: any, customer: any) => (
                                                <Badge variant="secondary" className="text-xs">
                                                    {customer.customer?.name || t('Walk-in')}
                                                </Badge>
                                            )
                                        },
                                        {
                                            key: 'total_orders',
                                            header: t('Total Orders')
                                        },
                                        {
                                            key: 'total_spent',
                                            header: t('Total Spent'),
                                            render: (value: number) => formatCurrency(value)
                                        },
                                        {
                                            key: 'avg_order_value',
                                            header: t('Avg Order Value'),
                                            render: (value: number) => formatCurrency(value)
                                        },
                                        {
                                            key: 'last_order_date',
                                            header: t('Last Order'),
                                            render: (value: string) => formatDate(value)
                                        }
                                    ]}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Users}
                                            title={t('No customers found')}
                                            description={t('No customer data available for the selected period.')}
                                            hasFilters={false}
                                            onClearFilters={() => {}}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}