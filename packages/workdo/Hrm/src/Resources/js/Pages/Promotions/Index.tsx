import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Eye, Tag as TagIcon, Download, FileImage, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditPromotion from './Edit';
import View from './View';
import StatusUpdateModal from './StatusUpdateModal';

import NoRecordsFound from '@/components/no-records-found';
import { Promotion, PromotionsIndexProps, PromotionFilters, PromotionModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

const getStatusBadge = (status: string) => {
    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    };
    return statusColors[status as keyof typeof statusColors] || statusColors.pending;
};

export default function Index() {
    const { t } = useTranslation();
    const { promotions, auth, employees, branches, departments, designations } = usePage<PromotionsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);
    
    const [filters, setFilters] = useState<PromotionFilters>({
        name: urlParams.get('name') || '',
        employee_id: urlParams.get('employee_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<PromotionModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Promotion | null>(null);
    const [statusModalState, setStatusModalState] = useState<{
        isOpen: boolean;
        promotion: Promotion | null;
    }>({
        isOpen: false,
        promotion: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.promotions.destroy',
        defaultMessage: t('Are you sure you want to delete this promotion?')
    });

    const handleFilter = () => {
        router.get(route('hrm.promotions.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.promotions.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            name: '',
            employee_id: '',
        });
        router.get(route('hrm.promotions.index'), {per_page: perPage});
    };

    const openModal = (mode: 'add' | 'edit', data: Promotion | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const openStatusModal = (promotion: Promotion) => {
        setStatusModalState({ isOpen: true, promotion });
    };

    const closeStatusModal = () => {
        setStatusModalState({ isOpen: false, promotion: null });
    };

    const tableColumns = [
        {
            key: 'employee.name',
            header: t('Employee'),
            sortable: false,
            render: (value: any, row: any) => row.employee?.name || '-'
        },
        {
            key: 'previous_branch.branch_name',
            header: t('Previous Branch'),
            sortable: false,
            render: (value: any, row: any) => row.previous_branch?.branch_name || '-'
        },
        {
            key: 'current_branch.branch_name',
            header: t('Current Branch'),
            sortable: false,
            render: (value: any, row: any) => row.current_branch?.branch_name || '-'
        },
        {
            key: 'current_designation.designation_name',
            header: t('Current Designation'),
            sortable: false,
            render: (value: any, row: any) => row.current_designation?.designation_name || '-'
        },
        {
            key: 'effective_date',
            header: t('Effective Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusClass = getStatusBadge(value || 'pending');
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusClass}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Pending')}
                    </span>
                );
            }
        },
        {
            key: 'approved_by.name',
            header: t('Approved By'),
            sortable: false,
            render: (value: any, row: any) => row.approved_by?.name || '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-promotions-status', 'view-promotions', 'edit-promotions', 'delete-promotions'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, promotion: Promotion) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-promotions-status') && promotion.status === 'pending' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openStatusModal(promotion)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Status')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-promotions') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(promotion)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-promotions') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', promotion)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-promotions') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(promotion.id)}
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
                { label: t('HRM'), url: route('hrm.index') },
                {label: t('Promotions')}
            ]}
            pageTitle={t('Manage Promotions')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-promotions') && (
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
            <Head title={t('Promotions')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Promotions...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="hrm.promotions.index"
                                filters={{...filters}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>

                {/* Advanced Filters */}
                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                <Select value={filters.employee_id || 'all'} onValueChange={(value) => setFilters({...filters, employee_id: value === 'all' ? '' : value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Employees')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        <SelectItem value="all">{t('All Employees')}</SelectItem>
                                        {employees?.map((employee: any) => (
                                            <SelectItem key={employee.id} value={employee.id.toString()}>
                                                {employee.name}
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

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                        <DataTable
                            data={promotions?.data || []}
                            columns={tableColumns}
                            onSort={handleSort}
                            sortKey={sortField}
                            sortDirection={sortDirection as 'asc' | 'desc'}
                            className="rounded-none"
                            emptyState={
                                <NoRecordsFound
                                    icon={TagIcon}
                                    title={t('No Promotions found')}
                                    description={t('Get started by creating your first Promotion.')}
                                    hasFilters={!!(filters.name)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-promotions"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Promotion')}
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
                        data={promotions || { data: [], links: [], meta: {} }}
                        routeName="hrm.promotions.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditPromotion
                        promotion={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View promotion={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Promotion')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            <Dialog open={statusModalState.isOpen} onOpenChange={closeStatusModal}>
                {statusModalState.promotion && (
                    <StatusUpdateModal
                        promotion={statusModalState.promotion}
                        onSuccess={closeStatusModal}
                    />
                )}
            </Dialog>
        </AuthenticatedLayout>
    );
}