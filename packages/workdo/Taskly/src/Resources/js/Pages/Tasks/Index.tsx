import { useState } from 'react';
import React from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { Plus, Package, Edit, Trash2, Eye, Kanban, User } from "lucide-react";
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
import { ProjectTask, ProjectTasksIndexProps, ProjectTaskFilters, ProjectTaskModalState } from './types';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { toast } from 'sonner';
import axios from 'axios';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import EditTask from './Edit';
import ViewTask from './View';
import { formatDate, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const { tasks, project, milestones, teamMembers, taskStages, auth } = usePage<ProjectTasksIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ProjectTaskFilters>({
        title: urlParams.get('title') || '',
        priority: urlParams.get('priority') || '',
        date_range: urlParams.get('date_range') || '',
        user_id: urlParams.get('user_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');

    const [modalState, setModalState] = useState<ProjectTaskModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [deleteState, setDeleteState] = useState({ isOpen: false, taskId: null as number | null });
    const [showFilters, setShowFilters] = useState(false);

    const openDeleteDialog = (taskId: number) => {
        setDeleteState({ isOpen: true, taskId });
    };

    const closeDeleteDialog = () => {
        setDeleteState({ isOpen: false, taskId: null });
    };

    const confirmDelete = async () => {
        if (deleteState.taskId) {
            try {
                await axios.delete(route('project.tasks.destroy', deleteState.taskId));
                router.reload();
                closeDeleteDialog();
                toast.success(t('The task has been deleted successfully.'));
            } catch (error) {
                toast.error(t('Failed to delete task'));
            }
        }
    };

    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Task', settingKey: 'GoogleDrive Task' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Task', settingKey: 'OneDrive Task' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Project Task', settingKey: 'Dropbox Project Task' });

    // Helper function for rendering assigned users
    const renderAssignedUsers = (task: ProjectTask, maxVisible: number = 4) => {
        if (task.assigned_to && typeof task.assigned_to === 'string') {
            const userIds = task.assigned_to.split(',').filter(Boolean);
            const assignedUsers = teamMembers.filter(member => userIds.includes(member.id.toString()));

            if (assignedUsers.length === 0) return '-';

            return (
                <div className="flex items-center gap-1">
                    <div className="flex -space-x-1">
                        {assignedUsers.slice(0, maxVisible).map((user) => (
                            <Avatar key={user.id} className="h-6 w-6 border-2 border-white">
                                <AvatarFallback className="text-xs bg-gray-100">
                                    {user.name?.split(' ').map(n => n[0]).join('') || 'U'}
                                </AvatarFallback>
                            </Avatar>
                        ))}
                        {assignedUsers.length > maxVisible && (
                            <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center">
                                <span className="text-xs text-gray-600">+{assignedUsers.length - maxVisible}</span>
                            </div>
                        )}
                    </div>
                </div>
            );
        }
        if (task.assignedUser) {
            return (
                <div className="flex items-center gap-2">
                    <Avatar className="h-8 w-8">
                        <AvatarFallback className="text-xs bg-gray-100">
                            {task.assignedUser.name?.split(' ').map(n => n[0]).join('') || 'U'}
                        </AvatarFallback>
                    </Avatar>
                    <span className="text-sm">{task.assignedUser.name}</span>
                </div>
            );
        }
        return '-';
    };

    const handleFilter = () => {
        const params = {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode};
        if (project) params.project_id = project.id;
        router.get(route('project.tasks.index'), params, {
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
        router.get(route('project.tasks.index'), params, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ title: '', priority: '', date_range: '', user_id: '' });
        const params = {per_page: perPage, view: viewMode};
        if (project) params.project_id = project.id;
        router.get(route('project.tasks.index'), params);
    };

    const openModal = (mode: 'add' | 'edit' | 'view', data: ProjectTask | null = null) => {
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
            key: 'milestone',
            header: t('Milestone'),
            render: (_: any, task: ProjectTask) => task.milestone?.title || '-'
        },
        {
            key: 'assignedUser',
            header: t('Assigned To'),
            render: (_: any, task: ProjectTask) => {
                const assignedUsers = task.assignedUsers || [];
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
            render: (_: any, task: ProjectTask) => {
                const stage = taskStages.find(s => s.id === (task as any).stage_id);
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
            key: 'duration',
            header: t('Duration'),
            render: (_: any, task: ProjectTask) => {
                if (task.start_date && task.end_date) {
                    return `${formatDate(task.start_date)} - ${formatDate(task.end_date)}`;
                }
                return task.duration || '-';
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
            render: (_: any, task: ProjectTask) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => router.get(route('project.tasks.view', task.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                        {auth.user?.permissions?.includes('edit-project-task') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', task)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-project-task') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(task.id)}
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
            { label: t('Tasks') }
          ]
        : [{ label: t('Tasks') }];

    const pageTitle = project ? `${project.name} - ${t('Tasks')}` : t('Manage Tasks');

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            pageTitle={pageTitle}
            backUrl={project ? route('project.show', project.id) : undefined}
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
                        {project && auth.user?.permissions?.includes('manage-project-task') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm"
                                        onClick={() => router.get(route('project.tasks.kanban', project.id))}
                                    >
                                        <Kanban className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Kanban View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('create-project-task') && (
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
            <Head title={t('Tasks')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({...filters, title: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search tasks...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="project.tasks.index"
                                filters={{...filters, per_page: perPage, ...(project && {project_id: project.id})}}
                            />
                            <PerPageSelector
                                routeName="project.tasks.index"
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
                                            const usersWithTasks = teamMembers.filter(user =>
                                                tasks.data.some(task =>
                                                    task.assignedUsers?.some(assignedUser => assignedUser.id === user.id)
                                                )
                                            );
                                            return usersWithTasks.map((user) => (
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
                                    data={tasks.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Package}
                                            title={t('No tasks found')}
                                            description={t('Get started by creating your first task.')}
                                            hasFilters={!!(filters.title || filters.priority)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-project-task"
                                            onCreateClick={() => auth.user?.permissions?.includes('create-project-task') && openModal('add')}
                                            createButtonText={t('Create Task')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {tasks.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {tasks.data.map((task) => (
                                        <Card key={task.id} className="border border-gray-200">
                                            <div className="p-4">
                                                <div className="flex items-center gap-3 mb-3">
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900">{task.title}</h3>
                                                    </div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2 mb-4">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Milestone')}</p>
                                                        <p className="text-xs text-gray-900">{task.milestone?.title || '-'}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1 text-right">{t('Stage')}</p>
                                                        <div className="text-right">
                                                            {(() => {
                                                                const stage = taskStages.find(s => s.id === (task as any).stage_id);
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
                                                </div>

                                                <div className="grid grid-cols-2 gap-2 mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Assigned To')}</p>
                                                        <div className="text-xs text-gray-900">
                                                            {task.assignedUsers && task.assignedUsers.length > 0 ? (
                                                                <div className="flex -space-x-1">
                                                                    {task.assignedUsers.slice(0, 2).map((user) => (
                                                                        <div key={user.id} className="h-8 w-8 rounded-full border border-background overflow-hidden bg-gray-100 flex items-center justify-center">
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
                                                                    ))}
                                                                    {task.assignedUsers.length > 2 && (
                                                                        <div className="h-5 w-5 rounded-full bg-gray-200 border border-background flex items-center justify-center">
                                                                            <span className="text-xs text-gray-600">+{task.assignedUsers.length - 2}</span>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ) : '-'}
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
                                                                    <span className={`px-2 py-1 rounded-full text-xs ${priorityColors[task.priority as keyof typeof priorityColors]}`}>
                                                                        {t(task.priority)}
                                                                    </span>
                                                                );
                                                            })()}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center justify-between pt-3 border-t">
                                                    <span className="text-xs text-gray-900 text-right">
                                                        {task.start_date && task.end_date ? `${formatDate(task.start_date)} - ${formatDate(task.end_date)}` : task.duration || '-'}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('view-project-task') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('project.tasks.view', task.id))} className="h-8 w-8 p-0 text-green-600">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-project-task') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', task)} className="h-8 w-8 p-0 text-blue-600">
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-project-task') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(task.id)}
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
                                    title={t('No tasks found')}
                                    description={t('Get started by creating your first task.')}
                                    hasFilters={!!(filters.title || filters.priority)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-project-task"
                                    onCreateClick={() => auth.user?.permissions?.includes('create-project-task') && openModal('add')}
                                    createButtonText={t('Create Task')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={tasks}
                        routeName="project.tasks.index"
                        filters={{...filters, per_page: perPage, view: viewMode, ...(project && {project_id: project.id})}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create
                        onSuccess={closeModal}
                        project={project}
                        milestones={milestones}
                        teamMembers={teamMembers}
                    />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditTask
                        onSuccess={() => {
                            closeModal();
                            router.reload();
                        }}
                        task={{ id: modalState.data.id }}
                        project={project}
                        milestones={milestones}
                        teamMembers={teamMembers}
                        taskStages={taskStages}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Task')}
                message={t('Are you sure you want to delete this task?')}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
