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
import { Plus, Edit as EditIcon, Trash2, Eye, Megaphone as MegaphoneIcon, Download, FileImage, Upload, Star } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditJobPosting from './Edit';

import NoRecordsFound from '@/components/no-records-found';
import { JobPosting, JobPostingsIndexProps, JobPostingFilters, JobPostingModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const { jobpostings, auth, jobtypes, joblocations, branches } = usePage<JobPostingsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<JobPostingFilters>({
        title: urlParams.get('title') || '',
        description: urlParams.get('description') || '',
        job_type_id: urlParams.get('job_type_id') || 'all',
        location_id: urlParams.get('location_id') || 'all',
        branch_id: urlParams.get('branch_id') || 'all',
        status: urlParams.get('status') || 'all',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<JobPostingModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [filteredJobTypes, setFilteredJobTypes] = useState(jobtypes || []);
    const [filteredLocations, setFilteredLocations] = useState(joblocations || []);
    const [showFilters, setShowFilters] = useState(false);

    // Initialize filtered options without dependency
    useEffect(() => {
        setFilteredJobTypes(jobtypes || []);
        setFilteredLocations(joblocations || []);
    }, [jobtypes, joblocations]);

    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Job Posting', settingKey: 'Dropbox Job Posting' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'recruitment.job-postings.destroy',
        defaultMessage: t('Are you sure you want to delete this job posting?')
    });

    const handleFilter = () => {
        router.get(route('recruitment.job-postings.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('recruitment.job-postings.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            title: '',
            description: '',
            job_type_id: 'all',
            location_id: 'all',
            branch_id: 'all',
            status: 'all',
        });
        router.get(route('recruitment.job-postings.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: JobPosting | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'posting_code',
            header: t('Code'),
            sortable: true,
            render: (value: string, jobposting: JobPosting) =>
                auth.user?.permissions?.includes('view-job-postings') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('recruitment.job-postings.show', jobposting.id))}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'title',
            header: t('Title'),
            sortable: true,
            render: (value: string, row: any) => (
                <div className="flex items-center gap-2">
                    <span>{value}</span>
                    {row.is_featured && <Star className="h-4 w-4 text-yellow-500" />}
                </div>
            )
        },
        {
            key: 'jobType.name',
            header: t('Type'),
            sortable: false,
            render: (value: any, row: any) => row.jobType?.name || row.job_type?.name || row.jobtype?.name || '-'
        },
        {
            key: 'location.name',
            header: t('Location'),
            sortable: false,
            render: (value: any, row: any) => row.location?.name || '-'
        },
        {
            key: 'branch_name',
            header: t('Branch'),
            sortable: true,
            render: (value: any, row: any) => row.branch_name || '-'
        },
        {
            key: 'salary_range',
            header: t('Salary Range'),
            sortable: true,
            render: (value: any, row: any) => {
                if (row.min_salary && row.max_salary) {
                    return `${formatCurrency(row.min_salary)} - ${formatCurrency(row.max_salary)}`;
                }
                return '-';
            }
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusConfig: any = {
                    "0": { label: "Draft", class: "bg-gray-100 text-gray-800" },
                    "1": { label: "Published", class: "bg-green-100 text-green-800" },
                    "2": { label: "Closed", class: "bg-red-100 text-red-800" },
                    "draft": { label: "Draft", class: "bg-gray-100 text-gray-800" },
                    "active": { label: "Published", class: "bg-green-100 text-green-800" },
                    "closed": { label: "Closed", class: "bg-red-100 text-red-800" }
                };
                const config = statusConfig[value] || { label: value || '-', class: 'bg-gray-100 text-gray-800' };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${config.class}`}>
                        {t(config.label)}
                    </span>
                );
            }
        },
        {
            key: 'application_deadline',
            header: t('Deadline'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['publish-job-postings', 'view-job-postings', 'edit-job-postings', 'delete-job-postings'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, jobposting: JobPosting) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('publish-job-postings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.post(route('recruitment.job-postings.toggle-publish', jobposting.id))}
                                        className={`h-8 w-8 p-0 ${
                                            (jobposting.status === 'draft' || jobposting.status === '0')
                                                ? 'text-green-600 hover:text-green-700'
                                                : 'text-orange-600 hover:text-orange-700'
                                        }`}
                                    >
                                        {(jobposting.status === 'draft' || jobposting.status === '0') ? (
                                            <Upload className="h-4 w-4" />
                                        ) : (
                                            <Download className="h-4 w-4" />
                                        )}
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{(jobposting.status === 'draft' || jobposting.status === '0') ? t('Publish') : t('Unpublish')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-job-postings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.job-postings.show', jobposting.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-job-postings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', jobposting)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-job-postings') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(jobposting.id)}
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
                { label: t('Recruitment'), url: route('recruitment.index') },
                {label: t('Job Postings')}
            ]}
            pageTitle={t('Manage Job Postings')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {dropboxBtn.map((button) => (
                                <div key={button.id}>{button.component}</div>
                            ))}
                    {auth.user?.permissions?.includes('create-job-postings') && (
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
            <Head title={t('Job Postings')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({...filters, title: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search Job Postings...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="recruitment.job-postings.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="recruitment.job-postings.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.job_type_id !== 'all' ? filters.job_type_id : '', filters.location_id !== 'all' ? filters.location_id : '', filters.branch_id !== 'all' ? filters.branch_id : '', filters.status !== 'all' ? filters.status : ''].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Job type')}</label>
                                <Select value={filters.job_type_id} onValueChange={(value) => setFilters({...filters, job_type_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Jobtypes')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Job Types')}</SelectItem>
                                        {filteredJobTypes?.map((jobType: any) => (
                                            <SelectItem key={jobType.id} value={jobType.id.toString()}>
                                                {jobType.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Location')}</label>
                                <Select value={filters.location_id} onValueChange={(value) => setFilters({...filters, location_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Locations')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Locations')}</SelectItem>
                                        {filteredLocations?.map((location: any) => (
                                            <SelectItem key={location.id} value={location.id.toString()}>
                                                {location.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Branch')}</label>
                                <Select value={filters.branch_id} onValueChange={(value) => setFilters({...filters, branch_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Branches')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Branches')}</SelectItem>
                                        {branches?.map((branch: any) => (
                                            <SelectItem key={branch.id} value={branch.id.toString()}>
                                                {branch.branch_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Status')}</SelectItem>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="active">{t('Published')}</SelectItem>
                                        <SelectItem value="closed">{t('Closed')}</SelectItem>
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
                                data={jobpostings?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={MegaphoneIcon}
                                        title={t('No Job Postings found')}
                                        description={t('Get started by creating your first Job Posting.')}
                                        hasFilters={!!(filters.title || filters.description || (filters.job_type_id !== 'all' && filters.job_type_id) || (filters.location_id !== 'all' && filters.location_id) || (filters.branch_id !== 'all' && filters.branch_id) || (filters.status !== 'all' && filters.status))}
                                        onClearFilters={clearFilters}
                                        createPermission="create-job-postings"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Job Posting')}
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {jobpostings?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {jobpostings?.data?.map((jobposting) => {
                                        const statusConfig: any = {
                                            "0": { label: "Draft", class: "bg-gray-100 text-gray-800" },
                                            "1": { label: "Published", class: "bg-green-100 text-green-800" },
                                            "2": { label: "Closed", class: "bg-red-100 text-red-800" },
                                            "draft": { label: "Draft", class: "bg-gray-100 text-gray-800" },
                                            "active": { label: "Published", class: "bg-green-100 text-green-800" },
                                            "closed": { label: "Closed", class: "bg-red-100 text-red-800" }
                                        };
                                        const statusInfo = statusConfig[jobposting.status] || { label: jobposting.status || '-', class: 'bg-gray-100 text-gray-800' };

                                        return (
                                            <Card key={jobposting.id} className="flex flex-col h-full hover:shadow-md transition-shadow duration-200">
                                                <div className="flex items-center gap-3 p-3 border-b bg-gray-50/50">
                                                    <div className="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                                        <MegaphoneIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <h3 className="font-semibold text-sm leading-tight">{jobposting.title}</h3>
                                                            {jobposting.is_featured && <Star className="h-4 w-4 text-yellow-500" />}
                                                        </div>
                                                        {auth.user?.permissions?.includes('view-job-postings') ? (
                                                            <p className="text-xs text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('recruitment.job-postings.show', jobposting.id))}>{jobposting.posting_code}</p>
                                                        ) : (
                                                            <p className="text-xs text-muted-foreground">{jobposting.posting_code}</p>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex-1 p-3 space-y-3">
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Type')}</p>
                                                            <p className="font-medium">{jobposting.jobType?.name || jobposting.job_type?.name || jobposting.jobtype?.name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Location')}</p>
                                                            <p className="font-medium">{jobposting.location?.name || '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Branch')}</p>
                                                            <p className="font-medium">{jobposting.branch_name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Salary')}</p>
                                                            <p className="font-medium">{jobposting.min_salary && jobposting.max_salary ? `${formatCurrency(jobposting.min_salary)} - ${formatCurrency(jobposting.max_salary)}` : '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Deadline')}</p>
                                                            <p className="font-medium">{jobposting.application_deadline ? formatDate(jobposting.application_deadline) : '-'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex justify-between items-center p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-xs ${statusInfo.class}`}>
                                                        {t(statusInfo.label)}
                                                    </span>
                                                    <div className="flex gap-2">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('publish-job-postings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => router.post(route('recruitment.job-postings.toggle-publish', jobposting.id))}
                                                                            className={`h-8 w-8 p-0 ${
                                                                                (jobposting.status === 'draft' || jobposting.status === '0')
                                                                                    ? 'text-green-600 hover:text-green-700'
                                                                                    : 'text-orange-600 hover:text-orange-700'
                                                                            }`}
                                                                        >
                                                                            {(jobposting.status === 'draft' || jobposting.status === '0') ? (
                                                                                <Upload className="h-4 w-4" />
                                                                            ) : (
                                                                                <Download className="h-4 w-4" />
                                                                            )}
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{(jobposting.status === 'draft' || jobposting.status === '0') ? t('Publish') : t('Unpublish')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('view-job-postings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.job-postings.show', jobposting.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-job-postings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', jobposting)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-job-postings') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(jobposting.id)}
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
                                        );
                                    })}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={MegaphoneIcon}
                                    title={t('No Job Postings found')}
                                    description={t('Get started by creating your first Job Posting.')}
                                    hasFilters={!!(filters.title || filters.description || (filters.job_type_id !== 'all' && filters.job_type_id) || (filters.location_id !== 'all' && filters.location_id) || (filters.branch_id !== 'all' && filters.branch_id) || (filters.status !== 'all' && filters.status))}
                                    onClearFilters={clearFilters}
                                    createPermission="create-job-postings"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Job Posting')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={jobpostings || { data: [], links: [], meta: {} }}
                        routeName="recruitment.job-postings.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditJobPosting
                        jobposting={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Job Posting')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
