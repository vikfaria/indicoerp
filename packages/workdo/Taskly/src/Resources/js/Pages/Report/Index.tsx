import { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Eye, BarChart3 } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import NoRecordsFound from '@/components/no-records-found';
import { getImagePath, formatDate } from '@/utils/helpers';

interface ProjectReportItem {
    id: number;
    name: string;
    start_date?: string;
    end_date?: string;
    status: string;
    tasks_count: string;
    bugs_count: string;
    milestones_count: string;
}

interface ProjectReportIndexProps {
    projects: {
        data: ProjectReportItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    users: Array<{
        id: number;
        name: string;
    }>;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { projects, users, auth } = usePage<ProjectReportIndexProps>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), []);

    const [filters, setFilters] = useState({
        name: urlParams.get('name') || '',
        status: urlParams.get('status') || '',
        date: urlParams.get('date') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [showFilters, setShowFilters] = useState(false);


    const handleFilter = () => {
        router.get(route('project.report.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('project.report.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', status: '', date: '' });
        router.get(route('project.report.index'), {per_page: perPage});
    };

    const getStatusColor = (status: string) => {
        const colors = {
            'Ongoing': 'bg-blue-100 text-blue-800',
            'Onhold': 'bg-yellow-100 text-yellow-800',
            'Finished': 'bg-green-100 text-green-800'
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Project Name'),
            sortable: true
        },
        {
            key: 'tasks_count',
            header: t('Tasks'),
            render: (value: string) => {
                const [completed, total] = (value || '0/0').split('/');
                const isAllCompleted = completed === total && total !== '0';
                return (
                    <span className={isAllCompleted ? 'text-green-600 font-semibold' : ''}>
                        {value || '0/0'}
                    </span>
                );
            }
        },
        {
            key: 'bugs_count',
            header: t('Bugs'),
            render: (value: string) => {
                const [completed, total] = (value || '0/0').split('/');
                const isAllCompleted = completed === total && total !== '0';
                return (
                    <span className={isAllCompleted ? 'text-green-600 font-semibold' : ''}>
                        {value || '0/0'}
                    </span>
                );
            }
        },
        {
            key: 'milestones_count',
            header: t('Milestones'),
            render: (value: string) => {
                const [completed, total] = (value || '0/0').split('/');
                const isAllCompleted = completed === total && total !== '0';
                return (
                    <span className={isAllCompleted ? 'text-green-600 font-semibold' : ''}>
                        {value || '0/0'}
                    </span>
                );
            }
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            render: (value: string) => {
                if (!value) return '-';
                const isOverdue = new Date(value) < new Date();
                return (
                    <span className={isOverdue ? 'text-red-600' : ''}>
                        {formatDate(value)}
                    </span>
                );
            }
        },
        {
            key: 'status',
            header: t('Status'),
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(value)}`}>
                    {t(value)}
                </span>
            )
        },

        ...(auth.user?.permissions?.includes('view-project') ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: ProjectReportItem) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => router.get(route('project.report.show', item.id))}
                                    className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                >
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Project'), url: route('project.dashboard.index')},
                {label: t('Project Reports')},
            ]}
            pageTitle={t('Manage Project Reports')}
        >
            <Head title={t('Project Reports')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search projects...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="project.report.index"
                                filters={{...filters}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.date].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Ongoing">{t('Ongoing')}</SelectItem>
                                        <SelectItem value="Onhold">{t('Onhold')}</SelectItem>
                                        <SelectItem value="Finished">{t('Finished')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DatePicker
                                    value={filters.date}
                                    onChange={(value) => setFilters({...filters, date: value})}
                                    placeholder={t('Select date')}
                                />
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
                                data={projects.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={BarChart3}
                                        title={t('No project reports found')}
                                        description={t('No projects available for reporting.')}
                                        hasFilters={!!(filters.name || filters.status || filters.date)}
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
                        data={projects}
                        routeName="project.report.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
