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
import { Plus, Edit as EditIcon, Trash2, Eye, Users as UsersIcon, Download, FileImage, Tag, MoreVertical, Calendar, Kanban, List, ShoppingCart, Globe, CheckSquare } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import KanbanBoard from '@/components/kanban-board';
import Create from './Create';
import EditLead from './Edit';
import View from './View';
import LabelView from './LabelView';
import NoRecordsFound from '@/components/no-records-found';
import { Lead, LeadsIndexProps, LeadFilters, LeadModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';


export default function Index() {
    const { t } = useTranslation();
    const { leads, auth, users, pipelines, stages, labels, sources, products } = usePage<LeadsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<LeadFilters>({
        name: urlParams.get('name') || '',
        email: urlParams.get('email') || '',
        subject: urlParams.get('subject') || '',
        is_active: urlParams.get('is_active') || '',
        user_id: urlParams.get('user_id') || '',
        pipeline_id: urlParams.get('pipeline_id') || (pipelines?.[0]?.id?.toString() || ''),
        stage_id: urlParams.get('stage_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'kanban'>(urlParams.get('view') as 'list' | 'kanban' || 'list');
    const [modalState, setModalState] = useState<LeadModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Lead | null>(null);
    const [labelingItem, setLabelingItem] = useState<Lead | null>(null);

    const [showFilters, setShowFilters] = useState(false);

    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Lead', settingKey: 'GoogleDrive Lead' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Lead', settingKey: 'OneDrive Lead' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Lead', settingKey: 'Dropbox Lead' });



    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'lead.leads.destroy',
        defaultMessage: t('Are you sure you want to delete this lead?')
    });

    const handleFilter = () => {
        router.get(route('lead.leads.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('lead.leads.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            name: '',
            email: '',
            subject: '',
            is_active: '',
            user_id: '',
            pipeline_id: '',
            stage_id: '',
        });
        router.get(route('lead.leads.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = async (mode: 'add' | 'edit', data: Lead | null = null) => {
        if (mode === 'edit' && data) {
            try {
                const response = await fetch(route('lead.leads.edit', data.id));
                const editData = await response.json();
                setModalState({ isOpen: true, mode, data: editData });
            } catch (error) {
                setModalState({ isOpen: true, mode, data });
            }
        } else {
            setModalState({ isOpen: true, mode, data });
        }
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleMove = (leadId: number, fromStage: string, toStage: string) => {
        router.post(route('lead.leads.order'), {
            lead_id: leadId,
            stage_id: toStage,
            order: [leadId]
        }, {
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ['leads'] });
            }
        });
    };

    const getKanbanData = () => {
        const colors = ['#3b82f6', '#ef4444', '#10b77f', '#f59e0b', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#6366f1'];

        // Filter stages by pipeline if selected
        const filteredStages = filters.pipeline_id && filters.pipeline_id !== ''
            ? stages?.filter(stage => stage.pipeline_id?.toString() === filters.pipeline_id) || []
            : stages || [];

        const columns = filteredStages.map((stage, index) => ({
            id: stage.id.toString(),
            title: stage.name,
            color: colors[index % colors.length]
        }));

        const tasksByStage = {};
        columns.forEach(col => {
            tasksByStage[col.id] = [];
        });

        const filteredLeads = leads?.data?.filter(lead => {
            let isValid = true;

            if (filters.user_id && filters.user_id !== '') {
                isValid = isValid && lead.user_leads?.some(userLead => userLead.user.id.toString() === filters.user_id);
            }

            if (filters.pipeline_id && filters.pipeline_id !== '') {
                isValid = isValid && lead.pipeline_id?.toString() === filters.pipeline_id;
            }

            return isValid;
        }) || [];

        filteredLeads.forEach(lead => {
            const stageId = lead.stage_id?.toString();
            if (stageId && tasksByStage[stageId]) {
                tasksByStage[stageId].push({
                    id: lead.id,
                    title: lead.name,
                    description: lead.subject,
                    status: stageId,
                    due_date: lead.date,
                    assigned_to: lead.user_leads?.[0]?.user || null,
                    priority: null,
                    lead: lead
                });
            }
        });

        return { columns, tasks: tasksByStage };
    };

    const LeadCard = ({ task }: { task: any }) => {
        const lead = task.lead;
        const isOverdue = task.due_date && new Date(task.due_date) < new Date();

        const handleDragStart = (e: React.DragEvent) => {
            e.dataTransfer.setData('application/json', JSON.stringify({ taskId: task.id, fromStatus: task.status }));
            e.dataTransfer.effectAllowed = 'move';
        };

        return (
            <div
                className="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2 hover:shadow-md transition-all cursor-move select-none group"
                draggable={true}
                onDragStart={handleDragStart}
            >
                <div className="flex items-start justify-between mb-2">
                    <h4
                        className="font-medium text-sm text-gray-900 leading-tight pr-2 cursor-pointer hover:text-primary hover:underline"
                        onClick={(e) => {
                            e.stopPropagation();
                            router.get(route('lead.leads.show', lead.id));
                        }}
                    >
                        {task.title}
                    </h4>
                    <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100">
                                    <MoreVertical className="h-3 w-3" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {auth.user?.permissions?.includes('view-leads') && (
                                    <DropdownMenuItem onClick={() => router.get(route('lead.leads.show', lead.id))}>                                        <Eye className="h-3 w-3 mr-2" />
                                        {t('View')}
                                    </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('edit-leads') && (
                                    <DropdownMenuItem onClick={() => openModal('edit', lead)}>
                                        <EditIcon className="h-3 w-3 mr-2" />
                                        {t('Edit')}
                                    </DropdownMenuItem>
                                )}
                                {auth.user?.permissions?.includes('delete-leads') && (
                                    <DropdownMenuItem onClick={() => openDeleteDialog(lead.id)} className="text-red-600">
                                        <Trash2 className="h-3 w-3 mr-2" />
                                        {t('Delete')}
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                </div>

                {task.description && (
                    <p className="text-xs text-gray-600 mb-3 line-clamp-2">{task.description}</p>
                )}

                <div className="flex items-center justify-between mb-3">
                    <Tooltip>
                        <TooltipTrigger>
                            <div className={`flex items-center space-x-1 text-sm font-medium px-2 py-1 rounded ${
                                lead.tasks_count > 0 && lead.complete_tasks_count === lead.tasks_count ? 'text-green-600 bg-green-50' : 'text-gray-600 bg-gray-50'
                            }`}>
                                <CheckSquare className="h-3 w-3" />
                                <span>{lead.complete_tasks_count || 0}/{lead.tasks_count || 0}</span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{t('Tasks')}</p>
                        </TooltipContent>
                    </Tooltip>

                    <div className="flex items-center gap-1">
                        <Tooltip>
                            <TooltipTrigger>
                                <div className="flex items-center space-x-1 text-xs text-blue-600 font-medium bg-blue-50 px-2 py-1 rounded">
                                    <ShoppingCart className="h-3 w-3" />
                                    <span>{lead.products ? lead.products.split(',').filter(id => id.trim()).length : 0}</span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                <div>
                                    <p className="font-medium">{t('Products')}</p>
                                    {(() => {
                                        const productIds = lead.products ? lead.products.split(',').filter(id => id.trim()) : [];
                                        return productIds.length > 0 ? productIds.map((productId: string, index: number) => {
                                            const product = products?.find((p: any) => p.id.toString() === String(productId).trim());
                                            return <p key={index} className="text-sm">{product?.name || `Product ${productId}`}</p>;
                                        }) : '';
                                    })()}
                                </div>
                            </TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger>
                                <div className="flex items-center space-x-1 text-xs text-purple-600 font-medium bg-purple-50 px-2 py-1 rounded">
                                    <Globe className="h-3 w-3" />
                                    <span>{lead.sources ? lead.sources.split(',').filter(id => id.trim()).length : 0}</span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                <div>
                                    <p className="font-medium">{t('Sources')}</p>
                                    {(() => {
                                        const sourceIds = lead.sources ? lead.sources.split(',').filter(id => id.trim()) : [];
                                        return sourceIds.length > 0 ? sourceIds.map((sourceId: string, index: number) => {
                                            const source = sources?.find((s: any) => s.id.toString() === sourceId.trim());
                                            return <p key={index} className="text-sm">{source?.name || `Source ${sourceId}`}</p>;
                                        }) : '';
                                    })()}
                                </div>
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex -space-x-2">
                        <TooltipProvider>
                            {lead.user_leads?.length > 0 ? lead.user_leads.slice(0, 3).map((userLead: any, index: number) => (
                                <Tooltip key={userLead.user.id}>
                                    <TooltipTrigger>
                                        <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden">
                                            {userLead.user.avatar ? (
                                                <img
                                                    src={getImagePath(userLead.user.avatar)}
                                                    alt={userLead.user.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <div className="h-full w-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                                                    {userLead.user.name.charAt(0).toUpperCase()}
                                                </div>
                                            )}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{userLead.user.name}</p>
                                    </TooltipContent>
                                </Tooltip>
                            )) : (
                                <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center text-sm font-medium">
                                    -
                                </div>
                            )}
                            {lead.user_leads?.length > 3 && (
                                <Tooltip>
                                    <TooltipTrigger>
                                        <div className="h-8 w-8 rounded-full bg-gray-100 border-2 border-background flex items-center justify-center text-xs font-medium">
                                            +{lead.user_leads.length - 3}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <div className="space-y-1">
                                            {lead.user_leads.slice(3).map((userLead: any, index: number) => (
                                                <p key={userLead.user.id}>{userLead.user.name}</p>
                                            ))}
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </TooltipProvider>
                    </div>

                    {task.due_date && (
                        <div className={`flex items-center space-x-1 text-xs px-2 py-1 rounded ${isOverdue ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>
                            <Calendar className="h-3 w-3" />
                            <span>{formatDate(task.due_date)}</span>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'subject',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'stage',
            header: t('Stage'),
            sortable: false,
            render: (value: any, row: any) => {
                const stageName = row.stage?.name || stages?.find(item => item.id.toString() === row.stage_id?.toString())?.name;
                return (
                    <Badge variant="secondary">
                        {stageName || 'No Stage'}
                    </Badge>
                );
            }
        },
        {
            key: 'tasks',
            header: t('Tasks'),
            sortable: false,
            render: (value: any, row: any) => {
                const totalTasks = row.tasks_count || 0;
                const completedTasks = row.complete_tasks_count || 0;
                return (
                    <span className={`text-sm font-medium ${
                        totalTasks === 0 ? 'text-gray-400' :
                        completedTasks === totalTasks ? 'text-green-600' : ''
                    }`}>
                        {completedTasks}/{totalTasks}
                    </span>
                );
            }
        },
        {
            key: 'date',
            header: t('Follow Up Date'),
            sortable: false,
            render: (value: string) => {
                if (!value) return '-';
                const isExpired = new Date(value) < new Date();
                return (
                    <span className={isExpired ? 'text-red-600 font-medium' : ''}>
                        {formatDate(value)}
                    </span>
                );
            }
        },
        {
            key: 'users',
            header: t('Users'),
            sortable: false,
            render: (value: any, row: any) => (
                <div className="flex items-center">
                    <TooltipProvider>
                        <div className="flex -space-x-1">
                            {row.user_leads?.length > 0 ? row.user_leads.slice(0, 3).map((userLead: any, index: number) => (
                                <Tooltip key={userLead.user.id}>
                                    <TooltipTrigger>
                                        <Avatar className="h-7 w-7 border-2 border-white">
                                            {userLead.user.avatar ? (
                                                <img
                                                    src={getImagePath(userLead.user.avatar)}
                                                    alt={userLead.user.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <AvatarFallback className="text-xs bg-primary/10">
                                                    {userLead.user.name.charAt(0).toUpperCase()}
                                                </AvatarFallback>
                                            )}
                                        </Avatar>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{userLead.user.name}</p>
                                    </TooltipContent>
                                </Tooltip>
                            )) : (
                                <Avatar className="h-7 w-7 border-2 border-white">
                                    <AvatarFallback className="text-xs bg-gray-200">
                                        <UsersIcon className="h-3 w-3" />
                                    </AvatarFallback>
                                </Avatar>
                            )}
                            {row.user_leads?.length > 3 && (
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Avatar className="h-7 w-7 border-2 border-white">
                                            <AvatarFallback className="text-xs bg-gray-100">
                                                +{row.user_leads.length - 3}
                                            </AvatarFallback>
                                        </Avatar>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <div className="space-y-1">
                                            {row.user_leads.slice(3).map((userLead: any) => (
                                                <p key={userLead.user.id}>{userLead.user.name}</p>
                                            ))}
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </div>
                    </TooltipProvider>
                </div>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-leads','edit-leads', 'delete-leads'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, lead: Lead) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-leads') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('lead.leads.show', lead.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-leads') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setLabelingItem(lead)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Tag className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Label')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-leads') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', lead)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-leads') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(lead.id)}
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
                {label: t('CRM'), url: route('lead.index')},
                {label: t('Leads')}
            ]}
            pageTitle={t('Manage Leads')}
            pageActions={
                <div className="flex items-center gap-2">
                    <TooltipProvider>
                                <Select value={filters.pipeline_id || pipelines?.[0]?.id?.toString() || 'all'} onValueChange={(value) => {
                                    const pipelineId = value === 'all' ? '' : value;
                                    setFilters({...filters, pipeline_id: pipelineId});
                                    router.get(route('lead.leads.index'), {...filters, pipeline_id: pipelineId, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
                                        preserveState: true,
                                        replace: true
                                    });
                                }}>
                                    <SelectTrigger className="w-40">
                                        <SelectValue placeholder={t('Select Pipeline')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {pipelines?.map((pipeline: any) => (
                                            <SelectItem key={pipeline.id} value={pipeline.id.toString()}>
                                                {pipeline.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>



                                {googleDriveButtons.map((button) => (
                                    <div key={button.id}>{button.component}</div>
                                ))}
                                 {oneDriveButtons.map((button) => (
                                    <div key={button.id}>{button.component}</div>
                                ))}
                                {dropboxBtn.map((button) => (
                                    <div key={button.id}>{button.component}</div>
                                ))}
                                {auth.user?.permissions?.includes('view-leads') && (
                                    <>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="outline" size="sm" onClick={() => setViewMode(viewMode === 'kanban' ? 'list' : 'kanban')}>
                                            {viewMode === 'kanban' ? <List className="h-4 w-4" /> : <Kanban className="h-4 w-4" />}
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{viewMode === 'kanban' ? t('List View') : t('Kanban View')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </>
                        )}
                        {auth.user?.permissions?.includes('create-leads') && (
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
            <Head title={t('Leads')} />

            {viewMode === 'kanban' ? (
                (() => {
                    const { columns, tasks } = getKanbanData();
                    return (
                        <KanbanBoard
                            tasks={tasks}
                            columns={columns}
                            onMove={handleMove}
                            taskCard={LeadCard}
                            kanbanActions={null}
                        />
                    );
                })()
            ) : (
                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.name}
                                    onChange={(value) => setFilters({...filters, name: value})}
                                    onSearch={handleFilter}
                                    placeholder={t('Search Leads...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <PerPageSelector
                                    routeName="lead.leads.index"
                                    filters={{...filters, view: viewMode}}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.is_active, filters.user_id, filters.stage_id].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('User')}</label>
                                    <Select value={filters.user_id} onValueChange={(value) => setFilters({...filters, user_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by User')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {users?.map((item: any) => (
                                                <SelectItem key={item.id} value={item.id.toString()}>
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Stage')}</label>
                                    <Select value={filters.stage_id} onValueChange={(value) => setFilters({...filters, stage_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by Stage')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {stages?.map((item: any) => (
                                                <SelectItem key={item.id} value={item.id.toString()}>
                                                    {item.name}
                                                </SelectItem>
                                            ))}
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
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                            <DataTable
                                data={leads?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={UsersIcon}
                                        title={t('No Leads found')}
                                        description={t('Get started by creating your first Lead.')}
                                        hasFilters={!!(filters.name || filters.email || filters.subject || filters.is_active || filters.user_id || filters.pipeline_id || filters.stage_id)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-leads"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Lead')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={leads || { data: [], links: [], meta: {} }}
                            routeName="lead.leads.index"
                            filters={{...filters, per_page: perPage, view: viewMode}}
                        />
                    </CardContent>
                </Card>
            )}

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditLead
                        lead={modalState.data.lead || modalState.data}
                        sources={modalState.data.sources || {}}
                        products={modalState.data.products || {}}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View lead={viewingItem} />}
            </Dialog>

            <Dialog open={!!labelingItem} onOpenChange={() => setLabelingItem(null)}>
                {labelingItem && <LabelView lead={labelingItem} onSuccess={() => setLabelingItem(null)} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Lead')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
