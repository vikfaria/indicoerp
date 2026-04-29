import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { BarChart3, TrendingUp, DollarSign } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line } from 'recharts';

interface SalesReportProps {
    salesData: {
        data: Array<{
            id: number;
            sale_number: string;
            total: number;
            created_at: string;
            customer?: { name: string };
            warehouse?: { name: string };
        }>;
    };
    dailySales?: Array<{ date: string; sales: number; count: number }>;
    monthlySales?: Array<{ month: string; sales: number; count: number }>;
    warehouseSales?: Array<{ name: string; sales: number; count: number }>;
}

export default function SalesReport({ salesData, dailySales, monthlySales, warehouseSales }: SalesReportProps) {
    const { t } = useTranslation();
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('POS'), url: route('pos.index') },
                { label: t('Sales Report') }
            ]}
            pageTitle={t('Sales Report')}
        >
            <Head title={t('Sales Report')} />

            <Tabs defaultValue="daily" className="w-full">
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="daily">{t('Daily Sales')}</TabsTrigger>
                        <TabsTrigger value="monthly">{t('Monthly Sales')}</TabsTrigger>
                        <TabsTrigger value="warehouse">{t('Warehouse Sales')}</TabsTrigger>
                    </TabsList>

                <TabsContent value="daily" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Daily Sales Performance')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={dailySales || []}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="date" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => [formatCurrency(value), t('Sales')]} />
                                    <Bar dataKey="sales" fill="#8884d8" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="monthly" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Monthly Sales Performance')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={monthlySales || []}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => [formatCurrency(value), t('Sales')]} />
                                    <Bar dataKey="sales" fill="#00C49F" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="warehouse" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Warehouse Sales Comparison')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={warehouseSales || []}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => [formatCurrency(value), t('Sales')]} />
                                    <Bar dataKey="sales" fill="#ff7300" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </AuthenticatedLayout>
    );
}