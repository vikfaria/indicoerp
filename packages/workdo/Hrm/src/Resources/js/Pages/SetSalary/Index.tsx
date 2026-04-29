import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Eye, DollarSign, UserIcon } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency } from '@/utils/helpers';

interface Employee {
    id: number;
    employee_id: string;
    basic_salary?: number;
    user?: {
        id: number;
        name: string;
        avatar?: string;
    };
    branch?: {
        branch_name: string;
    };
    department?: {
        department_name: string;
    };
    designation?: {
        designation_name: string;
    };
}

interface SetSalaryIndexProps {
    employees: {
        data: Employee[];
        links: any[];
        meta: any;
    };
    auth: any;
    allEmployees: any[];
}

export default function Index() {
    const { t } = useTranslation();
    const { employees, auth, allEmployees } = usePage<SetSalaryIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        search: urlParams.get('search') || '',
        employee_id: urlParams.get('employee_id') || '',
    });

    const [showFilters, setShowFilters] = useState(false);

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');


    const handleFilter = () => {
        router.get(route('hrm.set-salary.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.set-salary.index'), { ...filters, per_page: perPage, sort: field, direction }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            search: '',
            employee_id: '',
        });
        router.get(route('hrm.set-salary.index'), { per_page: perPage });
    };

    const tableColumns = [
        {
            key: 'employee_id',
            header: t('Employee ID'),
            sortable: true,
            render: (value: string, employee: Employee) =>
                auth.user?.permissions?.includes('view-set-salary') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('hrm.set-salary.show', employee.id))}>{value}</span>
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
            key: 'basic_salary',
            header: t('Basic Salary'),
            sortable: false,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        ...(auth.user?.permissions?.includes('view-set-salary') ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, employee: Employee) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-set-salary') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.get(route('hrm.set-salary.show', employee.id))}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View Salary')}</p>
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
                { label: t('Set Salary') }
            ]}
            pageTitle={t('Set Salary')}
        >
            <Head title={t('Set Salary')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({ ...filters, search: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search by employee name or ID...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="hrm.set-salary.index"
                                filters={{ ...filters }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.employee_id].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                            {auth.user?.permissions?.includes('manage-employees') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Employee')}</label>
                                    <Select value={filters.employee_id} onValueChange={(value) => setFilters({ ...filters, employee_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by Employee')} />
                                        </SelectTrigger>
                                        <SelectContent searchable={true}>
                                            {allEmployees?.map((employee: any) => (
                                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                                    {employee.user?.name || employee.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
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
                                data={employees?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={DollarSign}
                                        title={t('No Employees found')}
                                        description={t('No employees available for salary management.')}
                                        hasFilters={!!(filters.search || filters.employee_id)}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={employees || { data: [], links: [], meta: {} }}
                        routeName="hrm.set-salary.index"
                        filters={{ ...filters, per_page: perPage }}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}