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
import { Plus, Edit as EditIcon, Trash2, Flag, ArrowLeft, Eye } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import Create from './Create';
import EditMilestone from './Edit';
import ViewMilestone from './View';
import NoRecordsFound from '@/components/no-records-found';
import { GoalMilestone, MilestonesIndexProps, MilestoneFilters, MilestoneModalState } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { milestones, goals, auth } = usePage<MilestonesIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<MilestoneFilters>({
        milestone_name: urlParams.get('milestone_name') || '',
        status: urlParams.get('status') || '',
        goal_id: urlParams.get('goal_id') || '',
        date_range: (() => {
            const fromDate = urlParams.get('date_from');
            const toDate = urlParams.get('date_to');
            return (fromDate && toDate) ? `${fromDate} - ${toDate}` : '';
        })(),
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<MilestoneModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<GoalMilestone | null>(null);
    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'goal.milestones.destroy',
        defaultMessage: t('Are you sure you want to delete this milestone?')
    });

    const handleFilter = () => {
        const filterParams = {...filters};

        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('goal.milestones.index'), {...filterParams, per_page: perPage, sort: sortField, direction: sortDirection}, {
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

        router.get(route('goal.milestones.index'), {...filterParams, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            milestone_name: '',
            status: '',
            goal_id: '',
            date_range: '',
        });
        router.get(route('goal.milestones.index'), {per_page: perPage});
    };

    const openModal = (mode: 'add' | 'edit', data: GoalMilestone | null = null) => {
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
            render: (_: any, milestone: GoalMilestone) => milestone.goal?.goal_name || '-'
        },
        {
            key: 'milestone_name',
            header: t('Milestone Name'),
            sortable: true
        },
        {
            key: 'target_amount',
            header: t('Target Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'achieved_amount',
            header: t('Achieved Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'achieved_date',
            header: t('Achieved Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'target_date',
            header: t('Target Date'),
            sortable: false,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'achieved' ? 'bg-green-100 text-green-800' :
                    value === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-gray-100 text-gray-800'
                }`}>
                    {t(value.replace('_', ' ').charAt(0).toUpperCase() + value.replace('_', ' ').slice(1))}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-goal-milestones', 'edit-goal-milestones', 'delete-goal-milestones'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, milestone: GoalMilestone) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-goal-milestones') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(milestone)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-goal-milestones') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', milestone)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-goal-milestones') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(milestone.id)}
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
                {label: t('Milestones')}
            ]}
            pageTitle={t('Manage Milestones')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-goal-milestones') && (
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
            <Head title={t('Milestones')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.milestone_name}
                                onChange={(value) => setFilters({...filters, milestone_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Milestones...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="goal.milestones.index"
                                filters={{...filters}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.goal_id, filters.status, filters.date_range].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status || 'all'} onValueChange={(value) => setFilters({...filters, status: value === 'all' ? '' : value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Status')}</SelectItem>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="achieved">{t('Achieved')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Target Date Range')}</label>
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
                            data={milestones?.data || []}
                            columns={tableColumns}
                            onSort={handleSort}
                            sortKey={sortField}
                            sortDirection={sortDirection as 'asc' | 'desc'}
                            className="rounded-none"
                            emptyState={
                                <NoRecordsFound
                                    icon={Flag}
                                    title={t('No Milestones found')}
                                    description={t('Get started by creating your first Milestone.')}
                                    hasFilters={!!(filters.milestone_name || filters.status || filters.goal_id || filters.date_range)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-goal-milestones"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Milestone')}
                                    className="h-auto"
                                />
                            }
                        />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={milestones || { data: [], links: [], meta: {} }}
                        routeName="goal.milestones.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create goals={goals} onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditMilestone
                        milestone={modalState.data}
                        goals={goals}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <ViewMilestone milestone={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Milestone')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
