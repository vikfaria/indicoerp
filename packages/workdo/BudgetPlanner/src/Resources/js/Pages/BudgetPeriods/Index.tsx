import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Calendar as CalendarIcon, CheckCircle, Play, X } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import Edit from './Edit';

import NoRecordsFound from '@/components/no-records-found';
import { BudgetPeriod, BudgetPeriodsIndexProps, BudgetPeriodFilters, BudgetPeriodModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { budgetperiods, auth } = usePage<BudgetPeriodsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<BudgetPeriodFilters>({
        period_name: urlParams.get('period_name') || '',
        financial_year: urlParams.get('financial_year') || '',
        status: urlParams.get('status') || '',
        date_range: (() => {
            const fromDate = urlParams.get('date_from');
            const toDate = urlParams.get('date_to');
            return (fromDate && toDate) ? `${fromDate} - ${toDate}` : '';
        })(),
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<BudgetPeriodModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'budget-planner.budget-periods.destroy',
        defaultMessage: t('Are you sure you want to delete this budget period?')
    });

    const handleFilter = () => {
        const filterParams: any = {
            period_name: filters.period_name,
            financial_year: filters.financial_year,
            status: filters.status,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection
        };

        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }

        router.get(route('budget-planner.budget-periods.index'), filterParams, {
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
        
        router.get(route('budget-planner.budget-periods.index'), {...filterParams, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            period_name: '',
            financial_year: '',
            status: '',
            date_range: '',
        });
        router.get(route('budget-planner.budget-periods.index'), {per_page: perPage});
    };

    const openModal = (mode: 'add' | 'edit', data: BudgetPeriod | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'period_name',
            header: t('Period Name'),
            sortable: true
        },
        {
            key: 'financial_year',
            header: t('Financial Year'),
            sortable: true
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const getStatusColor = (status: string) => {
                    switch(status) {
                        case 'draft': return 'bg-gray-100 text-gray-800';
                        case 'approved': return 'bg-green-100 text-green-800';
                        case 'active': return 'bg-blue-100 text-blue-800';
                        case 'closed': return 'bg-red-100 text-red-800';
                        default: return 'bg-gray-100 text-gray-800';
                    }
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(value)}`}>
                        {value ? value.charAt(0).toUpperCase() + value.slice(1) : '-'}
                    </span>
                );
            }
        },
        {
            key: 'approved_by',
            header: t('Approved By'),
            sortable: false,
            render: (value: any, row: any) => row.approved_by?.name || '-'
        },

        ...(() => {
            const hasAnyActionPermission = (budgetperiod: BudgetPeriod) => {
                const permissions = auth.user?.permissions || [];
                return (
                    (budgetperiod.status === 'draft' && (permissions.includes('approve-budget-periods') || permissions.includes('edit-budget-periods') || permissions.includes('delete-budget-periods'))) ||
                    (budgetperiod.status === 'approved' && permissions.includes('active-budget-periods')) ||
                    (budgetperiod.status === 'active' && permissions.includes('close-budget-periods'))
                );
            };

            return budgetperiods?.data?.some(hasAnyActionPermission) ? [{
                key: 'actions',
                header: t('Actions'),
                render: (_: any, budgetperiod: BudgetPeriod) => {
                    if (!hasAnyActionPermission(budgetperiod)) return null;

                    return (
                        <div className="flex gap-1">
                            <TooltipProvider>
                                {budgetperiod.status === 'draft' && auth.user?.permissions?.includes('approve-budget-periods') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budget-periods.approve', budgetperiod.id))}
                                                className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                            >
                                                <CheckCircle className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Approve')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {budgetperiod.status === 'approved' && auth.user?.permissions?.includes('active-budget-periods') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budget-periods.active', budgetperiod.id))}
                                                className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                            >
                                                <Play className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Active')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {budgetperiod.status === 'active' && auth.user?.permissions?.includes('close-budget-periods') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budget-periods.close', budgetperiod.id))}
                                                className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Close')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {budgetperiod.status === 'draft' && auth.user?.permissions?.includes('edit-budget-periods') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', budgetperiod)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Edit')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {budgetperiod.status === 'draft' && auth.user?.permissions?.includes('delete-budget-periods') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(budgetperiod.id)}
                                                className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Delete')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </TooltipProvider>
                        </div>
                    );
                }
            }] : [];
        })()
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Budget Planner')},
                {label: t('Budget Periods')}
            ]}
            pageTitle={t('Manage Budget Periods')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-budget-periods') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => openModal('add')}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Create')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </TooltipProvider>
            }
        >
            <Head title={t('Budget Periods')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.period_name}
                                onChange={(value) => setFilters({...filters, period_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Budget Periods...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">

                            <PerPageSelector
                                routeName="budget-planner.budget-periods.index"
                                filters={{...filters}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.financial_year, filters.date_range].filter(f => f !== '' && f !== null && f !== undefined).length;
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

                {/* Advanced Filters */}
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Financial Year')}</label>
                                <Input
                                    value={filters.financial_year}
                                    onChange={(e) => setFilters({...filters, financial_year: e.target.value})}
                                    placeholder={t('Enter Financial Year')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="active">{t('Active')}</SelectItem>
                                        <SelectItem value="closed">{t('Closed')}</SelectItem>
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

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={budgetperiods?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={CalendarIcon}
                                        title={t('No Budget Periods found')}
                                        description={t('Get started by creating your first Budget Period.')}
                                        hasFilters={!!(filters.period_name || filters.financial_year || filters.status || filters.date_range)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-budget-periods"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Budget Period')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={budgetperiods || { data: [], links: [], meta: {} }}
                        routeName="budget-planner.budget-periods.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        budgetperiod={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Budget Period')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
