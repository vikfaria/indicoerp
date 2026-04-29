import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/utils/helpers';
import { Package, BarChart3, PieChart, TrendingUp } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart as RechartsPieChart, Cell, Pie } from 'recharts';
import { DataTable } from "@/components/ui/data-table";
import NoRecordsFound from '@/components/no-records-found';
import { PieChart as PieChartComponent } from '@/components/charts/PieChart';
import { BarChart as BarChartComponent } from '@/components/charts/BarChart';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658', '#FF7C7C', '#8DD1E1', '#D084D0'];

interface ProductsReportProps {
    productData: Array<{
        name: string;
        sku: string;
        total_quantity: number;
        total_revenue: number;
        total_orders: number;
    }>;
}

export default function ProductsReport({ productData }: ProductsReportProps) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('POS'), url: route('pos.index') },
                { label: t('Product Report') }
            ]}
            pageTitle={t('Product Report')}
        >
            <Head title={t('Product Report')} />

            <div className="space-y-6">
                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="relative overflow-hidden bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                        <div className="absolute top-2 right-2">
                            <Package className="h-5 w-5 text-blue-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-blue-700">{productData?.length || 0}</div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-blue-700">{t('Total Products')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                        <div className="absolute top-2 right-2">
                            <TrendingUp className="h-5 w-5 text-green-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-green-700">
                                {formatCurrency(productData?.reduce((sum, p) => sum + p.total_revenue, 0) || 0)}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-green-700">{t('Total Revenue')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                        <div className="absolute top-2 right-2">
                            <PieChart className="h-5 w-5 text-purple-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-purple-700">
                                {productData?.reduce((sum, p) => sum + p.total_quantity, 0) || 0}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-purple-700">{t('Total Quantity')}</CardTitle>
                        </CardContent>
                    </Card>
                    <Card className="relative overflow-hidden bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
                        <div className="absolute top-2 right-2">
                            <BarChart3 className="h-5 w-5 text-orange-700 opacity-80" />
                        </div>
                        <CardHeader className="text-center space-y-0 pb-1 pt-3">
                            <div className="text-2xl font-bold text-orange-700">
                                {productData?.reduce((sum, p) => sum + p.total_orders, 0) || 0}
                            </div>
                        </CardHeader>
                        <CardContent className="text-center pt-1 pb-3">
                            <CardTitle className="text-sm font-medium text-orange-700">{t('Total Orders')}</CardTitle>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Revenue Bar Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <BarChart3 className="h-4 w-4" />
                                {t('Top 10 Products by Revenue')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-0 flex justify-center">
                            {productData?.length > 0 ? (
                                <div className="w-full">
                                    <ResponsiveContainer width="100%" height={400}>
                                        <BarChart data={productData?.slice(0, 10).map(product => ({ 
                                            name: product.name.substring(0, 15), 
                                            value: Number(product.total_revenue) || 0
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
                                        <BarChart3 className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No revenue data available')}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Quantity Pie Chart */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <PieChart className="h-4 w-4" />
                                {t('Quantity Distribution')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 pb-3">
                            {productData?.length > 0 ? (
                                <div className="w-full">
                                    <ResponsiveContainer width="100%" height={320}>
                                        <RechartsPieChart>
                                            <Tooltip 
                                                formatter={(value) => [value, t('Quantity')]}
                                            />
                                            <Pie
                                                data={productData?.slice(0, 10) || []}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ name }) => name}
                                                outerRadius={120}
                                                fill="#8884d8"
                                                dataKey="total_quantity"
                                            >
                                                {(productData?.slice(0, 10) || []).map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                        </RechartsPieChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-48 text-muted-foreground">
                                    <div className="text-center">
                                        <PieChart className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{t('No quantity data available')}</p>
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
                            <Package className="h-5 w-5 text-blue-600" />
                            {t('Product Performance Report')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 h-96 rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={productData || []}
                                    columns={[
                                        {
                                            key: 'name',
                                            header: t('Product Name')
                                        },
                                        {
                                            key: 'sku',
                                            header: t('SKU')
                                        },
                                        {
                                            key: 'total_quantity',
                                            header: t('Quantity Sold')
                                        },
                                        {
                                            key: 'total_revenue',
                                            header: t('Total Revenue'),
                                            render: (value: number) => formatCurrency(value)
                                        },
                                        {
                                            key: 'total_orders',
                                            header: t('Orders')
                                        }
                                    ]}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Package}
                                            title={t('No products found')}
                                            description={t('No product data available for the selected period.')}
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