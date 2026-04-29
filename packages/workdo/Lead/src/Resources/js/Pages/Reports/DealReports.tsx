import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Calendar } from 'lucide-react';
import { formatDate } from '@/utils/helpers';
import { DatePicker } from "@/components/ui/date-picker";

interface DealReportsProps {
    weeklyDealConversions: any[];
    dealSourcesConversion: any[];
    monthlyDeals: any[];
    staffDeals: any[];
    clientDeals: any[];
    pipelineDeals: any[];
}

export default function DealReports() {
    const { t } = useTranslation();
    const { weeklyDealConversions, dealSourcesConversion, monthlyDeals, staffDeals, clientDeals, pipelineDeals } = usePage<DealReportsProps>().props;
    
    const [selectedMonth, setSelectedMonth] = useState('all');
    const [fromDate, setFromDate] = useState(new URLSearchParams(window.location.search).get('from_date') || '');
    const [toDate, setToDate] = useState(new URLSearchParams(window.location.search).get('to_date') || '');
    const [filteredStaffDeals, setFilteredStaffDeals] = useState(staffDeals);
    const [filteredClientDeals, setFilteredClientDeals] = useState(clientDeals);

    useEffect(() => {
        setFilteredStaffDeals(staffDeals);
        setFilteredClientDeals(clientDeals);
    }, [staffDeals, clientDeals]);

    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

    const months = [
        { value: 1, label: t('January') },
        { value: 2, label: t('February') },
        { value: 3, label: t('March') },
        { value: 4, label: t('April') },
        { value: 5, label: t('May') },
        { value: 6, label: t('June') },
        { value: 7, label: t('July') },
        { value: 8, label: t('August') },
        { value: 9, label: t('September') },
        { value: 10, label: t('October') },
        { value: 11, label: t('November') },
        { value: 12, label: t('December') }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('CRM'), url: route('lead.index')},
                {label: t('Reports')},
                {label: t('Deal Reports')}
            ]}
            pageTitle={t('Deal Reports')}
        >
            <Head title={t('Deal Reports')} />

            <Tabs defaultValue="general" className="w-full">
                <TabsList className="grid w-full grid-cols-4">
                    <TabsTrigger value="general">{t('General Report')}</TabsTrigger>
                    <TabsTrigger value="staff">{t('Staff Report')}</TabsTrigger>
                    <TabsTrigger value="client">{t('Client Report')}</TabsTrigger>
                    <TabsTrigger value="pipeline">{t('Pipeline Report')}</TabsTrigger>
                </TabsList>

                <TabsContent value="general" className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('This Week Deal Status')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={weeklyDealConversions}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({name, percent}) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {weeklyDealConversions?.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Deal Sources Conversion')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart data={dealSourcesConversion}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" />
                                        <YAxis />
                                        <Tooltip />
                                        <Bar dataKey="value" fill="#8884d8" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>{t('Per Month Deal')}</CardTitle>
                            <Select value={selectedMonth} onValueChange={(value) => setSelectedMonth(value)}>
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('Select Month')}</SelectItem>
                                    {months.map((month) => (
                                        <SelectItem key={month.value} value={month.value.toString()}>
                                            {month.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={selectedMonth === 'all' ? monthlyDeals : 
                                    [{
                                        name: months.find(m => m.value.toString() === selectedMonth)?.label + ' 2024',
                                        deals: monthlyDeals?.find(item => item.month === parseInt(selectedMonth))?.deals || 0
                                    }]}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="deals" fill="#ff6b9d" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="staff" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-end gap-4">
                                <div className="flex-1 min-w-[200px]">
                                    <Label htmlFor="from-date" className="text-sm font-medium">{t('From Date')}</Label>
                                    <DatePicker
                                        value={fromDate}
                                        onChange={(value) => setFromDate(value)}
                                        placeholder={t('Select from date')}
                                    />
                                </div>
                                <div className="flex-1 min-w-[200px]">
                                    <Label htmlFor="to-date" className="text-sm font-medium">{t('To Date')}</Label>
                                    <DatePicker
                                        value={toDate}
                                        onChange={(value) => setToDate(value)}
                                        placeholder={t('Select to date')}
                                    />
                                </div>
                                <Button 
                                    onClick={() => {
                                        if (fromDate && toDate) {
                                            router.get(route('lead.reports.deals'), {
                                                from_date: fromDate,
                                                to_date: toDate
                                            }, {
                                                preserveState: true,
                                                preserveScroll: true,
                                                only: ['staffDeals'],
                                                onSuccess: (page) => {
                                                    setFilteredStaffDeals(page.props.staffDeals);
                                                }
                                            });
                                        } else {
                                            alert('Please select both from and to dates');
                                        }
                                    }}
                                    className="px-6"
                                >
                                    <Calendar className="h-4 w-4 mr-2" />
                                    {t('Generate')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={filteredStaffDeals}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="deals" fill="#00C49F" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="client" className="space-y-6">
                    <Card>
                    <CardHeader>
                            <CardTitle>{t('Clients')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={filteredClientDeals || []}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="deals" fill="#8B5CF6" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="pipeline" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Pipelines')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={pipelineDeals}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="deals" fill="#ff7300" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </AuthenticatedLayout>
    );
}