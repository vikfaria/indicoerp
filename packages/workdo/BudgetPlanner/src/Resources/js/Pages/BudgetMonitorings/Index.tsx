import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { BarChart3 } from "lucide-react";
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency, formatDate } from '@/utils/helpers';

interface BudgetMonitoring {
    id: number;
    budget?: { budget_name: string; };
    monitoring_date: string;
    total_allocated: number;
    total_spent: number;
    total_remaining: number;
    variance_amount: number;
    variance_percentage: number;
}

export default function Index() {
    const { t } = useTranslation();
    const { budgetMonitorings, budgets } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        search: urlParams.get('search') || '',
        budget_id: urlParams.get('budget_id') || '',
        date_range: (() => {
            const fromDate = urlParams.get('date_from');
            const toDate = urlParams.get('date_to');
            return (fromDate && toDate) ? `${fromDate} - ${toDate}` : '';
        })(),
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [showFilters, setShowFilters] = useState(false);


    const handleFilter = () => {
        const filterParams: any = {
            search: filters.search,
            budget_id: filters.budget_id,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection
        };

        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }

        router.get(route('budget-planner.budget-monitorings.index'), filterParams, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);

        const filterParams = { ...filters };
        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('budget-planner.budget-monitorings.index'), {...filterParams, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', budget_id: '', date_range: '' });
        router.get(route('budget-planner.budget-monitorings.index'));
    };

    const tableColumns = [
        {
            key: 'budget',
            header: t('Budget'),
            sortable: true,
            render: (value: any, row: BudgetMonitoring) => row.budget?.budget_name || '-'
        },
        {
            key: 'monitoring_date',
            header: t('Date'),
            sortable: true,
             render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'total_allocated',
            header: t('Allocated'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_spent',
            header: t('Spent'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'total_remaining',
            header: t('Remaining'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'variance_percentage',
            header: t('Variance %'),
            sortable: false,
            render: (value: any) => `${Number(value || 0).toFixed(2)}%`
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Budget Planner')},
                {label: t('Budget Monitoring')}
            ]}
            pageTitle={t('Budget Monitoring')}
        >
            <Head title={t('Budget Monitoring')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Budget Monitoring...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="budget-planner.budget-monitorings.index"
                                filters={{...filters}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.budget_id, filters.date_range].filter(f => f !== '' && f !== null && f !== undefined).length;
                                    return activeFilters > 0 && (
                                        <span className="absolute -top-2 -right-2 bg-primary text-primary-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                                            {activeFilters}
                                        </span>
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                </CardContent>

                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Budget')}</label>
                                <Select value={filters.budget_id} onValueChange={(value) => setFilters({...filters, budget_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Budget')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {budgets?.map((budget: any) => (
                                            <SelectItem key={budget.id} value={budget.id.toString()}>
                                                {budget.budget_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DateRangePicker
                                    value={filters.date_range}
                                    onChange={(value) => setFilters({...filters, date_range: value})}
                                    placeholder={t('Select date range')}
                                />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={budgetMonitorings?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={BarChart3}
                                        title={t('No Budget Monitoring found')}
                                        description={t('Budget monitoring data will appear here.')}
                                        hasFilters={!!(filters.search || filters.budget_id || filters.date_range)}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={budgetMonitorings || { data: [], links: [], meta: {} }}
                        routeName="budget-planner.budget-monitorings.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
