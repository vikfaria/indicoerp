import { useState, useMemo, useCallback } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, Package, Eye, Copy } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { DatePicker } from '@/components/ui/date-picker';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency, getImagePath, formatDate } from '@/utils/helpers';
import Create from './Create';
import EditItem from './Edit';
import DuplicateModal from './DuplicateModal';
import NoRecordsFound from '@/components/no-records-found';

interface ProjectItem {
    id: number;
    name: string;
    description?: string;
    budget?: number;
    start_date?: string;
    end_date?: string;
    status: 'Ongoing' | 'Onhold' | 'Finished';
    team_members?: Array<{
        id: number;
        name: string;
        avatar?: string;
    }>;
    task_count?: number;
    created_at: string;
}

interface ProjectIndexProps {
    items: {
        data: ProjectItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    users: Array<{
        id: number;
        name: string;
    }>;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { items, users, auth } = usePage<ProjectIndexProps>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), []);

    const [filters, setFilters] = useState({
        name: urlParams.get('name') || '',
        status: urlParams.get('status') || '',
        date: urlParams.get('date') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);

    const [modalState, setModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: ProjectItem | null;
    }>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [duplicateModalState, setDuplicateModalState] = useState<{
        isOpen: boolean;
        project: ProjectItem | null;
    }>({
        isOpen: false,
        project: null
    });


    const pageButtons = usePageButtons('projectBtn','Test data');
    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Projects', settingKey: 'GoogleDrive Projects' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Projects', settingKey: 'OneDrive Projects' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Project Projects', settingKey: 'Dropbox Project Projects' });

    const renderTemplateButtons = useCallback((item: ProjectItem) => {
        const TemplateButtonComponent = () => {
            const buttons = usePageButtons('templateBtn', item, undefined, false);
            return buttons?.map((button) => (
                <div key={button.id}>{button.component}</div>
            )) || null;
        };
        return <TemplateButtonComponent />;
    }, []);

    const renderGridTemplateButtons = useCallback((item: ProjectItem) => {
        const buttons = usePageButtons('templateBtn', item, undefined, false);
        return buttons?.map((button) => (
            <div key={button.id}>{button.component}</div>
        )) || null;
    }, []);

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'project.destroy',
        defaultMessage: t('Are you sure you want to delete this project item?')
    });

    const handleFilter = () => {
        router.get(route('project.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('project.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', status: '', date: '' });
        router.get(route('project.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: ProjectItem | null = null) => {
        setModalState({
            isOpen: true,
            mode,
            data
        });
    };

    const closeModal = () => {
        setModalState({
            isOpen: false,
            mode: '',
            data: null
        });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'user',
            header: t('Users'),
            render: (_: any, item: ProjectItem) => {
                const teamMembers = item.team_members || [];
                const maxVisible = 4;

                if (teamMembers.length === 0) return '-';

                return (
                    <div className="flex items-center gap-1">
                        <div className="flex -space-x-1">
                            {teamMembers.slice(0, maxVisible).map((user) => (
                                <TooltipProvider key={user.id}>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger>
                                            <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden">
                                                {user.avatar ? (
                                                    <img
                                                        src={getImagePath(user.avatar)}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <img
                                                        src={getImagePath('avatar.png')}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                )}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{user.name}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            ))}
                            {teamMembers.length > maxVisible && (
                                <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center">
                                    <span className="text-xs text-gray-600">+{teamMembers.length - maxVisible}</span>
                                </div>
                            )}
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'budget',
            header: t('Budget'),
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            render: (value: string) => {
                if (!value) return '-';
                const isOverdue = new Date(value) < new Date();
                return (
                    <span className={isOverdue ? 'text-red-600' : ''}>
                        {formatDate(value)}
                    </span>
                );
            }
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    'Ongoing': 'bg-blue-100 text-blue-800',
                    'Onhold': 'bg-yellow-100 text-yellow-800',
                    'Finished': 'bg-green-100 text-green-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors]}`}>
                        {t(value)}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-project', 'edit-project', 'delete-project', 'duplicate-project'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: ProjectItem) => (
                <div className="flex gap-1">
                    {renderTemplateButtons(item)}
                    {auth.user?.permissions?.includes('duplicate-project') && (
                        <Tooltip key={`duplicate-${item.id}`} delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => setDuplicateModalState({ isOpen: true, project: item })} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Duplicate')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}

                    {auth.user?.permissions?.includes('view-project') && (
                        <Tooltip key={`view-${item.id}`} delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => router.get(route('project.show', item.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                    {auth.user?.permissions?.includes('edit-project') && (
                        <Tooltip key={`edit-${item.id}`} delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', item)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Edit')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                    {auth.user?.permissions?.includes('delete-project') && (
                        <Tooltip key={`delete-${item.id}`} delayDuration={0}>
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
                            <TooltipContent>
                                <p>{t('Delete')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Projects'), url: route('project.dashboard.index') },
            ]}
            pageTitle={t('Manage Projects')}
            pageActions={
                <div className="flex gap-2">
                    {googleDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {oneDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {dropboxBtn.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-project') && (
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
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Project')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search projects...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="project.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="project.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.date].filter(Boolean).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Ongoing">{t('Ongoing')}</SelectItem>
                                        <SelectItem value="Onhold">{t('Onhold')}</SelectItem>
                                        <SelectItem value="Finished">{t('Finished')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DatePicker
                                    value={filters.date}
                                    onChange={(value) => setFilters({...filters, date: value})}
                                    placeholder={t('Select date')}
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
                                    data={items.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Package}
                                            title={t('No projects found')}
                                            description={t('Get started by creating your first project.')}
                                            hasFilters={!!(filters.name || filters.status || filters.date)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-project"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Project')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {items.data.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {items.data.map((project) => (
                                        <Card key={project.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg">
                                                        <Package className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm text-gray-900">{project.name}</h3>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Budget')}</p>
                                                        <p className="font-medium text-xs">{project.budget ? formatCurrency(project.budget) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Tasks')}</p>
                                                        <p className="font-medium text-xs">{project.task_count || 0}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Start Date')}</p>
                                                        <p className="font-medium text-xs">{project.start_date ? formatDate(project.start_date) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('End Date')}</p>
                                                        <p className={`font-medium text-xs ${
                                                            project.end_date && new Date(project.end_date) < new Date() ? 'text-red-600' : 'text-gray-900'
                                                        }`}>{project.end_date ? formatDate(project.end_date) : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="mb-4">
                                                    <p className="text-muted-foreground mb-2 text-xs uppercase tracking-wide">{t('Team Members')}</p>
                                                    <div className="flex items-center gap-1">
                                                        {project.team_members && project.team_members.length > 0 ? (
                                                            <div className="flex -space-x-1">
                                                                {project.team_members.slice(0, 4).map((user) => (
                                                                    <TooltipProvider key={user.id}>
                                                                        <Tooltip delayDuration={0}>
                                                                            <TooltipTrigger>
                                                                                <div className="h-6 w-6 rounded-full border-2 border-background overflow-hidden">
                                                                                    <img
                                                                                        src={user.avatar ? getImagePath(user.avatar) : getImagePath('avatar.png')}
                                                                                        alt={user.name}
                                                                                        className="h-full w-full object-cover"
                                                                                    />
                                                                                </div>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>{user.name}</p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    </TooltipProvider>
                                                                ))}
                                                                {project.team_members.length > 4 && (
                                                                    <div className="h-6 w-6 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center">
                                                                        <span className="text-xs text-gray-600">+{project.team_members.length - 4}</span>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-gray-500">-</span>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="text-xs min-w-0">
                                                    <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                    <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${
                                                        project.status === 'Ongoing' ? 'bg-blue-100 text-blue-800' :
                                                        project.status === 'Onhold' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-green-100 text-green-800'
                                                    }`}>
                                                        {t(project.status)}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                    {renderGridTemplateButtons(project)}
                                                    {auth.user?.permissions?.includes('duplicate-project') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setDuplicateModalState({ isOpen: true, project })} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700">
                                                                    <Copy className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Duplicate')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-project') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => router.get(route('project.show', project.id))} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-project') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', project)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-project') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(project.id)}
                                                                    className="h-9 w-9 p-0 text-red-600 hover:text-red-700"
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
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={Package}
                                    title={t('No projects found')}
                                    description={t('Get started by creating your first project.')}
                                    hasFilters={!!(filters.name || filters.status || filters.date)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-project"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Project')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={items}
                        routeName="project.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} users={users} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditItem
                        item={modalState.data}
                        users={users}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Project')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            <Dialog open={duplicateModalState.isOpen} onOpenChange={() => setDuplicateModalState({ isOpen: false, project: null })}>
                <DuplicateModal
                    isOpen={duplicateModalState.isOpen}
                    project={duplicateModalState.project}
                    onClose={() => setDuplicateModalState({ isOpen: false, project: null })}
                />
            </Dialog>
        </AuthenticatedLayout>
    );
}
