import { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import { formatDate } from '@/utils/helpers';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, RefreshCw, Eye } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Dialog } from '@/components/ui/dialog';
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';
import EditReviewCycle from './Edit';
import Show from './Show';

interface ReviewCycle {
    id: number;
    name: string;
    frequency: string;
    description: string;
    status: string;
    created_at: string;
}

interface ReviewCycleFilters {
    name: string;
    frequency: string;
    status: string;
}

interface ReviewCycleModalState {
    isOpen: boolean;
    mode: string;
    data: ReviewCycle | null;
}

export default function Index() {
    const { t } = useTranslation();
    const { reviewCycles, auth } = usePage<{ reviewCycles: any; auth: any }>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), [window.location.search]);

    const [filters, setFilters] = useState<ReviewCycleFilters>({
        name: urlParams.get('name') || '',
        frequency: urlParams.get('frequency') || '',
        status: urlParams.get('status') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState<ReviewCycleModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    // Component for review cycle action buttons
    const ReviewCycleActionButtons = ({ reviewCycle }: { reviewCycle: ReviewCycle }) => {
        const actionButtons = usePageButtons('reviewCycleActionButtons', { review_cycle_id: reviewCycle.id, auth });
        return (
            <>
                {actionButtons.map((button) => (
                    <div key={button.id}>{button.component}</div>
                ))}
            </>
        );
    };

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'performance.review-cycles.destroy',
        defaultMessage: t('Are you sure you want to delete this review cycle?')
    });

    const handleFilter = () => {
        router.get(route('performance.review-cycles.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('performance.review-cycles.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', frequency: '', status: '' });
        router.get(route('performance.review-cycles.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit' | 'show', data: ReviewCycle | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const getFrequencyLabel = (frequency: string) => {
        const labels: { [key: string]: string } = {
            monthly: t('Monthly'),
            quarterly: t('Quarterly'),
            'semi-annual': t('Semi-Annual'),
            annual: t('Annual')
        };
        return labels[frequency] || frequency;
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'frequency',
            header: t('Frequency'),
            sortable: true,
            render: (value: string) => getFrequencyLabel(value)
        },
        {
            key: 'description',
            header: t('Description'),
            render: (value: string) => value ? (
                <div className="p-4 align-middle text-foreground">
                    <p className="whitespace-pre-wrap break-words text-sm leading-relaxed">{value}</p>
                </div>
            ) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                    {value === 'active' ? t('Active') : t('Inactive')}
                </span>
            )
        },
        {
            key: 'created_at',
            header: t('Created'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-review-cycles', 'edit-review-cycles', 'delete-review-cycles'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, reviewCycle: ReviewCycle) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <ReviewCycleActionButtons reviewCycle={reviewCycle} />
                        {auth.user?.permissions?.includes('view-review-cycles') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openModal('show', reviewCycle)}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-review-cycles') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openModal('edit', reviewCycle)}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-review-cycles') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(reviewCycle.id)}
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
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    { label: t('Performance')},
                    { label: t('Review Cycles') }
                ]}
                pageTitle={t('Manage Review Cycles')}
                pageActions={
                    <div className="flex gap-2">
                        {auth.user?.permissions?.includes('create-review-cycles') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Create')}</p></TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                }
            >
                <Head title={t('Review Cycles')} />

                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.name}
                                    onChange={(value) => setFilters({ ...filters, name: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search review cycles...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <ListGridToggle
                                    currentView={viewMode}
                                    routeName="performance.review-cycles.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                                <PerPageSelector
                                    routeName="performance.review-cycles.index"
                                    filters={{ ...filters, view: viewMode }}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.frequency, filters.status].filter(Boolean).length;
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
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Frequency')}</label>
                                    <Select value={filters.frequency} onValueChange={(value) => setFilters({ ...filters, frequency: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Frequencies')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="monthly">{t('Monthly')}</SelectItem>
                                            <SelectItem value="quarterly">{t('Quarterly')}</SelectItem>
                                            <SelectItem value="semi-annual">{t('Semi-Annual')}</SelectItem>
                                            <SelectItem value="annual">{t('Annual')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                    <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Status')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">{t('Active')}</SelectItem>
                                            <SelectItem value="inactive">{t('Inactive')}</SelectItem>
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
                                        data={reviewCycles.data}
                                        columns={tableColumns}
                                        onSort={handleSort}
                                        sortKey={sortField}
                                        sortDirection={sortDirection as 'asc' | 'desc'}
                                        className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={RefreshCw}
                                            title={t('No review cycles found')}
                                            description={t('Get started by creating your first review cycle.')}
                                            hasFilters={!!(filters.name || filters.frequency || filters.status)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-review-cycles"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Review Cycle')}
                                            className="h-auto"
                                        />
                                    }
                                />
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-auto max-h-[70vh] p-6">
                                {reviewCycles.data.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                        {reviewCycles.data.map((cycle: ReviewCycle) => (
                                            <Card key={cycle.id} className="p-0 hover:shadow-lg transition-all duration-200 flex flex-col h-full min-w-0">
                                                {/* Header */}
                                                <div className="p-4 border-b flex-shrink-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-primary/10 rounded-lg">
                                                            <RefreshCw className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-semibold text-sm text-gray-900">{cycle.name}</h3>
                                                            <p className="text-xs text-gray-500 truncate">{getFrequencyLabel(cycle.frequency)}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Body */}
                                                <div className="p-4 flex-1 min-h-0">
                                                    <div className="mb-4">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Description')}</p>
                                                        <p className="font-medium text-xs line-clamp-3">{cycle.description || '-'}</p>
                                                    </div>

                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Created')}</p>
                                                        <p className="font-medium text-xs">{formatDate(cycle.created_at)}</p>
                                                    </div>
                                                </div>

                                                {/* Actions Footer */}
                                                <div className="flex justify-between items-center gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                                        cycle.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {cycle.status === 'active' ? t('Active') : t('Inactive')}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            <ReviewCycleActionButtons reviewCycle={cycle} />
                                                            {auth.user?.permissions?.includes('view-review-cycles') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openModal('show', cycle)}
                                                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                                        >
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-review-cycles') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openModal('edit', cycle)}
                                                                            className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                        >
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-review-cycles') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(cycle.id)}
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
                                        icon={RefreshCw}
                                        title={t('No review cycles found')}
                                        description={t('Get started by creating your first review cycle.')}
                                        hasFilters={!!(filters.name || filters.frequency || filters.status)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-review-cycles"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Review Cycle')}
                                        className="h-auto"
                                    />
                                )}
                            </div>
                        )}
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={reviewCycles}
                            routeName="performance.review-cycles.index"
                            filters={{ ...filters, per_page: perPage, view: viewMode }}
                        />
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Review Cycle')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create onSuccess={closeModal} />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditReviewCycle reviewCycle={modalState.data} onSuccess={closeModal} />
                    )}
                    {modalState.mode === 'show' && modalState.data && (
                        <Show reviewCycle={modalState.data} />
                    )}
                </Dialog>
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}