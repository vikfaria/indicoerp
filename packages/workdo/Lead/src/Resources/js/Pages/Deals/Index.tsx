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
import { Plus, Edit as EditIcon, Trash2, Eye, DollarSign as DollarSignIcon, Download, FileImage, Tag, MoreVertical, Calendar, Kanban, List, ShoppingCart, Globe, CheckSquare } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import KanbanBoard from '@/components/kanban-board';
import Create from './Create';
import EditDeal from './Edit';
import View from './View';
import LabelView from './LabelView';
import NoRecordsFound from '@/components/no-records-found';
import { Deal, DealsIndexProps, DealFilters, DealModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';


export default function Index() {
    const { t } = useTranslation();
    const { deals, auth, pipelines, stages, groups, users, sources, products, permissions } = usePage<DealsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<DealFilters>({
        name: urlParams.get('name') || '',
        notes: urlParams.get('notes') || '',
        pipeline_id: urlParams.get('pipeline_id') || (pipelines?.[0]?.id?.toString() || ''),
        stage_id: urlParams.get('stage_id') || '',
        status: urlParams.get('status') || '',
        is_active: urlParams.get('is_active') || '',

    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'kanban'>(urlParams.get('view') as 'list' | 'kanban' || 'list');
    const [modalState, setModalState] = useState<DealModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Deal | null>(null);
    const [labelingItem, setLabelingItem] = useState<Deal | null>(null);

    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'lead.deals.destroy',
        defaultMessage: t('Are you sure you want to delete this deal?')
    });

    const handleFilter = () => {
        router.get(route('lead.deals.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('lead.deals.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };
    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Deal', settingKey: 'GoogleDrive Deal' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Deal', settingKey: 'OneDrive Deal' });
    const dropboxButtons = usePageButtons('dropboxBtn', { module: 'Deal', settingKey: 'Dropbox Deal' });
    const hubspotButtons = usePageButtons('hubspotBtn', { module: 'Deal', settingKey: 'HubSpot Deal' });

    const clearFilters = () => {
        setFilters({
            name: '',
            notes: '',
            pipeline_id: '',
            stage_id: '',
            status: '',
            is_active: '',

        });
        router.get(route('lead.deals.index'), {per_page: perPage, view: viewMode});
    };


    const openModal = (mode: 'add' | 'edit', data: Deal | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleMove = (dealId: number, fromStage: string, toStage: string) => {
        router.post(route('lead.deals.order'), {
            deal_id: dealId,
            stage_id: toStage,
            order: [dealId]
        }, {
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ['deals'] });
            }
        });
    };

    const getKanbanData = () => {
        const colors = ['#3b82f6', '#ef4444', '#10b77f', '#f59e0b', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#6366f1'];

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

        const filteredDeals = deals?.data?.filter(deal => {
            let isValid = true;



            if (filters.pipeline_id && filters.pipeline_id !== '') {
                isValid = isValid && deal.pipeline_id?.toString() === filters.pipeline_id;
            }

            return isValid;
        }) || [];

        filteredDeals.forEach(deal => {
            const stageId = deal.stage_id?.toString();
            if (stageId && tasksByStage[stageId]) {
                tasksByStage[stageId].push({
                    id: deal.id,
                    title: deal.name,
                    description: deal.phone,
                    status: stageId,
                    due_date: null,
                    assigned_to: deal.user_deals || [],
                    priority: null,
                    deal: deal
                });
            }
        });

        return { columns, tasks: tasksByStage };
    };

    const DealCard = ({ task }: { task: any }) => {
        const deal = task.deal;

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
                            router.get(route('lead.deals.show', deal.id));
                        }}
                    >
                        {task.title}
                    </h4>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100">
                                <MoreVertical className="h-3 w-3" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {auth.user?.permissions?.includes('view-deals') && (
                                <DropdownMenuItem onClick={() => router.get(route('lead.deals.show', deal.id))}>
                                    <Eye className="h-3 w-3 mr-2" />
                                    {t('View')}
                                </DropdownMenuItem>
                            )}
                            {auth.user?.permissions?.includes('edit-deals') && (
                                <DropdownMenuItem onClick={() => openModal('edit', deal)}>
                                    <EditIcon className="h-3 w-3 mr-2" />
                                    {t('Edit')}
                                </DropdownMenuItem>
                            )}
                            {auth.user?.permissions?.includes('delete-deals') && (
                                <DropdownMenuItem onClick={() => openDeleteDialog(deal.id)} className="text-red-600">
                                    <Trash2 className="h-3 w-3 mr-2" />
                                    {t('Delete')}
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>



                <div className="flex items-center justify-between mb-3">
                    <Tooltip>
                        <TooltipTrigger>
                            <div className={`flex items-center space-x-1 text-sm font-medium px-2 py-1 rounded ${
                                deal.tasks_count > 0 && deal.complete_tasks_count === deal.tasks_count ? 'text-green-600 bg-green-50' : 'text-gray-600 bg-gray-50'
                            }`}>
                                <CheckSquare className="h-3 w-3" />
                                <span>{deal.complete_tasks_count || 0}/{deal.tasks_count || 0}</span>
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
                                    <span>{deal.products ? (Array.isArray(deal.products) ? deal.products.length : deal.products.split(',').filter(Boolean).length) : 0}</span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                <div>
                                    <p className="font-medium">{t('Products')}</p>
                                    {deal.products && (Array.isArray(deal.products) ? deal.products : deal.products.split(',')).filter(Boolean).length > 0 ? (
                                        (Array.isArray(deal.products) ? deal.products : deal.products.split(',')).filter(Boolean).map((productId: string, index: number) => {
                                            const product = products?.find((p: any) => p.id.toString() === String(productId).trim());
                                            return <p key={index} className="text-sm">{product?.name || `Product ${productId}`}</p>;
                                        })
                                    ) : ''}
                                </div>
                            </TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger>
                                <div className="flex items-center space-x-1 text-xs text-purple-600 font-medium bg-purple-50 px-2 py-1 rounded">
                                    <Globe className="h-3 w-3" />
                                    <span>{deal.sources ? (Array.isArray(deal.sources) ? deal.sources.length : deal.sources.split(',').filter(Boolean).length) : 0}</span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                <div>
                                    <p className="font-medium">{t('Sources')}</p>
                                    {deal.sources && (Array.isArray(deal.sources) ? deal.sources : deal.sources.split(',')).filter(Boolean).length > 0 ? (
                                        (Array.isArray(deal.sources) ? deal.sources : deal.sources.split(',')).filter(Boolean).map((sourceId: string, index: number) => {
                                            const source = sources?.find((s: any) => s.id.toString() === String(sourceId).trim());
                                            return <p key={index} className="text-sm">{source?.name || `Source ${sourceId}`}</p>;
                                        })
                                    ) : ''}
                                </div>
                            </TooltipContent>
                        </Tooltip>


                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex -space-x-2">
                        <TooltipProvider>
                            {(() => {
                                if (!deal.users || deal.users.length === 0) {
                                    return (
                                        <div className="h-8 w-8 rounded-full bg-gray-200 border-2 border-background flex items-center justify-center text-sm font-medium">
                                            -
                                        </div>
                                    );
                                }

                                return deal.users.slice(0, 3).map((user: any, index: number) => (
                                    <Tooltip key={user.id}>
                                        <TooltipTrigger>
                                            <div className="h-8 w-8 rounded-full border-2 border-background overflow-hidden">
                                                {user.avatar ? (
                                                    <img
                                                        src={getImagePath(user.avatar)}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="h-full w-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                )}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{user.name}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                ));
                            })()}
                            {deal.users?.length > 3 && (
                                <Tooltip>
                                    <TooltipTrigger>
                                        <div className="h-8 w-8 rounded-full bg-gray-100 border-2 border-background flex items-center justify-center text-xs font-medium">
                                            +{deal.users.length - 3}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <div className="space-y-1">
                                            {deal.users.slice(3).map((user: any, index: number) => (
                                                <p key={user.id}>{user.name}</p>
                                            ))}
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </TooltipProvider>
                    </div>

                    {deal.price && (
                        <div className="flex items-center space-x-1 text-xs text-green-600 font-medium">
                            <DollarSignIcon className="h-3 w-3" />
                            <span>{formatCurrency(deal.price)}</span>
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
            key: 'price',
            header: t('Price'),
            sortable: false,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'pipeline.name',
            header: t('Pipeline Name'),
            sortable: false,
            render: (value: any, row: any) => row.pipeline?.name || '-'
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

        ...(auth.user?.permissions?.some((p: string) => ['view-deals','edit-deals', 'delete-deals'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, deal: Deal) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-deals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('lead.deals.show', deal.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-deals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setLabelingItem(deal)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Tag className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Label')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-deals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', deal)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-deals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(deal.id)}
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
                {label: t('Deals')}
            ]}
            pageTitle={t('Manage Deals')}
            pageActions={
                <div className="flex items-center gap-2">
                    <TooltipProvider>
                                <Select value={filters.pipeline_id || pipelines?.[0]?.id?.toString() || 'all'} onValueChange={(value) => {
                                    const pipelineId = value === 'all' ? '' : value;
                                    setFilters({...filters, pipeline_id: pipelineId});
                                    router.get(route('lead.deals.index'), {...filters, pipeline_id: pipelineId, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
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
                                {dropboxButtons.map((button) => (
                                    <div key={button.id}>{button.component}</div>
                                ))}
                                {hubspotButtons.map((button) => (
                                    <div key={button.id}>{button.component}</div>
                                ))}
                                {auth.user?.permissions?.includes('view-deals') && (
                                    <>

                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button size="sm" onClick={() => setViewMode(viewMode === 'kanban' ? 'list' : 'kanban')}>
                                            {viewMode === 'kanban' ? <List className="h-4 w-4" /> : <Kanban className="h-4 w-4" />}
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{viewMode === 'kanban' ? t('List View') : t('Kanban View')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </>
                        )}
                        {auth.user?.permissions?.includes('create-deals') && (
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
            <Head title={t('Deals')} />

            {viewMode === 'kanban' ? (
                (() => {
                    const { columns, tasks } = getKanbanData();
                    return (
                        <KanbanBoard
                            tasks={tasks}
                            columns={columns}
                            onMove={handleMove}
                            taskCard={DealCard}
                            kanbanActions={null}
                        />
                    );
                })()
            ) : (
                <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Deals...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">

                            <PerPageSelector
                                routeName="lead.deals.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.stage_id, filters.status].filter(f => f !== '' && f !== null && f !== undefined).length;
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

                {/* Advanced Filters */}
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

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
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Won">{t('Won')}</SelectItem>
                                        <SelectItem value="Loss">{t('Loss')}</SelectItem>
                                        <SelectItem value="Active">{t('Active')}</SelectItem>
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

                {/* Table Content */}
                <CardContent className="p-0">
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                            <DataTable
                                data={deals?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={DollarSignIcon}
                                        title={t('No Deals found')}
                                        description={t('Get started by creating your first Deal.')}
                                        hasFilters={!!(filters.name || filters.notes || filters.pipeline_id || filters.stage_id || filters.status || filters.is_active)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-deals"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Deal')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={deals || { data: [], links: [], meta: {} }}
                        routeName="lead.deals.index"
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
                    <EditDeal
                        deal={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <Dialog open={!!labelingItem} onOpenChange={() => setLabelingItem(null)}>
                {labelingItem && <LabelView deal={labelingItem} onSuccess={() => setLabelingItem(null)} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Deal')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
