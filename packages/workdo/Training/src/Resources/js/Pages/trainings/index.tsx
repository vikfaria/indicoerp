import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, GraduationCap, CheckSquare } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import Create from './create';
import EditTraining from './edit';
import NoRecordsFound from '@/components/no-records-found';
import { formatDate } from '@/utils/helpers';
import { Training, TrainingsIndexProps, TrainingFilters, TrainingModalState } from './types';

export default function Index() {
    const { t } = useTranslation();
    const { trainings, trainingTypes, trainers, branches, departments, users, auth } = usePage<TrainingsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<TrainingFilters>({
        title: urlParams.get('title') || '',
        status: urlParams.get('status') || '',
        branch_id: urlParams.get('branch_id') || '',
        department_id: urlParams.get('department_id') || ''
    });

    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');

    const [modalState, setModalState] = useState<TrainingModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'training.trainings.destroy',
        defaultMessage: t('Are you sure you want to delete this training list?')
    });

    const handleFilter = () => {
        router.get(route('training.trainings.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('training.trainings.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ title: '', status: '', branch_id: '', department_id: '' });
        router.get(route('training.trainings.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: Training | null = null) => {
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

    useEffect(() => {
        if (filters.branch_id) {
            const branchDepartments = departments.filter(dept => dept.branch_id.toString() === filters.branch_id);
            setFilteredDepartments(branchDepartments);
            if (filters.department_id && !branchDepartments.find(dept => dept.id.toString() === filters.department_id)) {
                setFilters(prev => ({...prev, department_id: ''}));
            }
        } else {
            setFilteredDepartments([]);
            setFilters(prev => ({...prev, department_id: ''}));
        }
    }, [filters.branch_id]);

    const tableColumns = [
        {
            key: 'title',
            header: t('Title'),
            sortable: true
        },
        {
            key: 'trainingType',
            header: t('Training Type'),
            render: (value: any, training: Training) => {
                return training.trainingType?.name || 
                       trainingTypes.find(type => type.id === training.training_type_id)?.name || 
                       '-';
            }
        },
        {
            key: 'trainer',
            header: t('Trainer'),
            render: (value: any, training: Training) => training.trainer?.name || '-'
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
            render: (value: string) => {
                const statusColors = {
                    scheduled: 'bg-blue-100 text-blue-800',
                    ongoing: 'bg-yellow-100 text-yellow-800',
                    completed: 'bg-green-100 text-green-800',
                    cancelled: 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors]}`}>
                        {t(value.charAt(0).toUpperCase() + value.slice(1))}
                    </span>
                );
            }
        },
        {
            key: 'branch',
            header: t('Branch'),
            render: (value: any, training: Training) => training.branch?.branch_name || '-'
        },
        {
            key: 'location',
            header: t('Location'),
            render: (value: string) => value || '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-training-tasks','edit-trainings', 'delete-trainings'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, training: Training) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-training-tasks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        onClick={() => router.visit(route('training.trainings.tasks.index', training.id))} 
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <CheckSquare className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Tasks')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-trainings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', training)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-trainings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(training.id)}
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
                {label: t('Training')}, 
                {label: t('Training List')}
            ]}
            pageTitle={t('Manage Training List')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-trainings') && (
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
            <Head title={t('Training List')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({...filters, title: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search trainings...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="training.trainings.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="training.trainings.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.branch_id, filters.department_id].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="scheduled">{t('Scheduled')}</SelectItem>
                                        <SelectItem value="ongoing">{t('Ongoing')}</SelectItem>
                                        <SelectItem value="completed">{t('Completed')}</SelectItem>
                                        <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Branch')}</label>
                                <Select value={filters.branch_id} onValueChange={(value) => setFilters({...filters, branch_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by branch')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {branches.map((branch) => (
                                            <SelectItem key={branch.id} value={branch.id.toString()}>{branch.branch_name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Department')}</label>
                                <Select 
                                    value={filters.department_id} 
                                    onValueChange={(value) => setFilters({...filters, department_id: value})}
                                    disabled={!filters.branch_id}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={filters.branch_id ? t('Filter by department') : t('Select branch first')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredDepartments.map((department) => (
                                            <SelectItem key={department.id} value={department.id.toString()}>{department.department_name}</SelectItem>
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
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={trainings.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={GraduationCap}
                                            title={t('No training list found')}
                                            description={t('Get started by creating your first training list.')}
                                            hasFilters={!!(filters.title || filters.status || filters.branch_id || filters.department_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-trainings"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Training List')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {trainings.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {trainings.data.map((training) => (
                                        <Card key={training.id} className="border border-gray-200">
                                            <div className="p-4">
                                                <div className="flex items-center gap-3 mb-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                                        <GraduationCap className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900">{training.title}</h3>
                                                    </div>
                                                </div>

                                                <div className="space-y-3 mb-3">
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-2">{t('Training Type')}</p>
                                                            <p className="text-xs text-gray-900 truncate" title={training.trainingType?.name || trainingTypes.find(type => type.id === training.training_type_id)?.name}>
                                                                {training.trainingType?.name || trainingTypes.find(type => type.id === training.training_type_id)?.name || '-'}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Location')}</p>
                                                            <p className="text-xs text-gray-900 truncate" title={training.location}>{training.location || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Trainer')}</p>
                                                            <p className="text-xs text-gray-900 truncate">{training.trainer?.name || '-'}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Branch')}</p>
                                                            <p className="text-xs text-gray-900 truncate">{training.branch?.branch_name || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Start Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(training.start_date)}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('End Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(training.end_date)}</p>
                                                        </div>                                                        
                                                    </div>

                                                    
                                                </div>

                                                <div className="flex items-center justify-between pt-3 border-t">
                                                    {(() => {
                                                        const statusColors = {
                                                            scheduled: 'bg-blue-100 text-blue-800',
                                                            ongoing: 'bg-yellow-100 text-yellow-800',
                                                            completed: 'bg-green-100 text-green-800',
                                                            cancelled: 'bg-red-100 text-red-800'
                                                        };
                                                        return (
                                                            <span className={`px-2 py-1 rounded-full text-xs ${statusColors[training.status as keyof typeof statusColors]}`}>
                                                                {t(training.status.charAt(0).toUpperCase() + training.status.slice(1))}
                                                            </span>
                                                        );
                                                    })()}
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('manage-training-tasks') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button 
                                                                            variant="ghost" 
                                                                            size="sm" 
                                                                            onClick={() => router.visit(route('training.trainings.tasks.index', training.id))} 
                                                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                                        >
                                                                            <CheckSquare className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Tasks')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-trainings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', training)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-trainings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(training.id)}
                                                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
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
                                    icon={GraduationCap}
                                    title={t('No training list found')}
                                    description={t('Get started by creating your first training list.')}
                                    hasFilters={!!(filters.title || filters.status || filters.branch_id || filters.department_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-trainings"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Training List')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={trainings}
                        routeName="training.trainings.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} trainingTypes={trainingTypes} trainers={trainers} branches={branches} departments={departments} users={users} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditTraining
                        data={modalState.data}
                        training={modalState.data}
                        onSuccess={closeModal}
                        trainingTypes={trainingTypes}
                        trainers={trainers}
                        branches={branches}
                        departments={departments}
                        users={users}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Training List')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}