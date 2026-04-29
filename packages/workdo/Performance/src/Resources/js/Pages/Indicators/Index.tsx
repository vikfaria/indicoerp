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
import { Plus, Edit, Trash2, Eye, BarChart3 } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Dialog } from "@/components/ui/dialog";
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';
import EditIndicator from './Edit';
import Show from './Show';

interface PerformanceIndicator {
    id: number;
    name: string;
    description: string;
    measurement_unit: string;
    target_value: string;
    status: string;
    created_at: string;
    category?: {
        id: number;
        name: string;
    };
}

interface IndicatorCategory {
    id: number;
    name: string;
}

interface IndicatorFilters {
    name: string;
    category_id: string;
    status: string;
    measurement_unit: string;
}

interface ModalState {
    isOpen: boolean;
    mode: string;
    data: PerformanceIndicator | null;
}

interface ShowModalState {
    isOpen: boolean;
    data: PerformanceIndicator | null;
}

interface Props {
    indicators: {
        data: PerformanceIndicator[];
        links: any[];
        meta: any;
    };
    categories: IndicatorCategory[];
    auth: any;
}

export default function Index() {
    const { t } = useTranslation();
    const { indicators, auth, categories } = usePage<Props>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), [window.location.search]);

    const [filters, setFilters] = useState<IndicatorFilters>({
        name: urlParams.get('name') || '',
        category_id: urlParams.get('category_id') || '',
        status: urlParams.get('status') || '',
        measurement_unit: urlParams.get('measurement_unit') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState<ModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    
    const [showModalState, setShowModalState] = useState<ShowModalState>({
        isOpen: false,
        data: null
    });


    // Component for indicator action buttons
    const IndicatorActionButtons = ({ indicator }: { indicator: PerformanceIndicator }) => {
        const actionButtons = usePageButtons('indicatorActionButtons', { indicator_id: indicator.id, auth });
        return (
            <>
                {actionButtons.map((button) => (
                    <div key={button.id}>{button.component}</div>
                ))}
            </>
        );
    };



    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'performance.indicators.destroy',
        defaultMessage: t('Are you sure you want to delete this performance indicator?')
    });

    const handleFilter = () => {
        router.get(route('performance.indicators.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('performance.indicators.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', category_id: '', status: '', measurement_unit: '' });
        router.get(route('performance.indicators.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: PerformanceIndicator | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };
    
    const openShowModal = (indicator: PerformanceIndicator) => {
        setShowModalState({ isOpen: true, data: indicator });
    };
    
    const closeShowModal = () => {
        setShowModalState({ isOpen: false, data: null });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'category',
            header: t('Category'),
            sortable: true,
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'measurement_unit',
            header: t('Unit')
        },
        {
            key: 'target_value',
            header: t('Target Value'),
            render: (value: string) => value || '-'
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                    {value === 'active' ? t('Active') : t('Inactive')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-performance-indicators', 'edit-performance-indicators', 'delete-performance-indicators'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, indicator: PerformanceIndicator) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <IndicatorActionButtons indicator={indicator} />
                        {auth.user?.permissions?.includes('view-performance-indicators') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openShowModal(indicator)}
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
                        {auth.user?.permissions?.includes('edit-performance-indicators') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openModal('edit', indicator)}
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
                        {auth.user?.permissions?.includes('delete-performance-indicators') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(indicator.id)}
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
                    { label: t('Performance Indicators') }
                ]}
                pageTitle={t('Manage Performance Indicators')}
                pageActions={
                    <div className="flex gap-2">
                        {auth.user?.permissions?.includes('create-performance-indicators') && (
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
                <Head title={t('Performance Indicators')} />

                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.name}
                                    onChange={(value) => setFilters({ ...filters, name: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search indicators...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <ListGridToggle
                                    currentView={viewMode}
                                    routeName="performance.indicators.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                                <PerPageSelector
                                    routeName="performance.indicators.index"
                                    filters={{ ...filters, view: viewMode }}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.category_id, filters.status, filters.measurement_unit].filter(Boolean).length;
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
                            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Category')}</label>
                                    <Select value={filters.category_id} onValueChange={(value) => setFilters({ ...filters, category_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Categories')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories?.map((category) => (
                                                <SelectItem key={category.id} value={category.id.toString()}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
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
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Measurement Unit')}</label>
                                    <Input
                                        value={filters.measurement_unit}
                                        onChange={(e) => setFilters({ ...filters, measurement_unit: e.target.value })}
                                        placeholder={t('Filter by unit')}
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
                                        data={indicators.data}
                                        columns={tableColumns}
                                        onSort={handleSort}
                                        sortKey={sortField}
                                        sortDirection={sortDirection as 'asc' | 'desc'}
                                        className="rounded-none"
                                        emptyState={
                                            <NoRecordsFound
                                                icon={BarChart3}
                                                title={t('No performance indicators found')}
                                                description={t('Get started by creating your first performance indicator.')}
                                                hasFilters={!!(filters.name || filters.category_id || filters.status || filters.measurement_unit)}
                                                onClearFilters={clearFilters}
                                                createPermission="create-performance-indicators"
                                                onCreateClick={() => openModal('add')}
                                                createButtonText={t('Create Performance Indicator')}
                                                className="h-auto"
                                            />
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-auto max-h-[70vh] p-6">
                                {indicators.data.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                        {indicators.data.map((indicator) => (
                                            <Card key={indicator.id} className="p-0 hover:shadow-lg transition-all duration-200 flex flex-col h-full min-w-0">
                                                {/* Header */}
                                                <div className="p-4 border-b flex-shrink-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-primary/10 rounded-lg">
                                                            <BarChart3 className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-semibold text-sm text-gray-900">{indicator.name}</h3>
                                                            <p className="text-xs text-gray-500 truncate">{indicator.description}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Body */}
                                                <div className="p-4 flex-1 min-h-0">
                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Category')}</p>
                                                            <p className="font-medium text-xs">{indicator.category?.name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Unit')}</p>
                                                            <p className="font-medium text-xs">{indicator.measurement_unit}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Target')}</p>
                                                            <p className="font-medium text-xs">{indicator.target_value || '-'}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Actions Footer */}
                                                <div className="flex justify-between items-center gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                                        indicator.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {indicator.status === 'active' ? t('Active') : t('Inactive')}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            <IndicatorActionButtons indicator={indicator} />
                                                            {auth.user?.permissions?.includes('view-performance-indicators') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openShowModal(indicator)}
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
                                                            {auth.user?.permissions?.includes('edit-performance-indicators') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openModal('edit', indicator)}
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
                                                            {auth.user?.permissions?.includes('delete-performance-indicators') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(indicator.id)}
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
                                        icon={BarChart3}
                                        title={t('No performance indicators found')}
                                        description={t('Get started by creating your first performance indicator.')}
                                        hasFilters={!!(filters.name || filters.category_id || filters.status || filters.measurement_unit)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-performance-indicators"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Performance Indicator')}
                                        className="h-auto"
                                    />
                                )}
                            </div>
                        )}
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={indicators}
                            routeName="performance.indicators.index"
                            filters={{ ...filters, per_page: perPage, view: viewMode }}
                        />
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Performance Indicator')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create 
                            onSuccess={closeModal} 
                            categories={categories}
                        />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditIndicator
                            indicator={modalState.data}
                            onSuccess={closeModal}
                            categories={categories}
                        />
                    )}
                </Dialog>
                
                <Dialog open={showModalState.isOpen} onOpenChange={closeShowModal}>
                    {showModalState.data && (
                        <Show indicator={showModalState.data} />
                    )}
                </Dialog>
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}