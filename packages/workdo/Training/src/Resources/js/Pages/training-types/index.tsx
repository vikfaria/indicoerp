import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, GraduationCap } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import Create from './create';
import EditTrainingType from './edit';
import NoRecordsFound from '@/components/no-records-found';
import { TrainingType, TrainingTypesIndexProps, TrainingTypeFilters, TrainingTypeModalState } from './types';

export default function Index() {
    const { t } = useTranslation();
    const { trainingTypes, branches, departments, auth } = usePage<TrainingTypesIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<TrainingTypeFilters>({
        name: urlParams.get('name') || '',
        branch_id: urlParams.get('branch_id') || '',
        department_id: urlParams.get('department_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<TrainingTypeModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [showFilters, setShowFilters] = useState(false);

    // Filter departments based on selected branch
    const filteredDepartments = filters.branch_id 
        ? departments.filter(dept => dept.branch_id.toString() === filters.branch_id)
        : departments;


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'training.training-types.destroy',
        defaultMessage: t('Are you sure you want to delete this training type?')
    });

    const handleFilter = () => {
        router.get(route('training.training-types.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('training.training-types.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', branch_id: '', department_id: '' });
        router.get(route('training.training-types.index'), {per_page: perPage});
    };

    const openModal = (mode: 'add' | 'edit', data: TrainingType | null = null) => {
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
            key: 'branch',
            header: t('Branch'),
            render: (value: any, trainingType: TrainingType) => trainingType.branch?.branch_name || '-'
        },
        {
            key: 'department',
            header: t('Department'),
            render: (value: any, trainingType: TrainingType) => trainingType.department?.department_name || '-'
        },
        {
            key: 'description',
            header: t('Description'),
            render: (value: string) => {
                if (!value) return '-';
                return (
                    <span className="text-sm text-gray-600 truncate max-w-40" title={value}>
                        {value.length > 50 ? `${value.substring(0, 50)}...` : value}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-training-types', 'delete-training-types'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, trainingType: TrainingType) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-training-types') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', trainingType)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-training-types') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(trainingType.id)}
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
            breadcrumbs={[{label: t('Training')}, {label: t('Training Types')}]}
            pageTitle={t('Manage Training Types')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-training-types') && (
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
            <Head title={t('Training Types')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search training types...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="training.training-types.index"
                                filters={filters}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.branch_id, filters.department_id].filter(Boolean).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Branch')}</label>
                                <Select value={filters.branch_id} onValueChange={(value) => setFilters({...filters, branch_id: value, department_id: ''})}>
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
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={trainingTypes.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={GraduationCap}
                                        title={t('No training types found')}
                                        description={t('Get started by creating your first training type.')}
                                        hasFilters={!!(filters.name || filters.branch_id || filters.department_id)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-training-types"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Training Type')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={trainingTypes}
                        routeName="training.training-types.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} branches={branches} departments={departments}  />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditTrainingType
                        data={modalState.data}
                        trainingType={modalState.data}
                        onSuccess={closeModal}
                        branches={branches}
                        departments={departments}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Training Type')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}