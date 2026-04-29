import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DollarSign, ShoppingCart, Package, Users, TrendingUp, AlertTriangle, Plus, Eye } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { LineChart, PieChart } from '@/components/charts';

interface PosProps {
    stats: {
        today_sales: number;
        week_sales: number;
        month_sales: number;
        total_sales: number;
        total_revenue: number;
        avg_transaction: number;
        total_products: number;
        low_stock_products: number;
        total_customers: number;
        walk_in_sales: number;
    };
    topProducts: Array<{
        name: string;
        total_quantity: number;
        total_revenue: number;
    }>;
    recentSales: Array<{
        id: number;
        sale_number: string;
        total: number;
        created_at: string;
        customer?: { name: string };
        warehouse?: { name: string };
    }>;
    salesByStatus: Record<string, number>;
    last10DaysSales: Array<{
        date: string;
        sales: number;
    }>;
    outOfStockProductsList: Array<{
        product_name: string;
        sku: string;
        warehouse_name: string;
        stock: number;
    }>;

}

export default function PosIndex({ stats, topProducts, recentSales, salesByStatus, last10DaysSales, outOfStockProductsList }: PosProps) {
    const { t } = useTranslation();
    
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
        const iconColors = {
            blue: "text-blue-600",
            green: "text-green-600",
            red: "text-red-600",
            purple: "text-purple-600",
            orange: "text-orange-600"
        };
        return (
            <Card className={`relative overflow-hidden ${colorClasses[color as keyof typeof colorClasses]}`}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className={`text-sm font-medium ${textColors[color as keyof typeof textColors]}`}>{title}</CardTitle>
                    <Icon className={`h-5 w-5 ${iconColors[color as keyof typeof iconColors]}`} />
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

    // Prepare chart data
    const salesTrendData = [
        { period: t('Today'), sales: stats.today_sales },
        { period: t('Week'), sales: stats.week_sales },
        { period: t('Month'), sales: stats.month_sales }
    ];

    const statusChartData = Object.entries(salesByStatus || {}).map(([status, count]) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1),
        value: count as number
    }));
    
    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('POS Dashboard')}]}
            pageTitle={t('POS Dashboard')}
        >
            <Head title={t('POS Dashboard')} />
            
            <div className="space-y-6">


                {/* Enhanced Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title={t('Today Revenue')}
                        value={formatCurrency(stats.today_sales)}
                        subtitle={t('Current day revenue')}
                        color="green"
                        icon={DollarSign}
                    />
                    <StatCard
                        title={t('Total Sales')}
                        value={stats.total_sales}
                        subtitle={`${formatCurrency(stats.total_revenue)} ${t('revenue')}`}
                        color="blue"
                        icon={ShoppingCart}
                    />
                    <StatCard
                        title={t('Avg Transaction')}
                        value={formatCurrency(stats.avg_transaction)}
                        subtitle={`${stats.total_customers} ${t('customers')}`}
                        color="purple"
                        icon={Users}
                    />
                    <StatCard
                        title={t('Total Products')}
                        value={stats.total_products}
                        color="orange"
                        icon={Package}
                    />
                </div>

                {/* Last 10 Days Sales Report */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Last 10 Days Sales Report')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            data={last10DaysSales}
                            height={300}
                            showTooltip={true}
                            showGrid={true}
                            lines={[
                                { dataKey: 'sales', color: '#3b82f6', name: t('Daily Sales') }
                            ]}
                            xAxisKey="date"
                            showLegend={true}
                        />
                    </CardContent>
                </Card>

                {/* Out of Stock Products Warehouse Wise */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <AlertTriangle className="h-5 w-5 mr-2 text-red-600" />
                            {t('Out of Stock Products (Warehouse Wise)')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {outOfStockProductsList?.length > 0 ? (
                                outOfStockProductsList.map((item, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                        <div className="flex items-center space-x-3">
                                            <div className="h-3 w-3 rounded-full bg-red-500"></div>
                                            <div>
                                                <h4 className="font-medium text-gray-900">{item.product_name} ({item.sku})</h4>
                                                <p className="text-xs text-blue-600">{t('Warehouse')}: {item.warehouse_name}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <Badge variant="destructive">
                                                {item.stock} {t('units')}
                                            </Badge>
                                            <p className="text-xs text-gray-500 mt-1">{t('Out of Stock')}</p>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <Package className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                    <p>{t('No out of stock products')}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Selling Products */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Package className="h-5 w-5 text-primary" />
                                {t('Top Selling Products')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topProducts && topProducts.length > 0 ? (
                                <div className="space-y-3 max-h-80 overflow-y-auto">
                                    {topProducts.slice(0, 5).map((product, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div className="flex-1">
                                                <h4 className="font-medium text-sm text-gray-900">{product.name}</h4>
                                                <p className="text-xs text-gray-600 mt-1">{product.total_quantity} {t('units sold')}</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-bold text-green-600">{formatCurrency(product.total_revenue)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500">
                                    <Package className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No product data')}</p>
                                    <p className="text-xs">{t('Top products will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Transactions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ShoppingCart className="h-5 w-5 text-primary" />
                                {t('Recent Transactions')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentSales && recentSales.length > 0 ? (
                                <div className="space-y-3 max-h-80 overflow-y-auto">
                                    {recentSales.slice(0, 5).map((sale) => (
                                        <div key={sale.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div className="flex-1">
                                                <h4 className="font-medium text-sm text-gray-900">{sale.sale_number}</h4>
                                                <p className="text-xs text-gray-600 mt-1">{sale.customer?.name || t('Walk-in')}</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-bold">{formatCurrency(sale.total)}</p>
                                                <p className="text-xs text-gray-500">{formatDate(sale.created_at)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500">
                                    <ShoppingCart className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm font-medium">{t('No recent sales')}</p>
                                    <p className="text-xs">{t('New transactions will appear here')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}