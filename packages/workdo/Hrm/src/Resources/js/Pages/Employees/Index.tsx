import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Eye, Users as UsersIcon, Lock, Download, FileImage } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import NoRecordsFound from '@/components/no-records-found';
import { Employee, EmployeesIndexProps, EmployeeFilters } from './types';
import { formatDate, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const { employees, auth, users, branches, departments, designations } = usePage<EmployeesIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<EmployeeFilters>({
        employee_id: urlParams.get('employee_id') || '',
        user_name: urlParams.get('user_name') || '',
        branch_id: urlParams.get('branch_id') || 'all',
        department_id: urlParams.get('department_id') || 'all',
        employment_type: urlParams.get('employment_type') || '',
        gender: urlParams.get('gender') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');


    const [filteredBranches, setFilteredBranches] = useState(branches || []);
    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);
    const [filteredDesignations, setFilteredDesignations] = useState(designations || []);
    const [showFilters, setShowFilters] = useState(false);

    // Handle dependent dropdown for department filters
    useEffect(() => {
        if (filters.branch_id && filters.branch_id !== 'all') {
            const branchDepartments = departments.filter(dept => dept.branch_id.toString() === filters.branch_id);
            setFilteredDepartments(branchDepartments);
            // Clear department if it doesn't belong to selected branch
            if (filters.department_id && filters.department_id !== 'all') {
                const departmentExists = branchDepartments.find(dept => dept.id.toString() === filters.department_id);
                if (!departmentExists) {
                    setFilters(prev => ({ ...prev, department_id: 'all' }));
                }
            }
        } else {
            setFilteredDepartments(departments || []);
            setFilters(prev => ({ ...prev, department_id: 'all' }));
        }
    }, [filters.branch_id]);

    // Handle dependent dropdown for designation filters
    useEffect(() => {
        if (filters.department_id && filters.department_id !== 'all') {
            const departmentDesignations = designations.filter(desig => desig.department_id.toString() === filters.department_id);
            setFilteredDesignations(departmentDesignations);
        } else {
            setFilteredDesignations(designations || []);
        }
    }, [filters.department_id]);

    const pageButtons = usePageButtons('googleDriveBtn', { module: 'Employee', settingKey: 'GoogleDrive Employee' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Employee', settingKey: 'OneDrive Employee' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.employees.destroy',
        defaultMessage: t('Are you sure you want to delete this employee?')
    });

    const handleFilter = () => {
        router.get(route('hrm.employees.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.employees.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            employee_id: '',
            user_name: '',
            branch_id: 'all',
            department_id: 'all',
            employment_type: '',
            gender: '',
        });
        router.get(route('hrm.employees.index'), { per_page: perPage, view: viewMode });
    };



    const tableColumns = [
        {
            key: 'employee_id',
            header: t('Employee Id'),
            sortable: true,
            render: (value: string, employee: Employee) =>
                auth.user?.permissions?.includes('view-employees') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('hrm.employees.show', employee.id))}>{value}</span>
                ) : (
                    <span>{value}</span>
                )
        },
        {
            key: 'user.name',
            header: t('Employee Name'),
            sortable: false,
            render: (value: any, row: any) => (
                <div className="flex items-center gap-2">
                    {row.user?.avatar ? (
                        <img
                            src={getImagePath(row.user.avatar)}
                            alt="Avatar"
                            className="w-8 h-8 rounded-full object-cover flex-shrink-0"
                        />
                    ) : (
                        <img
                            src={getImagePath('avatar.png')}
                            alt="Avatar"
                            className="w-8 h-8 rounded-full object-cover flex-shrink-0"
                        />
                    )}
                    <span>{row.user?.name || '-'}</span>
                </div>
            )
        },
        {
            key: 'branch.branch_name',
            header: t('Branch'),
            sortable: false,
            render: (value: any, row: any) => row.branch?.branch_name || '-'
        },
        {
            key: 'department.department_name',
            header: t('Department'),
            sortable: false,
            render: (value: any, row: any) => row.department?.department_name || '-'
        },
        {
            key: 'designation.designation_name',
            header: t('Designation'),
            sortable: false,
            render: (value: any, row: any) => row.designation?.designation_name || '-'
        },
        {
            key: 'employment_type',
            header: t('Employment Type'),
            sortable: false,
            render: (value: any) => {
                const options: any = { "0": "Full Time", "1": "Part Time", "2": "Temporary", "3": "Contract" };
                return options[value] || value;
            }
        },
        {
            key: 'date_of_joining',
            header: t('Date Of Joining'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-employees','edit-employees', 'delete-employees'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, employee: Employee) => (
                <div className="flex gap-1">
                    {employee.user?.is_disable === 1 ? (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <div className="h-8 w-8 p-0 flex items-center justify-center text-gray-400">
                                    <Lock className="h-4 w-4" />
                                </div>
                            </TooltipTrigger>
                            <TooltipContent><p>{t('User is disabled')}</p></TooltipContent>
                        </Tooltip>
                    ) : (
                        <TooltipProvider>
                            {auth.user?.permissions?.includes('view-employees') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('hrm.employees.show', employee.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                            <Eye className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('View')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('edit-employees') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => router.visit(route('hrm.employees.edit', employee.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                            <EditIcon className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Edit')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('delete-employees') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => openDeleteDialog(employee.id)}
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
                    )}
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('HRM'), url: route('hrm.index') },
                { label: t('Employees') }
            ]}
            pageTitle={t('Manage Employees')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {auth.user?.permissions?.includes('create-employees') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => router.visit(route('hrm.employees.create'))}>
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
            <Head title={t('Employees')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.employee_id}
                                onChange={(value) => setFilters({ ...filters, employee_id: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Employees...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="hrm.employees.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="hrm.employees.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.branch_id !== 'all' ? filters.branch_id : '', filters.department_id !== 'all' ? filters.department_id : '', filters.employment_type, filters.gender].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Branch')}</label>
                                <Select value={filters.branch_id} onValueChange={(value) => setFilters({ ...filters, branch_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Branchs')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        <SelectItem value="all">{t('All Branchs')}</SelectItem>
                                        {filteredBranches?.map((branch: any) => (
                                            <SelectItem key={branch.id} value={branch.id.toString()}>
                                                {branch.branch_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Department')}</label>
                                <Select value={filters.department_id} onValueChange={(value) => setFilters({ ...filters, department_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Departments')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        <SelectItem value="all">{t('All Departments')}</SelectItem>
                                        {filteredDepartments?.map((department: any) => (
                                            <SelectItem key={department.id} value={department.id.toString()}>
                                                {department.department_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employment Type')}</label>
                                <Select value={filters.employment_type} onValueChange={(value) => setFilters({ ...filters, employment_type: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Employment Type')} />
                                    </SelectTrigger>
                                    <SelectContent searchable={true}>
                                        <SelectItem value="Full Time">{t('Full Time')}</SelectItem>
                                        <SelectItem value="Part Time">{t('Part Time')}</SelectItem>
                                        <SelectItem value="Temporary">{t('Temporary')}</SelectItem>
                                        <SelectItem value="Contract">{t('Contract')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Gender')}</label>
                                <Select value={filters.gender} onValueChange={(value) => setFilters({ ...filters, gender: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Gender')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">{t('Male')}</SelectItem>
                                        <SelectItem value="1">{t('Female')}</SelectItem>
                                        <SelectItem value="2">{t('Other')}</SelectItem>
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
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={employees?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={UsersIcon}
                                            title={t('No Employees found')}
                                            description={t('Get started by creating your first Employee.')}
                                            hasFilters={!!(filters.employee_id || filters.user_name || (filters.branch_id !== 'all' && filters.branch_id) || (filters.department_id !== 'all' && filters.department_id) || filters.employment_type || filters.gender)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-employees"
                                            onCreateClick={() => router.visit(route('hrm.employees.create'))}
                                            createButtonText={t('Create Employee')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {employees?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {employees?.data?.map((employee) => (
                                        <Card key={employee.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                        {employee.user?.avatar ? (
                                                            <img
                                                                src={getImagePath(employee.user.avatar)}
                                                                alt="Avatar"
                                                                className="w-12 h-12 object-cover rounded-lg border"
                                                            />
                                                        ) : (
                                                            <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                                                <UsersIcon className="h-6 w-6 text-primary" />
                                                            </div>
                                                        )}
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm text-gray-900">{employee.employee_id}</h3>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Employee Name')}</p>
                                                        <p className="font-medium text-xs">{employee.user?.name || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Branch')}</p>
                                                        <p className="font-medium text-xs">{employee.branch?.branch_name || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Department')}</p>
                                                        <p className="font-medium text-xs">{employee.department?.department_name || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Designation')}</p>
                                                        <p className="font-medium text-xs">{employee.designation?.designation_name || '-'}</p>
                                                    </div>
                                                    
                                                </div>
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Employment Type')}</p>
                                                        <p className="font-medium text-xs">{(() => { const options: any = { "0": "Full Time", "1": "Part Time", "2": "Temporary", "3": "Contract" }; return options[employee.employment_type] || employee.employment_type || '-'; })()}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Date Of Joining')}</p>
                                                        <p className="font-medium text-xs">{employee.date_of_joining ? formatDate(employee.date_of_joining) : '-'}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-end gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                {employee.user?.is_disable === 1 ? (
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <div className="h-9 w-9 p-0 flex items-center justify-center text-gray-400">
                                                                <Lock className="h-4 w-4" />
                                                            </div>
                                                        </TooltipTrigger>
                                                        <TooltipContent><p>{t('User is disabled')}</p></TooltipContent>
                                                    </Tooltip>
                                                ) : (
                                                    <TooltipProvider>
                                                        {auth.user?.permissions?.includes('view-employees') && (
                                                            <Tooltip delayDuration={300}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('hrm.employees.show', employee.id))} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('View')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {auth.user?.permissions?.includes('edit-employees') && (
                                                            <Tooltip delayDuration={300}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.visit(route('hrm.employees.edit', employee.id))} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                        <EditIcon className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Edit')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {auth.user?.permissions?.includes('delete-employees') && (
                                                            <Tooltip delayDuration={300}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => openDeleteDialog(employee.id)}
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
                                                )}
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={UsersIcon}
                                    title={t('No Employees found')}
                                    description={t('Get started by creating your first Employee.')}
                                    hasFilters={!!(filters.employee_id || filters.user_name || (filters.branch_id !== 'all' && filters.branch_id) || (filters.department_id !== 'all' && filters.department_id) || filters.employment_type || filters.gender)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-employees"
                                    onCreateClick={() => router.visit(route('hrm.employees.create'))}
                                    createButtonText={t('Create Employee')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={employees || { data: [], links: [], meta: {} }}
                        routeName="hrm.employees.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>





            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Employee')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}