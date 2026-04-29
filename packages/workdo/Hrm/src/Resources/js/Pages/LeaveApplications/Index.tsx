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
import { Plus, Edit as EditIcon, Trash2, Eye, FileText as FileTextIcon, Download, FileImage, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditLeaveApplication from './Edit';
import View from './View';
import StatusUpdate from './StatusUpdate';
import NoRecordsFound from '@/components/no-records-found';
import { LeaveApplication, LeaveApplicationsIndexProps, LeaveApplicationFilters, LeaveApplicationModalState } from './types';
import { Input } from '@/components/ui/input';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { leaveapplications, auth, users, leavetypes, employees } = usePage<LeaveApplicationsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<LeaveApplicationFilters>({
        reason: urlParams.get('reason') || '',
        status: urlParams.get('status') || '',
        employee_id: urlParams.get('employee_id') || '',
        start_date: urlParams.get('start_date') || '',
        end_date: urlParams.get('end_date') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<LeaveApplicationModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<LeaveApplication | null>(null);
    const [statusModalItem, setStatusModalItem] = useState<LeaveApplication | null>(null);

    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.leave-applications.destroy',
        defaultMessage: t('Are you sure you want to delete this leaveapplication?')
    });

    const handleFilter = () => {
        router.get(route('hrm.leave-applications.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.leave-applications.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            reason: '',
            status: '',
            employee_id: '',
            start_date: '',
            end_date: '',
        });
        router.get(route('hrm.leave-applications.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: LeaveApplication | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const openStatusModal = (leaveapplication: LeaveApplication) => {
        setStatusModalItem(leaveapplication);
    };

    const tableColumns = [
        {
            key: 'employee.name',
            header: t('Employee'),
            sortable: false,
            render: (value: any, row: any) => row.employee?.name || '-'
        },
        {
            key: 'leave_type.name',
            header: t('Leave Type'),
            sortable: false,
            render: (value: any, row: any) => (
                <div className="flex items-center gap-2">
                    <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: row.leave_type?.color || '#gray' }}
                    ></div>
                    <div className="flex flex-col">
                        <span>{row.leave_type?.name || '-'}</span>
                        <span className={`text-xs px-1.5 py-0.5 rounded-full w-fit ${
                            row.leave_type?.is_paid 
                                ? 'bg-green-100 text-green-700' 
                                : 'bg-orange-100 text-orange-700'
                        }`}>
                            {row.leave_type?.is_paid ? t('Paid') : t('Unpaid')}
                        </span>
                    </div>
                </div>
            )
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'total_days',
            header: t('Days'),
            sortable: false,
            render: (value: number) => value || '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'approved': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Unknown')}
                    </span>
                );
            }
        },
        {
            key: 'created_at',
            header: t('Applied On'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'attachment',
            header: t('Document'),
            sortable: false,
            render: (_: any, leaveapplication: LeaveApplication) => (
                leaveapplication.attachment ? (
                    <a href={getImagePath(leaveapplication.attachment)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <FileImage className="h-4 w-4" />
                    </a>
                ) : '-'
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-leave-status', 'view-leave-applications', 'edit-leave-applications', 'delete-leave-applications'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, leaveapplication: LeaveApplication) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('manage-leave-status') && leaveapplication.status === 'pending' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openStatusModal(leaveapplication)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Manage Status')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-leave-applications') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(leaveapplication)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-leave-applications') && leaveapplication.status === 'pending' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', leaveapplication)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-leave-applications') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(leaveapplication.id)}
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
                { label: t('Leave Applications') }
            ]}
            pageTitle={t('Manage Leave Applications')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-leave-applications') && (
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
            <Head title={t('LeaveApplications')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.reason}
                                onChange={(value) => setFilters({ ...filters, reason: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search LeaveApplications...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="hrm.leave-applications.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="hrm.leave-applications.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.employee_id, filters.start_date, filters.end_date].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {auth.user?.permissions?.includes('manage-employees') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                    <Select value={filters.employee_id} onValueChange={(value) => setFilters({ ...filters, employee_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by Employee')} />
                                        </SelectTrigger>
                                        <SelectContent searchable={true}>
                                            {employees?.map((employee) => (
                                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                                    {employee.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Start Date')}</label>
                                <Input
                                    type="date"
                                    value={filters.start_date}
                                    onChange={(e) => setFilters({ ...filters, start_date: e.target.value })}
                                    placeholder={t('Filter by Start Date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('End Date')}</label>
                                <Input
                                    type="date"
                                    value={filters.end_date}
                                    onChange={(e) => setFilters({ ...filters, end_date: e.target.value })}
                                    placeholder={t('Filter by End Date')}
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2 mt-4">
                            <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                            <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                        </div>
                    </CardContent>
                )}

                {/* Table Content */}
                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={leaveapplications?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={FileTextIcon}
                                            title={t('No LeaveApplications found')}
                                            description={t('Get started by creating your first LeaveApplication.')}
                                            hasFilters={!!(filters.reason || filters.status || filters.employee_id || filters.start_date || filters.end_date)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-leave-applications"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create LeaveApplication')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {leaveapplications?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {leaveapplications?.data?.map((leaveapplication) => (
                                        <Card key={leaveapplication.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <FileTextIcon className="h-6 w-6 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-lg truncate">{leaveapplication.employee?.name || 'Unknown Employee'}</h3>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Leave Type')}</p>
                                                        <p className="font-medium text-xs">{leaveapplication.leave_type?.name || '-'}</p>
                                                    </div>
                                                     <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Total Days')}</p>
                                                        <p className="font-medium text-xs">{leaveapplication.total_days || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Start Date')}</p>
                                                        <p className="font-medium text-xs">{leaveapplication.start_date ? formatDate(leaveapplication.start_date) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('End Date')}</p>
                                                        <p className="font-medium text-xs">{leaveapplication.end_date ? formatDate(leaveapplication.end_date) : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                        <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${
                                                            leaveapplication.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                            leaveapplication.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                            leaveapplication.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {t(leaveapplication.status?.charAt(0).toUpperCase() + leaveapplication.status?.slice(1) || 'Unknown')}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('view-leave-applications') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => setViewingItem(leaveapplication)} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('manage-leave-status') && leaveapplication.status === 'pending' && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openStatusModal(leaveapplication)} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700">
                                                                    <Play className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Manage Status')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-leave-applications') && leaveapplication.status === 'pending' && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', leaveapplication)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-leave-applications') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(leaveapplication.id)}
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
                                    icon={FileTextIcon}
                                    title={t('No LeaveApplications found')}
                                    description={t('Get started by creating your first LeaveApplication.')}
                                    hasFilters={!!(filters.reason || filters.status || filters.employee_id || filters.start_date || filters.end_date)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-leave-applications"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create LeaveApplication')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={leaveapplications || { data: [], links: [], meta: {} }}
                        routeName="hrm.leave-applications.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditLeaveApplication
                        leaveapplication={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View leaveapplication={viewingItem} />}
            </Dialog>

            <Dialog open={!!statusModalItem} onOpenChange={() => setStatusModalItem(null)}>
                {statusModalItem && <StatusUpdate leaveapplication={statusModalItem} onSuccess={() => setStatusModalItem(null)} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete LeaveApplication')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}