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
import { Plus, Edit, Trash2, Eye, Target } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog } from "@/components/ui/dialog";
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';
import EditGoal from './Edit';
import Show from './Show';

interface EmployeeGoal {
    id: number;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    target: string;
    progress: number;
    status: string;
    created_at: string;
    employee?: {
        id: number;
        name: string;
    };
    goal_type?: {
        id: number;
        name: string;
    };
}

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface GoalType {
    id: number;
    name: string;
}

interface Props {
    goals: {
        data: EmployeeGoal[];
        links: any[];
        meta: any;
    };
    employees: Employee[];
    goalTypes: GoalType[];
    auth: any;
}

interface GoalFilters {
    title: string;
    employee_id: string;
    goal_type_id: string;
    status: string;
}

interface ModalState {
    isOpen: boolean;
    mode: string;
    data: EmployeeGoal | null;
}

interface ShowModalState {
    isOpen: boolean;
    data: EmployeeGoal | null;
}

export default function Index() {
    const { t } = useTranslation();
    const { goals, auth, employees, goalTypes } = usePage<Props>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), [window.location.search]);

    const [filters, setFilters] = useState<GoalFilters>({
        title: urlParams.get('title') || '',
        employee_id: urlParams.get('employee_id') || '',
        goal_type_id: urlParams.get('goal_type_id') || '',
        status: urlParams.get('status') || ''
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


    // Component for goal action buttons
    const GoalActionButtons = ({ goal }: { goal: EmployeeGoal }) => {
        const actionButtons = usePageButtons('goalActionButtons', { goal_id: goal.id, auth });
        return (
            <>
                {actionButtons.map((button) => (
                    <div key={button.id}>{button.component}</div>
                ))}
            </>
        );
    };

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'performance.employee-goals.destroy',
        defaultMessage: t('Are you sure you want to delete this employee goal?')
    });

    const handleFilter = () => {
        router.get(route('performance.employee-goals.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('performance.employee-goals.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ title: '', employee_id: '', goal_type_id: '', status: '' });
        router.get(route('performance.employee-goals.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: EmployeeGoal | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };
    
    const openShowModal = (goal: EmployeeGoal) => {
        setShowModalState({ isOpen: true, data: goal });
    };
    
    const closeShowModal = () => {
        setShowModalState({ isOpen: false, data: null });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'bg-green-100 text-green-700';
            case 'in_progress': return 'bg-blue-100 text-blue-700';
            case 'not_started': return 'bg-gray-100 text-gray-700';
            case 'overdue': return 'bg-red-100 text-red-700';
            default: return 'bg-gray-100 text-gray-700';
        }
    };

    const getStatusLabel = (status: string) => {
        const labels: { [key: string]: string } = {
            completed: t('Completed'),
            in_progress: t('In Progress'),
            not_started: t('Not Started'),
            overdue: t('Overdue')
        };
        return labels[status] || status;
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Title'),
            sortable: true
        },
        {
            key: 'employee',
            header: t('Employee'),
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'goal_type',
            header: t('Goal Type'),
            sortable: true,
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'target',
            header: t('Target')
        },
        {
            key: 'progress',
            header: t('Progress'),
            render: (value: number) => {
                if (!value) return '-';
                return (
                    <div className="flex items-center gap-2 min-w-24">
                        <div className="flex-1 bg-gray-200 rounded-full h-2">
                            <div
                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                style={{ width: `${value}%` }}
                            />
                        </div>
                        <span className="text-xs font-medium text-gray-600 min-w-8">{value}%</span>
                    </div>
                );
            }
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'end_date',
            header: t('End Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(value)}`}>
                    {getStatusLabel(value)}
                </span>
            )
        },

        ...(auth.user?.permissions?.some((p: string) => ['view-employee-goals', 'edit-employee-goals', 'delete-employee-goals'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, goal: EmployeeGoal) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <GoalActionButtons goal={goal} />
                        {auth.user?.permissions?.includes('view-employee-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openShowModal(goal)}
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
                        {auth.user?.permissions?.includes('edit-employee-goals') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openModal('edit', goal)}
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
                        {auth.user?.permissions?.includes('delete-employee-goals') && (
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
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    { label: t('Performance') },
                    { label: t('Employee Goals') }
                ]}
                pageTitle={t('Manage Employee Goals')}
                pageActions={
                    <div className="flex gap-2">
                        {auth.user?.permissions?.includes('create-employee-goals') && (
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
                <Head title={t('Employee Goals')} />

                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.title}
                                    onChange={(value) => setFilters({ ...filters, title: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search goals...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <ListGridToggle
                                    currentView={viewMode}
                                    routeName="performance.employee-goals.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                                <PerPageSelector
                                    routeName="performance.employee-goals.index"
                                    filters={{ ...filters, view: viewMode }}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.employee_id, filters.goal_type_id, filters.status].filter(Boolean).length;
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
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                    <Select value={filters.employee_id} onValueChange={(value) => setFilters({ ...filters, employee_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Employees')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {employees?.map((employee) => (
                                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                                    {employee.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Goal Type')}</label>
                                    <Select value={filters.goal_type_id} onValueChange={(value) => setFilters({ ...filters, goal_type_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Goal Types')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {goalTypes?.map((type) => (
                                                <SelectItem key={type.id} value={type.id.toString()}>
                                                    {type.name}
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
                                            <SelectItem value="not_started">{t('Not Started')}</SelectItem>
                                            <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                            <SelectItem value="completed">{t('Completed')}</SelectItem>
                                            <SelectItem value="overdue">{t('Overdue')}</SelectItem>
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
                                        data={goals.data}
                                        columns={tableColumns}
                                        onSort={handleSort}
                                        sortKey={sortField}
                                        sortDirection={sortDirection as 'asc' | 'desc'}
                                        className="rounded-none"
                                        emptyState={
                                            <NoRecordsFound
                                                icon={Target}
                                                title={t('No employee goals found')}
                                                description={t('Get started by creating your first employee goal.')}
                                                hasFilters={!!(filters.title || filters.employee_id || filters.goal_type_id || filters.status)}
                                                onClearFilters={clearFilters}
                                                createPermission="create-employee-goals"
                                                onCreateClick={() => openModal('add')}
                                                createButtonText={t('Create Employee Goal')}
                                                className="h-auto"
                                            />
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-auto max-h-[70vh] p-6">
                                {goals.data.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                        {goals.data.map((goal) => (
                                            <Card key={goal.id} className="p-0 hover:shadow-lg transition-all duration-200 flex flex-col h-full min-w-0">
                                                {/* Header */}
                                                <div className="p-4 border-b flex-shrink-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-primary/10 rounded-lg">
                                                            <Target className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-semibold text-sm text-gray-900">{goal.title}</h3>
                                                            <p className="text-xs text-gray-500 truncate">{goal.description}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Body */}
                                                <div className="p-4 flex-1 min-h-0">
                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Employee')}</p>
                                                            <p className="font-medium text-xs">{goal.employee?.name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Type')}</p>
                                                            <p className="font-medium text-xs">{goal.goal_type?.name || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Target')}</p>
                                                            <p className="font-medium text-xs">{goal.target}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Due Date')}</p>
                                                            <p className="font-medium text-xs">{formatDate(goal.end_date)}</p>
                                                        </div>
                                                    </div>

                                                    <div className="mb-4">
                                                        <div className="flex items-center justify-between mb-1">
                                                            <p className="text-muted-foreground text-xs uppercase tracking-wide">{t('Progress')}</p>
                                                            <span className="text-xs font-medium text-gray-600">{goal.progress || 0}%</span>
                                                        </div>
                                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                                style={{ width: `${goal.progress || 0}%` }}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Actions Footer */}
                                                <div className="flex justify-between items-center gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(goal.status)}`}>
                                                        {getStatusLabel(goal.status)}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            <GoalActionButtons goal={goal} />
                                                            {auth.user?.permissions?.includes('view-employee-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openShowModal(goal)}
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
                                                            {auth.user?.permissions?.includes('edit-employee-goals') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openModal('edit', goal)}
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
                                                            {auth.user?.permissions?.includes('delete-employee-goals') && (
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
                                            </Card>
                                        ))}
                                    </div>
                                ) : (
                                    <NoRecordsFound
                                        icon={Target}
                                        title={t('No employee goals found')}
                                        description={t('Get started by creating your first employee goal.')}
                                        hasFilters={!!(filters.title || filters.employee_id || filters.goal_type_id || filters.status)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-employee-goals"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Employee Goal')}
                                        className="h-auto"
                                    />
                                )}
                            </div>
                        )}
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={goals}
                            routeName="performance.employee-goals.index"
                            filters={{ ...filters, per_page: perPage, view: viewMode }}
                        />
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Employee Goal')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create 
                            onSuccess={closeModal} 
                            employees={employees}
                            goalTypes={goalTypes}
                        />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditGoal
                            goal={modalState.data}
                            onSuccess={closeModal}
                            employees={employees}
                            goalTypes={goalTypes}
                        />
                    )}
                </Dialog>
                
                <Dialog open={showModalState.isOpen} onOpenChange={closeShowModal}>
                    {showModalState.data && (
                        <Show goal={showModalState.data} />
                    )}
                </Dialog>
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}