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
import { Plus, Edit as EditIcon, Trash2, Eye, AlertOctagon as AlertOctagonIcon, Download, FileImage, MessageSquare, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditWarning from './Edit';
import WarningResponse from './Response';
import WarningView from './View';

import NoRecordsFound from '@/components/no-records-found';
import { Warning, WarningsIndexProps, WarningFilters, WarningModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { warnings, auth, users, warningtypes } = usePage<WarningsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<WarningFilters>({
        subject: urlParams.get('subject') || '',
        employee_id: urlParams.get('employee_id') || 'all',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<WarningModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.warnings.destroy',
        defaultMessage: t('Are you sure you want to delete this warning?')
    });

    const handleFilter = () => {
        router.get(route('hrm.warnings.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.warnings.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            subject: '',
            employee_id: 'all',
        });
        router.get(route('hrm.warnings.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit' | 'response' | 'view', data: Warning | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'employee.name',
            header: t('Employee Name'),
            sortable: false,
            render: (value: any, row: any) => row.employee?.name || '-'
        },
        {
            key: 'warning_by.name',
            header: t('Warning By Name'),
            sortable: false,
            render: (value: any, row: any) => row.warning_by?.name || '-'
        },
        {
            key: 'warning_type.warning_type_name',
            header: t('Warning Type'),
            sortable: false,
            render: (value: any, row: any) => row.warning_type?.warning_type_name || '-'
        },
        {
            key: 'subject',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'severity',
            header: t('Severity'),
            sortable: false,
            render: (value: string) => {
                const severityColors = {
                    'Minor': 'bg-green-100 text-green-800',
                    'Moderate': 'bg-yellow-100 text-yellow-800',
                    'Major': 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${severityColors[value as keyof typeof severityColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(value || '-')}
                    </span>
                );
            }
        },
        {
            key: 'warning_date',
            header: t('Warning Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Warning Status'),
            sortable: false,
            render: (value: string) => {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'approved': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Pending')}
                    </span>
                );
            }
        },
        {
            key: 'document',
            header: t('Document'),
            sortable: false,
            render: (_: any, warning: Warning) => (
                warning.document ? (
                    <a href={getImagePath(warning.document)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <FileImage className="h-4 w-4" />
                    </a>
                ) : '-'
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-warnings', 'manage-warning-response', 'edit-warnings', 'delete-warnings'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, warning: Warning) => (
                <div className="flex gap-1">
                    <TooltipProvider>

                        {auth.user?.permissions?.includes('manage-warning-response') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('response', warning)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Response')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-warnings') && (warning.status === 'approved' || warning.status === 'rejected') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('view', warning)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-warnings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', warning)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-warnings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(warning.id)}
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
                { label: t('Warnings') }
            ]}
            pageTitle={t('Manage Warnings')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-warnings') && (
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
            <Head title={t('Warnings')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.subject}
                                onChange={(value) => setFilters({ ...filters, subject: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Warnings...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="hrm.warnings.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="hrm.warnings.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.employee_id !== 'all' ? filters.employee_id : ''].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        {auth.user?.permissions?.includes('manage-employees') && (
                            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                    <Select value={filters.employee_id} onValueChange={(value) => setFilters({ ...filters, employee_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Employees')} />
                                        </SelectTrigger>
                                        <SelectContent searchable={true}>
                                            <SelectItem value="all">{t('All Employees')}</SelectItem>
                                            {users?.map((employee: any) => (
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
                        )}
                    </CardContent>
                )}

                {/* Table Content */}
                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={warnings?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={AlertOctagonIcon}
                                            title={t('No Warnings found')}
                                            description={t('Get started by creating your first Warning.')}
                                            hasFilters={!!(filters.subject || (filters.employee_id !== 'all' && filters.employee_id))}
                                            onClearFilters={clearFilters}
                                            createPermission="create-warnings"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Warning')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {warnings?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {warnings?.data?.map((warning) => (
                                        <Card key={warning.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <AlertOctagonIcon className="h-6 w-6 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-lg truncate">{warning.employee?.name || 'Unknown Employee'}</h3>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Warning By')}</p>
                                                        <p className="font-medium text-xs">{warning.warning_by?.name || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Warning Date')}</p>
                                                        <p className="font-medium text-xs">{warning.warning_date ? formatDate(warning.warning_date) : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Warning Type')}</p>
                                                        <p className="font-medium text-xs">{warning.warning_type?.warning_type_name || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Document')}</p>
                                                        {warning.document ? (
                                                            <a href={getImagePath(warning.document)} target="_blank" className="text-blue-600 hover:text-blue-700 flex items-center gap-1 text-xs">
                                                                <FileImage className="h-3 w-3" />
                                                                {t('View')}
                                                            </a>
                                                        ) : (
                                                            <p className="font-medium text-xs">-</p>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                        <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${
                                                            warning.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                            warning.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                            warning.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {t(warning.status ? warning.status.charAt(0).toUpperCase() + warning.status.slice(1) : 'Pending')}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Severity')}</p>
                                                        <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${
                                                            warning.severity === 'Minor' ? 'bg-green-100 text-green-800' :
                                                            warning.severity === 'Moderate' ? 'bg-yellow-100 text-yellow-800' :
                                                            warning.severity === 'Major' ? 'bg-red-100 text-red-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {t(warning.severity || '-')}
                                                        </span>
                                                    </div>
                                                    
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('manage-warning-response') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('response', warning)} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700">
                                                                    <Play className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Response')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-warnings') && (warning.status === 'approved' || warning.status === 'rejected') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('view', warning)} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-warnings') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', warning)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-warnings') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(warning.id)}
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
                                    icon={AlertOctagonIcon}
                                    title={t('No Warnings found')}
                                    description={t('Get started by creating your first Warning.')}
                                    hasFilters={!!(filters.subject || (filters.employee_id !== 'all' && filters.employee_id))}
                                    onClearFilters={clearFilters}
                                    createPermission="create-warnings"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Warning')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={warnings || { data: [], links: [], meta: {} }}
                        routeName="hrm.warnings.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditWarning
                        warning={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
                {modalState.mode === 'response' && modalState.data && (
                    <WarningResponse
                        warning={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
                {modalState.mode === 'view' && modalState.data && (
                    <WarningView
                        warning={modalState.data}
                        onClose={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Warning')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}