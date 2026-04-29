import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, DollarSign, CheckCircle, Play, X } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency } from '@/utils/helpers';
import Create from './Create';
import Edit from './Edit';

interface Budget {
    id: number;
    budget_name: string;
    budget_type: string;
    total_budget_amount: number;
    status: string;
    budget_period?: { period_name: string; };
    approved_by?: { name: string; };
}

export default function Index() {
    const { t } = useTranslation();
    const { budgets, budgetPeriods, auth } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        budget_name: urlParams.get('budget_name') || '',
        budget_type: urlParams.get('budget_type') || '',
        status: urlParams.get('status') || '',
        period_id: urlParams.get('period_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'budget-planner.budgets.destroy',
        defaultMessage: t('Are you sure you want to delete this budget?')
    });

    const handleFilter = () => {
        router.get(route('budget-planner.budgets.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('budget-planner.budgets.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            budget_name: '',
            budget_type: '',
            status: '',
            period_id: '',
        });
        router.get(route('budget-planner.budgets.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: Budget | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'budget_name',
            header: t('Budget Name'),
            sortable: true
        },
        {
            key: 'budget_period',
            header: t('Period'),
            sortable: false,
            render: (value: any, row: Budget) => row.budget_period?.period_name || '-'
        },
        {
            key: 'budget_type',
            header: t('Type'),
            sortable: false,
            render: (value: string) => {
                const getTypeColor = (type: string) => {
                    switch(type) {
                        case 'operational': return 'bg-purple-100 text-purple-800';
                        case 'capital': return 'bg-orange-100 text-orange-800';
                        case 'cash_flow': return 'bg-cyan-100 text-cyan-800';
                        default: return 'bg-gray-100 text-gray-800';
                    }
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${getTypeColor(value)} capitalize`}>
                        {value === 'cash_flow' ? 'Cash Flow' : value}
                    </span>
                );
            }
        },
        {
            key: 'total_budget_amount',
            header: t('Amount'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
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
            render: (value: any, row: Budget) => row.approved_by?.name || '-'
        },
        ...(() => {
            const hasAnyActionPermission = (budget: Budget) => {
                const permissions = auth.user?.permissions || [];
                return (
                    (budget.status === 'draft' && (permissions.includes('approve-budgets') || permissions.includes('edit-budgets') || permissions.includes('delete-budgets'))) ||
                    (budget.status === 'approved' && permissions.includes('active-budgets')) ||
                    (budget.status === 'active' && permissions.includes('close-budgets'))
                );
            };

            return budgets?.data?.some(hasAnyActionPermission) ? [{
                key: 'actions',
                header: t('Actions'),
                render: (_: any, budget: Budget) => {
                    if (!hasAnyActionPermission(budget)) return null;

                    return (
                        <div className="flex gap-1">
                            <TooltipProvider>
                                {budget.status === 'draft' && auth.user?.permissions?.includes('approve-budgets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budgets.approve', budget.id))}
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
                                {budget.status === 'approved' && auth.user?.permissions?.includes('active-budgets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budgets.active', budget.id))}
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
                                {budget.status === 'active' && auth.user?.permissions?.includes('close-budgets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('budget-planner.budgets.close', budget.id))}
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
                                {budget.status === 'draft' && auth.user?.permissions?.includes('edit-budgets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openModal('edit', budget)}
                                                className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                            >
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Edit')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {budget.status === 'draft' && auth.user?.permissions?.includes('delete-budgets') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(budget.id)}
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
                {label: t('Budget')}
            ]}
            pageTitle={t('Manage Budget')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-budgets') && (
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
            <Head title={t('Budgets')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.budget_name}
                                onChange={(value) => setFilters({...filters, budget_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Budgets...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="budget-planner.budgets.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="budget-planner.budgets.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <FilterButton
                                showFilters={showFilters}
                                onToggle={() => setShowFilters(!showFilters)}
                            />
                        </div>
                    </div>
                </CardContent>

                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Budget Period')}</label>
                                <Select value={filters.period_id} onValueChange={(value) => setFilters({...filters, period_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Period')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {budgetPeriods?.filter((period: any) => period.status === 'active').map((period: any) => (
                                            <SelectItem key={period.id} value={period.id.toString()}>
                                                {period.period_name} ({period.financial_year})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Type')}</label>
                                <Select value={filters.budget_type} onValueChange={(value) => setFilters({...filters, budget_type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="operational">{t('Operational')}</SelectItem>
                                        <SelectItem value="capital">{t('Capital')}</SelectItem>
                                        <SelectItem value="cash_flow">{t('Cash Flow')}</SelectItem>
                                    </SelectContent>
                                </Select>
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
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={budgets?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={DollarSign}
                                            title={t('No Budgets found')}
                                            description={t('Get started by creating your first Budget.')}
                                            hasFilters={!!(filters.budget_name || filters.budget_type || filters.status || filters.period_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-budgets"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Budget')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {budgets?.data && budgets.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    {budgets.data.map((budget) => (
                                        <Card key={budget.id} className="border border-gray-200 hover:shadow-md transition-shadow">
                                            <div className="p-4">
                                                <div className="flex items-center justify-between mb-3">
                                                    <h3 className="font-semibold text-base text-gray-900">{budget.budget_name}</h3>
                                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                                        budget.status === 'draft' ? 'bg-gray-100 text-gray-800' :
                                                        budget.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                        budget.status === 'active' ? 'bg-blue-100 text-blue-800' :
                                                        budget.status === 'closed' ? 'bg-red-100 text-red-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {budget.status ? budget.status.charAt(0).toUpperCase() + budget.status.slice(1) : '-'}
                                                    </span>
                                                </div>

                                                <div className="space-y-3 mb-4">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Period')}</p>
                                                        <p className="text-sm text-gray-900 truncate font-medium">{budget.budget_period?.period_name || '-'}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Type')}</p>
                                                            <span className={`px-2 py-1 rounded-full text-xs capitalize ${
                                                                budget.budget_type === 'operational' ? 'bg-purple-100 text-purple-800' :
                                                                budget.budget_type === 'capital' ? 'bg-orange-100 text-orange-800' :
                                                                budget.budget_type === 'cash_flow' ? 'bg-cyan-100 text-cyan-800' :
                                                                'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {budget.budget_type === 'cash_flow' ? 'Cash Flow' : budget.budget_type}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1 text-end">{t('Approved By')}</p>
                                                            <p className="text-xs text-gray-900 text-end">{budget.approved_by?.name || '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Amount')}</span>
                                                            <span className="text-lg font-bold text-green-600">{formatCurrency(budget.total_budget_amount)}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex justify-end gap-1 pt-3 border-t">
                                                    <TooltipProvider>
                                                        {budget.status === 'draft' && auth.user?.permissions?.includes('approve-budgets') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => router.post(route('budget-planner.budgets.approve', budget.id))}
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
                                                        {budget.status === 'approved' && auth.user?.permissions?.includes('active-budgets') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => router.post(route('budget-planner.budgets.active', budget.id))}
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
                                                        {budget.status === 'active' && auth.user?.permissions?.includes('close-budgets') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => router.post(route('budget-planner.budgets.close', budget.id))}
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
                                                        {budget.status === 'draft' && auth.user?.permissions?.includes('edit-budgets') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => openModal('edit', budget)}
                                                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                    >
                                                                        <EditIcon className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Edit')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {budget.status === 'draft' && auth.user?.permissions?.includes('delete-budgets') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => openDeleteDialog(budget.id)}
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
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={DollarSign}
                                    title={t('No Budgets found')}
                                    description={t('Get started by creating your first Budget.')}
                                    hasFilters={!!(filters.budget_name || filters.budget_type || filters.status || filters.period_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-budgets"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Budget')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={budgets || { data: [], links: [], meta: {} }}
                        routeName="budget-planner.budgets.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        budget={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Budget')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
