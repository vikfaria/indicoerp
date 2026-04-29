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
import { Plus, Edit as EditIcon, Trash2, Eye, Clock as ClockIcon, Download, FileImage } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import Create from './Create';
import Edit from './Edit';
import View from './Show';

import NoRecordsFound from '@/components/no-records-found';
import { Attendance, AttendancesIndexProps, AttendanceFilters, AttendanceModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { attendances, auth, employees, shifts } = usePage<AttendancesIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<AttendanceFilters>({
        search: urlParams.get('search') || '',
        status: urlParams.get('status') || '',
        employee_id: urlParams.get('employee_id') || '',
        date_from: urlParams.get('date_from') || '',
        date_to: urlParams.get('date_to') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<AttendanceModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.attendances.destroy',
        defaultMessage: t('Are you sure you want to delete this attendance?')
    });

    const handleFilter = () => {
        router.get(route('hrm.attendances.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.attendances.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            search: '',
            status: '',
            employee_id: '',
            date_from: '',
            date_to: '',
        });
        router.get(route('hrm.attendances.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit' | 'view', data: Attendance | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'employee.user.name',
            header: t('Employee Name'),
            sortable: false,
            render: (value: any, row: any) => row.user?.name || '-'
        },
        {
            key: 'date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'shift.shift_name',
            header: t('Shift'),
            sortable: false,
            render: (value: any, row: any) => row.shift?.shift_name || '-'
        },
        {
            key: 'clock_in',
            header: t('Clock In'),
            sortable: false,
            render: (value: string) => value ? formatDateTime(value) : '-'
        },
        {
            key: 'clock_out',
            header: t('Clock Out'),
            sortable: false,
            render: (value: string) => value ? formatDateTime(value) : '-'
        },
        {
            key: 'total_hour',
            header: t('Total Hour'),
            sortable: false,
            render: (value: number) => value ? `${value}h` : '-'
        },
        {
            key: 'break_hour',
            header: t('Break Hour'),
            sortable: false,
            render: (value: number) => value ? `${value}h` : '-'
        },
        {
            key: 'overtime_hours',
            header: t('Overtime'),
            sortable: false,
            render: (value: number) => value ? `${value}h` : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusColors = {
                    present: 'bg-green-100 text-green-800',
                    'half day': 'bg-yellow-100 text-yellow-800',
                    absent: 'bg-red-100 text-red-800'
                };
                const formatStatus = (status: string) => {
                    return status.split(' ').map(word =>
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                };

                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || 'bg-gray-100 text-gray-800'}`}>
                        {t(formatStatus(value || 'Unknown'))}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-attendances', 'edit-attendances', 'delete-attendances'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, attendance: Attendance) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-attendances') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('view', attendance)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-attendances') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', attendance)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-attendances') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(attendance.id)}
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
                { label: t('Attendances') }
            ]}
            pageTitle={t('Manage Attendances')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-attendances') && (
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
            <Head title={t('Attendances')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        {auth.user?.permissions?.includes('manage-employees') && (
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.search}
                                    onChange={(value) => setFilters({ ...filters, search: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search by employee name or date...')}
                                />
                            </div>
                        )}
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="hrm.attendances.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.employee_id, filters.date_from, filters.date_to].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                <Select value={filters.employee_id} onValueChange={(value) => setFilters({ ...filters, employee_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Employee')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        {employees?.map((employee: any) => (
                                            <SelectItem key={employee.id} value={employee.id.toString()}>
                                                {employee.user?.name || employee.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="present">{t('Present')}</SelectItem>
                                        <SelectItem value="half day">{t('Half Day')}</SelectItem>
                                        <SelectItem value="absent">{t('Absent')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date From')}</label>
                                <Input
                                    type="date"
                                    value={filters.date_from}
                                    onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
                                    placeholder={t('Start Date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date To')}</label>
                                <Input
                                    type="date"
                                    value={filters.date_to}
                                    onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
                                    placeholder={t('End Date')}
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
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={attendances?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={ClockIcon}
                                        title={t('No Attendances found')}
                                        description={t('Get started by creating your first Attendance.')}
                                        hasFilters={!!(filters.search || filters.status || filters.employee_id || filters.date_from || filters.date_to)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-attendances"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Attendance')}
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
                        data={attendances || { data: [], links: [], meta: {} }}
                        routeName="hrm.attendances.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        attendance={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
                {modalState.mode === 'view' && modalState.data && (
                    <View
                        attendance={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Attendance')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}