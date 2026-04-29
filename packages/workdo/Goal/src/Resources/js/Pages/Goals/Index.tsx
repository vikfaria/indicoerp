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
import { Plus, Edit as EditIcon, Trash2, Target, Eye, Flag, CheckCircle } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import Create from './Create';
import EditGoal from './Edit';
import ViewGoal from './View';
import NoRecordsFound from '@/components/no-records-found';
import { Goal, GoalsIndexProps, GoalFilters, GoalModalState } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { goals, categories, auth } = usePage<GoalsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<GoalFilters>({
        goal_name: urlParams.get('goal_name') || '',
        goal_type: urlParams.get('goal_type') || '',
        status: urlParams.get('status') || '',
        priority: urlParams.get('priority') || '',
        category_id: urlParams.get('category_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');

    const [modalState, setModalState] = useState<GoalModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Goal | null>(null);

    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'goal.goals.destroy',
        defaultMessage: t('Are you sure you want to delete this goal?')
    });

    const handleFilter = () => {
        router.get(route('goal.goals.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('goal.goals.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            goal_name: '',
            goal_type: '',
            status: '',
            priority: '',
            category_id: '',
        });
        router.get(route('goal.goals.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: Goal | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const getStatusBadge = (status: string) => {
        const statusColors = {
            draft: 'bg-gray-100 text-gray-800',
            active: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
        };
        return statusColors[status as keyof typeof statusColors] || 'bg-gray-100 text-gray-800';
    };

    const getPriorityBadge = (priority: string) => {
        const priorityColors = {
            low: 'bg-green-100 text-green-800',
            medium: 'bg-yellow-100 text-yellow-800',
            high: 'bg-orange-100 text-orange-800',
            critical: 'bg-red-100 text-red-800'
        };
        return priorityColors[priority as keyof typeof priorityColors] || 'bg-gray-100 text-gray-800';
    };

    const tableColumns = [
        {
            key: 'goal_name',
            header: t('Goal Name'),
            sortable: true
        },
        {
            key: 'category',
            header: t('Category'),
            sortable: false,
            render: (_: any, goal: Goal) => goal.category?.category_name || '-'
        },
        {
            key: 'goal_type',
            header: t('Type'),
            sortable: false,
            render: (value: string) => t(value.replace('_', ' '))
        },
        {
            key: 'target_amount',
            header: t('Target Amount'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'current_amount',
            header: t('Current Amount'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'progress',
            header: t('Progress'),
            sortable: false,
            render: (_: any, goal: Goal) => {
                const progress = goal.target_amount > 0 ? Math.min(Math.round((goal.current_amount / goal.target_amount) * 100), 100) : 0;
                return (
                    <div className="flex items-center gap-2 min-w-24">
                        <div className="flex-1 bg-gray-200 rounded-full h-2">
                            <div
                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                style={{ width: `${progress}%` }}
                            />
                        </div>
                        <span className="text-xs font-medium text-gray-600 min-w-8">{progress}%</span>
                    </div>
                );
            }
        },
        {
            key: 'target_date',
            header: t('Target Date'),
            sortable: false,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'priority',
            header: t('Priority'),
            sortable: false,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'critical' ? 'bg-red-100 text-red-800' :
                    value === 'high' ? 'bg-orange-100 text-orange-800' :
                    value === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-green-100 text-green-800'
                }`}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'completed' ? 'bg-green-100 text-green-800' :
                    value === 'active' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                }`}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-goals', 'edit-goals', 'delete-goals'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, goal: Goal) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {goal.status === 'draft' && auth.user?.permissions?.includes('active-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('goal.goals.active', goal.id))} className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700">
                                        <CheckCircle className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Once the status becomes Active, it cannot be edited')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(goal)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {goal.status === 'draft' && auth.user?.permissions?.includes('edit-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', goal)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(goal.id)}
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
                {label: t('Goals')}
            ]}
            pageTitle={t('Manage Goals')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-goals') && (
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
            <Head title={t('Goals')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.goal_name}
                                onChange={(value) => setFilters({...filters, goal_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Goals...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="goal.goals.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="goal.goals.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.goal_type, filters.status, filters.priority, filters.category_id].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Goal Type')}</label>
                                <Select value={filters.goal_type} onValueChange={(value) => setFilters({...filters, goal_type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="savings">{t('Savings')}</SelectItem>
                                        <SelectItem value="debt_reduction">{t('Debt Reduction')}</SelectItem>
                                        <SelectItem value="expense_reduction">{t('Expense Reduction')}</SelectItem>
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
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="active">{t('Active')}</SelectItem>
                                        <SelectItem value="completed">{t('Completed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Priority')}</label>
                                <Select value={filters.priority} onValueChange={(value) => setFilters({...filters, priority: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Priority')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">{t('Low')}</SelectItem>
                                        <SelectItem value="medium">{t('Medium')}</SelectItem>
                                        <SelectItem value="high">{t('High')}</SelectItem>
                                        <SelectItem value="critical">{t('Critical')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Category')}</label>
                                <Select value={filters.category_id} onValueChange={(value) => setFilters({...filters, category_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.category_name}
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
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[1200px]">
                            <DataTable
                                data={goals?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Target}
                                        title={t('No Goals found')}
                                        description={t('Get started by creating your first Goal.')}
                                        hasFilters={!!(filters.goal_name || filters.goal_type || filters.status || filters.priority || filters.category_id)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-goals"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Goal')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {goals?.data && goals.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {goals.data.map((goal) => (
                                        <Card key={goal.id} className="border border-gray-200 hover:shadow-md transition-shadow">
                                            <div className="p-4">
                                                <div className="flex items-center justify-between mb-3">
                                                    <h3 className="font-semibold text-base text-gray-900 truncate">{goal.goal_name}</h3>
                                                    <span className={`px-2 py-1 rounded-full text-sm ${
                                                        goal.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                        goal.status === 'active' ? 'bg-blue-100 text-blue-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {t(goal.status.charAt(0).toUpperCase() + goal.status.slice(1))}
                                                    </span>
                                                </div>

                                                <div className="space-y-3 mb-4">
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Type')}</p>
                                                            <p className="text-xs text-gray-900">{t(goal.goal_type.replace('_', ' '))}</p>
                                                        </div>
                                                        <div className="text-end">
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Category')}</p>
                                                            <p className="text-xs text-gray-900 truncate">{goal.category?.category_name || '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Priority')}</p>
                                                            <span className={`px-2 py-1 rounded-full text-xs ${
                                                                goal.priority === 'critical' ? 'bg-red-100 text-red-800' :
                                                                goal.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                                                goal.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                                                'bg-green-100 text-green-800'
                                                            }`}>
                                                                {t(goal.priority.charAt(0).toUpperCase() + goal.priority.slice(1))}
                                                            </span>
                                                        </div>
                                                        <div className="text-end">
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Target Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(goal.target_date)}</p>
                                                        </div>
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Target')}</span>
                                                            <span className="text-lg font-bold text-blue-600">{formatCurrency(goal.target_amount)}</span>
                                                        </div>
                                                        <div className="flex justify-between items-center mb-3">
                                                            <span className="text-sm text-gray-600">{t('Current')}</span>
                                                            <span className="text-sm font-semibold text-green-600">{formatCurrency(goal.current_amount)}</span>
                                                        </div>
                                                        <div className="space-y-1">
                                                            <div className="flex justify-between items-center">
                                                                <span className="text-xs text-gray-600">{t('Progress')}</span>
                                                                <span className="text-xs font-medium text-gray-900">
                                                                    {goal.target_amount > 0 ? Math.min(Math.round((goal.current_amount / goal.target_amount) * 100), 100) : 0}%
                                                                </span>
                                                            </div>
                                                            <div className="bg-gray-200 rounded-full h-2">
                                                                <div
                                                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                                    style={{ width: `${goal.target_amount > 0 ? Math.min(Math.round((goal.current_amount / goal.target_amount) * 100), 100) : 0}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div className="flex justify-end pt-3 border-t">
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {goal.status === 'draft' && auth.user?.permissions?.includes('active-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.post(route('goal.goals.active', goal.id))} className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700">
                                                                            <CheckCircle className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Once the status becomes Active, it cannot be edited')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('view-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => setViewingItem(goal)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {goal.status === 'draft' && auth.user?.permissions?.includes('edit-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', goal)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(goal.id)}
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
                                    icon={Target}
                                    title={t('No Goals found')}
                                    description={t('Get started by creating your first Goal.')}
                                    hasFilters={!!(filters.goal_name || filters.goal_type || filters.status || filters.priority || filters.category_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-goals"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Goal')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={goals || { data: [], links: [], meta: {} }}
                        routeName="goal.goals.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditGoal
                        goal={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <ViewGoal goal={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Goal')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
