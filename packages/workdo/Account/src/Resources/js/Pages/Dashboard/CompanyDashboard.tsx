import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LineChart } from '@/components/charts';
import { Package, Users, CheckCircle, XCircle, UserCheck, Building2, CreditCard, ArrowUpCircle, ArrowDownCircle } from 'lucide-react';
import { formatDate,formatCurrency} from '@/utils/helpers';

interface AccountProps {
    message: string;
    stats?: {
        total_items: number;
        active_items: number;
        inactive_items: number;
        total_clients: number;
        total_vendors: number;
        total_customer_payment: number;
        total_vendor_payment: number;
    };
    monthlyVendorPayments?: Array<{ month: string; vendor_payments: number }>;
    monthlyCustomerPayments?: Array<{ month: string; customer_payments: number }>;
    recentRevenues?: Array<{ id: number; title: string; description: string; amount: number; date: string }>;
    recentExpenses?: Array<{ id: number; title: string; description: string; amount: number; date: string }>;
    recent_items?: Array<{
        id: number;
        name: string;
        created_at: string;
    }>;
}

export default function AccountIndex({ message, stats, monthlyVendorPayments, monthlyCustomerPayments, recentRevenues, recentExpenses, recent_items }: AccountProps) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Account Dashboard')}]}
            pageTitle={t('Account Dashboard')}
            pageTitleClass="text-lg"
        >
            <Head title={t('Account Dashboard')} />

            {stats && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <Card className="bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-orange-700">{t('Total Clients')}</CardTitle>
                                <UserCheck className="h-8 w-8 text-orange-700 opacity-80" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-700">{stats.total_clients || 0}</div>
                                <p className="text-xs text-orange-700 opacity-80 mt-1">{t('Active clients')}</p>
                            </CardContent>
                        </Card>
                        <Card className="bg-gradient-to-r from-teal-50 to-teal-100 border-teal-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-teal-700">{t('Total Vendors')}</CardTitle>
                                <Building2 className="h-8 w-8 text-teal-700 opacity-80" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-teal-700">{stats.total_vendors || 0}</div>
                                <p className="text-xs text-teal-700 opacity-80 mt-1">{t('Active vendors')}</p>
                            </CardContent>
                        </Card>
                        <Card className="bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-emerald-700">{t('Total Customer Payment')}</CardTitle>
                                <ArrowDownCircle className="h-8 w-8 text-emerald-700 opacity-80" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-emerald-700">{formatCurrency(stats.total_customer_payment || 0)}</div>
                                <p className="text-xs text-emerald-700 opacity-80 mt-1">{t('Received payments')}</p>
                            </CardContent>
                        </Card>
                        <Card className="bg-gradient-to-r from-rose-50 to-rose-100 border-rose-200">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-rose-700">{t('Total Vendor Payment')}</CardTitle>
                                <ArrowUpCircle className="h-8 w-8 text-rose-700 opacity-80" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-rose-700">{formatCurrency(stats.total_vendor_payment || 0)}</div>
                                <p className="text-xs text-rose-700 opacity-80 mt-1">{t('Paid to vendors')}</p>
                            </CardContent>
                        </Card>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div className="space-y-6">
                    <Card className="h-96">
                        <CardHeader>
                            <CardTitle className="text-base">{t('Monthly Customer Payments')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <LineChart
                                data={monthlyCustomerPayments}
                                height={300}
                                showTooltip={true}
                                showGrid={true}
                                lines={[
                                    { dataKey: 'customer_payments', color: '#10b77f', name: 'Customer Payments' }
                                ]}
                                xAxisKey="month"
                                showLegend={true}
                            />
                        </CardContent>
                    </Card>

                    {recentRevenues && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-base">{t('Recent Revenue')}</CardTitle>
                                <span className="text-xs text-gray-500">{t('Last 5 days')}</span>
                            </CardHeader>
                            <CardContent>
                                <div className="max-h-80 overflow-y-auto space-y-3">
                                    {recentRevenues.slice(0, 5).map((revenue) => (
                                        <div key={revenue.id} className="flex justify-between items-center p-3 rounded-lg border">
                                            <div>
                                                <p className="font-medium text-sm">{revenue.title}</p>
                                                <p className="text-xs text-gray-600">{revenue.description}</p>
                                                <p className="text-xs text-gray-500">{formatDate(revenue.date)}</p>
                                            </div>
                                            <div className="text-green-600 font-bold">{formatCurrency(revenue.amount)}</div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card className="h-96">
                        <CardHeader>
                            <CardTitle className="text-base">{t('Monthly Vendor Payments')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <LineChart
                                data={monthlyVendorPayments}
                                height={300}
                                showTooltip={true}
                                showGrid={true}
                                lines={[
                                    { dataKey: 'vendor_payments', color: '#ef4444', name: 'Vendor Payments' }
                                ]}
                                xAxisKey="month"
                                showLegend={true}
                            />
                        </CardContent>
                    </Card>

                    {recentExpenses && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-base">{t('Recent Expenses')}</CardTitle>
                                <span className="text-xs text-gray-500">{t('Last 5 days')}</span>
                            </CardHeader>
                            <CardContent>
                                <div className="max-h-80 overflow-y-auto space-y-3">
                                    {recentExpenses.slice(0, 5).map((expense) => (
                                        <div key={expense.id} className="flex justify-between items-center p-3 rounded-lg border">
                                            <div>
                                                <p className="font-medium text-sm">{expense.title}</p>
                                                <p className="text-xs text-gray-600">{expense.description}</p>
                                                <p className="text-xs text-gray-500">{formatDate(expense.date)}</p>
                                            </div>
                                            <div className="text-red-600 font-bold">{formatCurrency(expense.amount)}</div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>


        </AuthenticatedLayout>
    );
}
