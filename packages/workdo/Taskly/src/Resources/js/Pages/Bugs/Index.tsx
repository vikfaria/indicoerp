import { useState } from 'react';
import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { Plus, Package, Edit, Trash2, Eye, Bug, User } from "lucide-react";
import { getImagePath, formatDate } from '@/utils/helpers';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { DatePicker } from '@/components/ui/date-picker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Create from './Create';
import NoRecordsFound from '@/components/no-records-found';
import { ProjectBug, ProjectBugsIndexProps, ProjectBugFilters, ProjectBugModalState } from './types';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { toast } from 'sonner';
import axios from 'axios';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import EditBug from './Edit';
import ViewBug from './View';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const { bugs, project, teamMembers, bugStages, auth } = usePage<ProjectBugsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ProjectBugFilters>({
        title: urlParams.get('title') || '',
        priority: urlParams.get('priority') || '',
        date_range: urlParams.get('date_range') || '',
        user_id: urlParams.get('user_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<ProjectBugModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [deleteState, setDeleteState] = useState({ isOpen: false, bugId: null as number | null });

    const openDeleteDialog = (bugId: number) => {
        setDeleteState({ isOpen: true, bugId });
    };
    const pageButtons = usePageButtons('googleDriveBtn', { module: 'Bug', settingKey: 'GoogleDrive Bug' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Bug', settingKey: 'OneDrive Bug' });
    const closeDeleteDialog = () => {
        setDeleteState({ isOpen: false, bugId: null });
    };

    const confirmDelete = async () => {
        if (deleteState.bugId) {
            try {
                await axios.delete(route('project.bugs.destroy', deleteState.bugId));
                router.reload();
                closeDeleteDialog();
            } catch (error) {
                toast.error(t('Failed to delete bug.'));
            }
        }
    };
    const [showFilters, setShowFilters] = useState(false);

    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Project Bug', settingKey: 'Dropbox Project Bug' });


    // Helper function for rendering assigned users
    const renderAssignedUsers = (bug: ProjectBug, maxVisible: number = 4) => {
        if (bug.assigned_to && typeof bug.assigned_to === 'string') {
            const userIds = bug.assigned_to.split(',').filter(Boolean);
            const assignedUsers = teamMembers.filter(member => userIds.includes(member.id.toString()));

            if (assignedUsers.length === 0) return '-';

            return (
                <div className="flex items-center gap-1">
                    <div className="flex -space-x-1">
                        {assignedUsers.slice(0, maxVisible).map((user) => (
                            <TooltipProvider key={user.id}>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger>
                                        <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden bg-gray-100 flex items-center justify-center">
                                            {user.avatar ? (
                                                <img
                                                    src={getImagePath(user.avatar)}
                                                    alt={user.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <User className="h-3 w-3 text-gray-400" />
                                            )}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{user.name}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        ))}
                        {assignedUsers.length > maxVisible && (
                            <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center">
                                <span className="text-xs text-gray-600">+{assignedUsers.length - maxVisible}</span>
                            </div>
                        )}
                    </div>
                </div>
            );
        }
        if (bug.assignedUser) {
            return (
                <div className="flex items-center gap-2">
                    <div className="h-8 w-8 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                        {bug.assignedUser.avatar ? (
                            <img
                                src={getImagePath(bug.assignedUser.avatar)}
                                alt={bug.assignedUser.name}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <User className="h-3 w-3 text-gray-400" />
                        )}
                    </div>
                    <span className="text-sm">{bug.assignedUser.name}</span>
                </div>
            );
        }
        return '-';
    };

    const handleFilter = () => {
        const params = {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode};
        if (project) params.project_id = project.id;
        router.get(route('project.bugs.index'), params, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        const params = {...filters, per_page: perPage, sort: field, direction, view: viewMode};
        if (project) params.project_id = project.id;
        router.get(route('project.bugs.index'), params, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ title: '', priority: '', date_range: '', user_id: '' });
        const params = {per_page: perPage, view: viewMode};
        if (project) params.project_id = project.id;
        router.get(route('project.bugs.index'), params);
    };

    const openModal = (mode: 'add' | 'edit' | 'view', data: ProjectBug | null = null) => {
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
            key: 'title',
            header: t('Title'),
            sortable: true
        },

        {
            key: 'assignedUser',
            header: t('Assigned To'),
            render: (_: any, bug: ProjectBug) => {
                const assignedUsers = bug.assignedUsers || [];
                const maxVisible = 3;

                if (assignedUsers.length === 0) return '-';

                return (
                    <div className="flex items-center gap-1">
                        <div className="flex -space-x-1">
                            {assignedUsers.slice(0, maxVisible).map((user) => (
                                <TooltipProvider key={user.id}>
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger>
                                            <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden bg-gray-100 flex items-center justify-center">
                                                {user.avatar ? (
                                                    <img
                                                        src={getImagePath(user.avatar)}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <User className="h-3 w-3 text-gray-400" />
                                                )}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{user.name}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            ))}
                            {assignedUsers.length > maxVisible && (
                                <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center">
                                    <span className="text-xs text-gray-600">+{assignedUsers.length - maxVisible}</span>
                                </div>
                            )}
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'stage',
            header: t('Stage'),
            render: (_: any, bug: ProjectBug) => {
                const stage = bugStages.find(s => s.id === (bug as any).stage_id);
                const stageName = stage?.name || '-';
                if (stageName === '-') return stageName;
                return (
                    <span className="px-2 py-1 rounded-full text-sm" style={{ backgroundColor: `${stage?.color || '#e5e7eb'}30`, color: '#374151' }}>
                        {t(stageName)}
                    </span>
                );
            }
        },
        {
            key: 'priority',
            header: t('Priority'),
            sortable: true,
            render: (value: string) => {
                const priorityColors = {
                    'High': 'bg-red-100 text-red-800',
                    'Medium': 'bg-yellow-100 text-yellow-800',
                    'Low': 'bg-green-100 text-green-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${priorityColors[value as keyof typeof priorityColors]}`}>
                        {t(value)}
                    </span>
                );
            }
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, bug: ProjectBug) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('view', bug)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', bug)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(bug.id)}
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
        }
    ];

    const breadcrumbs = project
        ? [
            { label: t('Project'), url: route('project.index') },
            { label: project.name, url: route('project.show', project.id) },
            { label: t('Bug') }
          ]
        : [{ label: t('Bug') }];

    const pageTitle = project ? `${project.name} - ${t('Bug')}` : t('Manage Bug');

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            pageTitle={pageTitle}
            backUrl={project ? route('project.show', project.id) : undefined}
            pageActions={
                <div className="flex gap-2">
                    {pageButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {oneDriveButtons.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    {dropboxBtn.map((button) => (
                        <div key={button.id}>{button.component}</div>
                    ))}
                    <TooltipProvider>
                        {project && auth.user?.permissions?.includes('manage-project-bug') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm"
                                        onClick={() => router.get(route('project.bugs.kanban', project.id))}
                                    >
                                        <Bug className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Kanban View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('create-project-bug') && (
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
                </div>
            }
        >
            <Head title={t('Bug')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({...filters, title: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search bug...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="project.bugs.index"
                                filters={{...filters, per_page: perPage, ...(project && {project_id: project.id})}}
                            />
                            <PerPageSelector
                                routeName="project.bugs.index"
                                filters={{...filters, view: viewMode, ...(project && {project_id: project.id})}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.priority, filters.date_range, filters.user_id].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Priority')}</label>
                                <Select value={filters.priority} onValueChange={(value) => setFilters({...filters, priority: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by priority')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="High">{t('High')}</SelectItem>
                                        <SelectItem value="Medium">{t('Medium')}</SelectItem>
                                        <SelectItem value="Low">{t('Low')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DatePicker
                                    value={filters.date_range}
                                    onChange={(value) => setFilters({...filters, date_range: value})}
                                    placeholder={t('Select date range')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('User')}</label>
                                <Select value={filters.user_id} onValueChange={(value) => setFilters({...filters, user_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by user')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(() => {
                                            const usersWithBugs = teamMembers.filter(user =>
                                                bugs.data.some(bug =>
                                                    bug.assignedUsers?.some(assignedUser => assignedUser.id === user.id)
                                                )
                                            );
                                            return usersWithBugs.map((user) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>{user.name}</SelectItem>
                                            ));
                                        })()}
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
                                    data={bugs.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Package}
                                            title={t('No bug found')}
                                            description={t('Get started by creating your first bug.')}
                                            hasFilters={!!(filters.title || filters.priority || filters.date_range || filters.user_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-project-bug"
                                            onCreateClick={() => auth.user?.permissions?.includes('create-project-bug') && openModal('add')}
                                            createButtonText={t('Create Bug')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {bugs.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {bugs.data.map((bug) => (
                                        <Card key={bug.id} className="border border-gray-200">
                                            <div className="p-4">
                                                <div className="flex items-center gap-3 mb-3">
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900">{bug.title}</h3>
                                                    </div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2 mb-4">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Stage')}</p>
                                                        <div>
                                                            {(() => {
                                                                const stage = bugStages.find(s => s.id === (bug as any).stage_id);
                                                                const stageName = stage?.name || '-';
                                                                if (stageName === '-') return stageName;
                                                                return (
                                                                    <span className="px-2 py-1 rounded-full text-xs" style={{ backgroundColor: `${stage?.color || '#e5e7eb'}30`, color: '#374151' }}>
                                                                        {t(stageName)}
                                                                    </span>
                                                                );
                                                            })()}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1 text-right">{t('Priority')}</p>
                                                        <div className="text-right">
                                                            {(() => {
                                                                const priorityColors = {
                                                                    'High': 'bg-red-100 text-red-800',
                                                                    'Medium': 'bg-yellow-100 text-yellow-800',
                                                                    'Low': 'bg-green-100 text-green-800'
                                                                };
                                                                return (
                                                                    <span className={`px-2 py-1 rounded-full text-xs ${priorityColors[bug.priority as keyof typeof priorityColors]}`}>
                                                                        {t(bug.priority)}
                                                                    </span>
                                                                );
                                                            })()}
                                                        </div>
                                                    </div>
                                                </div>


                                                <div className="flex items-center justify-between pt-3 border-t">
                                                     <div className="text-xs text-gray-900">
                                                            {bug.assignedUsers && bug.assignedUsers.length > 0 ? (
                                                                <div className="flex -space-x-1">
                                                                    {bug.assignedUsers.slice(0, 2).map((user) => (
                                                                        <TooltipProvider key={user.id}>
                                                                            <Tooltip delayDuration={0}>
                                                                                <TooltipTrigger>
                                                                                    <div className="h-8 w-8 rounded-full border border-background overflow-hidden bg-gray-100 flex items-center justify-center">
                                                                                        {user.avatar ? (
                                                                                            <img
                                                                                                src={getImagePath(user.avatar)}
                                                                                                alt={user.name}
                                                                                                className="h-full w-full object-cover"
                                                                                            />
                                                                                        ) : (
                                                                                            <User className="h-2 w-2 text-gray-400" />
                                                                                        )}
                                                                                    </div>
                                                                                </TooltipTrigger>
                                                                                <TooltipContent>
                                                                                    <p>{user.name}</p>
                                                                                </TooltipContent>
                                                                            </Tooltip>
                                                                        </TooltipProvider>
                                                                    ))}
                                                                    {bug.assignedUsers.length > 2 && (
                                                                        <div className="h-8 w-8 rounded-full bg-gray-200 border border-background flex items-center justify-center">
                                                                            <span className="text-xs text-gray-600">+{bug.assignedUsers.length - 2}</span>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ) : '-'}
                                                        </div>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('view-project-bug') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('view', bug)} className="h-8 w-8 p-0 text-green-600">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-project-bug') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', bug)} className="h-8 w-8 p-0 text-blue-600">
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-project-bug') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(bug.id)}
                                                                            className="h-8 w-8 p-0 text-red-600"
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
                                    icon={Package}
                                    title={t('No bug found')}
                                    description={t('Get started by creating your first bug.')}
                                    hasFilters={!!(filters.title || filters.priority || filters.date_range || filters.user_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-project-bug"
                                    onCreateClick={() => auth.user?.permissions?.includes('create-project-bug') && openModal('add')}
                                    createButtonText={t('Create Bug')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={bugs}
                        routeName="project.bugs.index"
                        filters={{...filters, per_page: perPage, view: viewMode, ...(project && {project_id: project.id})}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create
                        onSuccess={closeModal}
                        project={project}
                        teamMembers={teamMembers}
                        bugStages={bugStages}
                    />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditBug
                        onSuccess={() => {
                            closeModal();
                            router.reload();
                        }}
                        bug={{ id: modalState.data.id }}
                        project={project}
                        teamMembers={teamMembers}
                        bugStages={bugStages}
                    />
                )}
                {modalState.mode === 'view' && modalState.data && (
                    <ViewBug
                        bug={{ id: modalState.data.id }}
                        auth={auth}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Bug')}
                message={t('Are you sure you want to delete this bug?')}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
