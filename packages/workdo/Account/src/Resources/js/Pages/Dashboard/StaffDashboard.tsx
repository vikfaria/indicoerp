import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Building, DollarSign, TrendingUp, TrendingDown, Receipt } from 'lucide-react';
import { formatCurrency } from '@/utils/helpers';

interface StaffProps {
    stats: {
        total_clients: number;
        total_vendors: number;
        monthly_revenue: number;
        monthly_expense: number;
    };
    recentActivities: Array<{
        type: string;
        title: string;
        amount: number;
        date: string;
    }>;
}

export default function StaffDashboard({ stats, recentActivities }: StaffProps) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Account')}, {label: t('Dashboard')}]}
            pageTitle={t('Dashboard')}
            pageTitleClass="text-lg"
        >
            <Head title={t('Dashboard')} />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium text-blue-700">{t('Total Clients')}</CardTitle>
                        <Users className="h-8 w-8 text-blue-700 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-blue-700">{stats.total_clients}</div>
                        <p className="text-xs text-blue-700 opacity-80 mt-1">{t('Active clients')}</p>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium text-purple-700">{t('Total Vendors')}</CardTitle>
                        <Building className="h-8 w-8 text-purple-700 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-purple-700">{stats.total_vendors}</div>
                        <p className="text-xs text-purple-700 opacity-80 mt-1">{t('Active vendors')}</p>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium text-green-700">{t('Monthly Revenue')}</CardTitle>
                        <TrendingUp className="h-8 w-8 text-green-700 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-green-700">{formatCurrency(stats.monthly_revenue)}</div>
                        <p className="text-xs text-green-700 opacity-80 mt-1">{t('Current month')}</p>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-r from-red-50 to-red-100 border-red-200">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium text-red-700">{t('Monthly Expense')}</CardTitle>
                        <TrendingDown className="h-8 w-8 text-red-700 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-red-700">{formatCurrency(stats.monthly_expense)}</div>
                        <p className="text-xs text-red-700 opacity-80 mt-1">{t('Current month')}</p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t('Monthly Summary')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                <div className="flex items-center space-x-3">
                                    <div className="p-2 bg-green-100 rounded-full">
                                        <TrendingUp className="h-4 w-4 text-green-600" />
                                    </div>
                                    <div>
                                        <p className="font-medium">{t('Revenue')}</p>
                                        <p className="text-sm text-muted-foreground">{t('Current month')}</p>
                                    </div>
                                </div>
                                <div className="text-lg font-bold text-green-600">
                                    {formatCurrency(stats.monthly_revenue)}
                                </div>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                                <div className="flex items-center space-x-3">
                                    <div className="p-2 bg-red-100 rounded-full">
                                        <TrendingDown className="h-4 w-4 text-red-600" />
                                    </div>
                                    <div>
                                        <p className="font-medium">{t('Expense')}</p>
                                        <p className="text-sm text-muted-foreground">{t('Current month')}</p>
                                    </div>
                                </div>
                                <div className="text-lg font-bold text-red-600">
                                    {formatCurrency(stats.monthly_expense)}
                                </div>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                <div className="flex items-center space-x-3">
                                    <div className="p-2 bg-blue-100 rounded-full">
                                        <DollarSign className="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p className="font-medium">{t('Net Profit')}</p>
                                        <p className="text-sm text-muted-foreground">{t('Current month')}</p>
                                    </div>
                                </div>
                                <div className={`text-lg font-bold ${(stats.monthly_revenue - stats.monthly_expense) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {formatCurrency(stats.monthly_revenue - stats.monthly_expense)}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-base">{t('Recent Activities')}</CardTitle>
                        <Receipt className="h-5 w-5 text-gray-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="max-h-96 overflow-y-auto space-y-3">
                            {recentActivities.length > 0 ? (
                                recentActivities.map((activity, index) => (
                                    <div key={index} className="flex justify-between items-center p-3 rounded-lg border">
                                        <div className="flex items-center space-x-3">
                                            <div className={`p-2 rounded-full ${activity.type === 'Revenue' ? 'bg-green-100' : 'bg-red-100'}`}>
                                                {activity.type === 'Revenue' ?
                                                    <TrendingUp className="h-4 w-4 text-green-600" /> :
                                                    <TrendingDown className="h-4 w-4 text-red-600" />
                                                }
                                            </div>
                                            <div>
                                                <p className="font-medium text-sm">{activity.title}</p>
                                                <p className="text-xs text-gray-600">{activity.type}</p>
                                                <p className="text-xs text-gray-500">{new Date(activity.date).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                        <div className={`font-bold ${activity.type === 'Revenue' ? 'text-green-600' : 'text-red-600'}`}>
                                            {activity.type === 'Revenue' ? '+' : '-'}{formatCurrency(activity.amount)}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <DollarSign className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                    <p>{t('No recent activities')}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
