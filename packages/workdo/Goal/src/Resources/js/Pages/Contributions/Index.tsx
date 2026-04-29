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
import { Plus, Edit as EditIcon, Trash2, DollarSign } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Badge } from '@/components/ui/badge';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import CreateContribution from './Create';
import EditContribution from './Edit';
import NoRecordsFound from '@/components/no-records-found';
import { GoalContribution, ContributionsIndexProps, ContributionFilters, ContributionModalState } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { contributions, goals, auth } = usePage<ContributionsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ContributionFilters>({
        goal_name: urlParams.get('goal_name') || '',
        goal_id: urlParams.get('goal_id') || '',
        contribution_type: urlParams.get('contribution_type') || '',
        date_range: (() => {
            const fromDate = urlParams.get('date_from');
            const toDate = urlParams.get('date_to');
            return (fromDate && toDate) ? `${fromDate} - ${toDate}` : '';
        })(),
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');

    const [modalState, setModalState] = useState<ContributionModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'goal.contributions.destroy',
        defaultMessage: t('Are you sure you want to delete this contribution?')
    });

    const handleFilter = () => {
        const filterParams = {...filters};

        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('goal.contributions.index'), {...filterParams, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);

        const filterParams = {...filters};
        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('goal.contributions.index'), {...filterParams, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            goal_name: '',
            goal_id: '',
            contribution_type: '',
            date_range: '',
        });
        router.get(route('goal.contributions.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: GoalContribution | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'goal',
            header: t('Goal'),
            sortable: false,
            render: (_: any, contribution: GoalContribution) => contribution.goal?.goal_name || '-'
        },
        {
            key: 'contribution_date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'contribution_amount',
            header: t('Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'contribution_type',
            header: t('Type'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'manual' ? 'bg-blue-100 text-blue-800' :
                    value === 'automatic' ? 'bg-green-100 text-green-800' :
                    value === 'journal_entry' ? 'bg-purple-100 text-purple-800' :
                    'bg-gray-100 text-gray-800'
                }`}>
                    {t(value.replace('_', ' ').charAt(0).toUpperCase() + value.replace('_', ' ').slice(1))}
                </span>
            )
        },
        {
            key: 'notes',
            header: t('Notes'),
            sortable: false,
            render: (value: string) => value || '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-goal-contributions', 'delete-goal-contributions'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, contribution: GoalContribution) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-goal-contributions') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', contribution)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-goal-contributions') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(contribution.id)}
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
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Goal')},
                {label: t('Contributions')}
            ]}
            pageTitle={t('Manage Contributions')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-goal-contributions') && (
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
            <Head title={t('Contributions')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.goal_name}
                                onChange={(value) => setFilters({...filters, goal_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Contributions...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="goal.contributions.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="goal.contributions.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.goal_name, filters.goal_id, filters.contribution_type, filters.date_range].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Goal')}</label>
                                <Select value={filters.goal_id || 'all'} onValueChange={(value) => setFilters({...filters, goal_id: value === 'all' ? '' : value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Goal')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Goals')}</SelectItem>
                                        {goals?.map((goal) => (
                                            <SelectItem key={goal.id} value={goal.id.toString()}>
                                                {goal.goal_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Type')}</label>
                                <Select value={filters.contribution_type || 'all'} onValueChange={(value) => setFilters({...filters, contribution_type: value === 'all' ? '' : value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Types')}</SelectItem>
                                        <SelectItem value="manual">{t('Manual')}</SelectItem>
                                        <SelectItem value="automatic">{t('Automatic')}</SelectItem>
                                        <SelectItem value="journal_entry">{t('Journal Entry')}</SelectItem>
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
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                            <DataTable
                                data={contributions?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={DollarSign}
                                        title={t('No Contributions found')}
                                        description={t('Get started by creating your first Contribution.')}
                                        hasFilters={!!(filters.goal_name || filters.goal_id || filters.contribution_type || filters.date_range)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-goal-contributions"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Contribution')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {contributions?.data && contributions.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {contributions.data.map((contribution) => (
                                        <Card key={contribution.id} className="border border-gray-200 hover:shadow-md transition-shadow">
                                            <div className="p-4">
                                                <div className="flex items-center justify-between mb-3">
                                                    <h3 className="font-semibold text-base text-gray-900 truncate">{contribution.goal?.goal_name}</h3>
                                                </div>

                                                <div className="space-y-3 mb-4">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Date')}</p>
                                                        <p className="text-sm text-gray-900 font-medium">{formatDate(contribution.contribution_date)}</p>
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Amount')}</span>
                                                            <span className="text-lg font-bold text-green-600">{formatCurrency(contribution.contribution_amount)}</span>
                                                        </div>
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-sm text-gray-600">{t('Type')}</span>
                                                            <span className={`px-2 py-1 rounded-full text-xs ${
                                                                contribution.contribution_type === 'manual' ? 'bg-blue-100 text-blue-800' :
                                                                contribution.contribution_type === 'automatic' ? 'bg-green-100 text-green-800' :
                                                                contribution.contribution_type === 'journal_entry' ? 'bg-purple-100 text-purple-800' :
                                                                'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {t(contribution.contribution_type.replace('_', ' ').charAt(0).toUpperCase() + contribution.contribution_type.replace('_', ' ').slice(1))}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {contribution.notes && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Notes')}</p>
                                                            <p className="text-xs text-gray-900">{contribution.notes}</p>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex justify-end pt-3 border-t">
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('edit-goal-contributions') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', contribution)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-goal-contributions') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(contribution.id)}
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
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={DollarSign}
                                    title={t('No Contributions found')}
                                    description={t('Get started by creating your first Contribution.')}
                                    hasFilters={!!(filters.goal_name || filters.goal_id || filters.contribution_type || filters.date_range)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-goal-contributions"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Contribution')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={contributions || { data: [], links: [], meta: {} }}
                        routeName="goal.contributions.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <CreateContribution onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditContribution
                        contribution={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Contribution')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
