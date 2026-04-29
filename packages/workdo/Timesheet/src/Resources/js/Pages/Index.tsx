import { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { formatDate } from '@/utils/helpers';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, Clock } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Dialog } from '@/components/ui/dialog';
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';
import EditTimesheet from './Edit';

interface Timesheet {
    id: number;
    user: { id: number; name: string };
    project_name?: string;
    task_name?: string;
    date: string;
    hours: number;
    minutes: number;
    type: 'clock_in_out' | 'project' | 'manual';
    formatted_time: string;
}

interface TimesheetIndexProps {
    timesheets: {
        data: Timesheet[];
        links: any;
        meta: any;
    };
    hasHRM: boolean;
    hasTaskly: boolean;
    auth: any;
    users: Array<{ id: number; name: string }>;
    projects: Array<{ id: number; name: string }>;
}

interface TimesheetFilters {
    search: string;
    type: string;
    date: string;
    user_id: string;
}

interface TimesheetModalState {
    isOpen: boolean;
    mode: string;
    data: Timesheet | null;
}

export default function Index() {
    const { t } = useTranslation();
    const { timesheets, hasHRM, hasTaskly, auth, users, projects } = usePage<TimesheetIndexProps>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), [window.location.search]);

    const [filters, setFilters] = useState<TimesheetFilters>({
        search: urlParams.get('search') || '',
        type: urlParams.get('type') || '',
        date: urlParams.get('date') || '',
        user_id: urlParams.get('user_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState<TimesheetModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'timesheet.destroy',
        defaultMessage: t('Are you sure you want to delete this timesheet?')
    });

    const handleFilter = () => {
        router.get(route('timesheet.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        
        router.get(route('timesheet.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', type: '', date: '', user_id: '' });
        router.get(route('timesheet.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Timesheet | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'clock_in_out': return 'bg-blue-100 text-blue-800';
            case 'project': return 'bg-green-100 text-green-800';
            case 'manual': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const tableColumns = [
        {
            key: 'user_id',
            header: t('Name'),
            sortable: true,
            render: (value: any, item: Timesheet) => item.user?.name || '-'
        },
        {
            key: 'project_name',
            header: t('Project'),
            render: (value: string) => value || '-'
        },
        {
            key: 'task_name',
            header: t('Task'),
            render: (value: string) => value || '-'
        },
        {
            key: 'type',
            header: t('Type'),
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(value)}`}>
                    {value === 'clock_in_out' ? t('Clock In/Out') : 
                     value === 'project' ? t('Project') : t('Manual')}
                </span>
            )
        },
        {
            key: 'date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value, usePage().props)
        },
        {
            key: 'hours',
            header: t('Hours'),
            sortable: true,
            render: (_: any, item: Timesheet) => (
                <span className="font-mono">
                    {String(item.hours).padStart(2, '0')}
                </span>
            )
        },
        {
            key: 'minutes',
            header: t('Minutes'),
            sortable: true,
            render: (_: any, item: Timesheet) => (
                <span className="font-mono">
                    {String(item.minutes).padStart(2, '0')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-timesheet', 'delete-timesheet'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: Timesheet) => (
                <div className="flex gap-1">
                    {auth.user?.permissions?.includes('edit-timesheet') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', item)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                        </Tooltip>
                    )}
                    {auth.user?.permissions?.includes('delete-timesheet') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => openDeleteDialog(item.id)}
                                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                        </Tooltip>
                    )}
                </div>
            )
        }] : [])
    ];

    return (
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[{ label: t('Timesheet') }]}
                pageTitle={t('Manage Timesheet')}
                pageActions={
                    <div className="flex gap-2">
                        {auth.user?.permissions?.includes('create-timesheet') && (
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
                <Head title={t('Timesheet')} />

                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.search}
                                    onChange={(value) => setFilters({ ...filters, search: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search timesheets...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <ListGridToggle
                                    currentView={viewMode}
                                    routeName="timesheet.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                                <PerPageSelector
                                    routeName="timesheet.index"
                                    filters={{ ...filters, view: viewMode }}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.type, filters.date, filters.user_id].filter(Boolean).length;
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
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 lg:grid-cols-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Type')}</label>
                                    <Select value={filters.type} onValueChange={(value) => setFilters({ ...filters, type: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Types')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {hasHRM && <SelectItem value="clock_in_out">{t('Clock In/Out')}</SelectItem>}
                                            {hasTaskly && <SelectItem value="project">{t('Project')}</SelectItem>}
                                            <SelectItem value="manual">{t('Manual')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                {(auth.user?.permissions?.includes('manage-any-users') || auth.user?.permissions?.includes('manage-own-users')) && users?.length > 0 && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">{t('User')}</label>
                                        <Select value={filters.user_id} onValueChange={(value) => setFilters({ ...filters, user_id: value })}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('All Users')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {users?.map((user) => (
                                                    <SelectItem key={user.id} value={user.id.toString()}>
                                                        {user.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date')}</label>
                                    <Input
                                        type="date"
                                        value={filters.date}
                                        onChange={(e) => setFilters({ ...filters, date: e.target.value })}
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
                                        data={timesheets?.data || []}
                                        columns={tableColumns}
                                        onSort={handleSort}
                                        sortKey={sortField}
                                        sortDirection={sortDirection as 'asc' | 'desc'}
                                        className="rounded-none"
                                        emptyState={
                                            <NoRecordsFound
                                                icon={Clock}
                                                title={t('No timesheets found')}
                                                description={t('Get started by creating your first timesheet.')}
                                                hasFilters={!!(filters.search || filters.type || filters.date || filters.user_id)}
                                                onClearFilters={clearFilters}
                                                createPermission="create-timesheet"
                                                onCreateClick={() => openModal('add')}
                                                createButtonText={t('Create Timesheet')}
                                                className="h-auto"
                                            />
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-auto max-h-[70vh] p-6">
                                {timesheets?.data?.length > 0 ? (
                                    <div className="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-4">
                                        {timesheets.data.map((timesheet) => (
                                            <Card key={timesheet.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                                {/* Header */}
                                                <div className="p-4 bg-gradient-to-r from-gray-50 to-transparent border-b flex-shrink-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-primary/10 rounded-lg">
                                                            <Clock className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-semibold text-sm text-gray-900">{timesheet.user.name}</h3>
                                                            <p className="text-xs font-medium text-gray-600">{formatDate(timesheet.date, usePage().props)}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Body */}
                                                <div className="p-4 flex-1 min-h-0">
                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Hours')}</p>
                                                            <p className="font-medium text-xs font-mono">{String(timesheet.hours).padStart(2, '0')}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Minutes')}</p>
                                                            <p className="font-medium text-xs font-mono">{String(timesheet.minutes).padStart(2, '0')}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Project')}</p>
                                                            <p className="font-medium text-xs">{timesheet.project_name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Task')}</p>
                                                            <p className="font-medium text-xs">{timesheet.task_name || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-1 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Type')}</p>
                                                            <p className="font-medium text-xs">
                                                                {timesheet.type === 'clock_in_out' ? t('Clock In/Out') : 
                                                                 timesheet.type === 'project' ? t('Project') : t('Manual')}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex justify-between items-center p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(timesheet.type)}`}>
                                                        {timesheet.type === 'clock_in_out' ? t('Clock In/Out') : 
                                                         timesheet.type === 'project' ? t('Project') : t('Manual')}
                                                    </span>
                                                    <div className="flex gap-2">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('edit-timesheet') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', timesheet)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-timesheet') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(timesheet.id)}
                                                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
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
                                        icon={Clock}
                                        title={t('No timesheets found')}
                                        description={t('Get started by creating your first timesheet.')}
                                        hasFilters={!!(filters.search || filters.type || filters.date || filters.user_id)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-timesheet"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Timesheet')}
                                        className="h-auto"
                                    />
                                )}
                            </div>
                        )}
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={timesheets}
                            routeName="timesheet.index"
                            filters={{ ...filters, per_page: perPage, view: viewMode }}
                        />
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Timesheet')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />

                {/* Timesheet Modal */}
                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create 
                            onSuccess={closeModal} 
                            users={users || []} 
                            projects={projects || []}
                            hasHRM={hasHRM}
                            hasTaskly={hasTaskly}
                        />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditTimesheet
                            timesheet={modalState.data}
                            onSuccess={closeModal}
                            users={users || []}
                            projects={projects || []}
                            hasHRM={hasHRM}
                            hasTaskly={hasTaskly}
                        />
                    )}
                </Dialog>
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}