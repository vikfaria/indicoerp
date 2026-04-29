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
import { Plus, Edit as EditIcon, Trash2, Eye, Users as UsersIcon, Download, FileImage, ChevronDown } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditCandidate from './Edit';
import { usePageButtons } from '@/hooks/usePageButtons';

import NoRecordsFound from '@/components/no-records-found';
import { Candidate, CandidatesIndexProps, CandidateFilters, CandidateModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { candidates, auth, jobpostings, candidatesources } = usePage<CandidatesIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<CandidateFilters>({
        name: urlParams.get('name') || '',
        job_id: urlParams.get('job_id') || 'all',
        source_id: urlParams.get('source_id') || 'all',
        status: urlParams.get('status') || '',
        application_date: urlParams.get('application_date') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<CandidateModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [showFilters, setShowFilters] = useState(false);

    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Job Application', settingKey: 'Dropbox Job Application' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'recruitment.candidates.destroy',
        defaultMessage: t('Are you sure you want to delete this candidate?')
    });

    const handleFilter = () => {
        router.get(route('recruitment.candidates.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('recruitment.candidates.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const updateStatus = (candidateId: number, newStatus: string) => {
        router.patch(route('recruitment.candidates.update-status', candidateId), {
            status: newStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Status updated successfully
            }
        });
    };

    const clearFilters = () => {
        setFilters({
            name: '',
            job_id: 'all',
            source_id: 'all',
            status: '',
            application_date: '',
        });
        router.get(route('recruitment.candidates.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Candidate | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'tracking_id',
            header: t('Tracking ID'),
            sortable: true,
            render: (value: string, candidate: Candidate) =>
                auth.user?.permissions?.includes('view-candidates') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('recruitment.candidates.show', candidate.id))}>{value || '-'}</span>
                ) : (
                    value || '-'
                )
        },
        {
            key: 'name',
            header: t('Name'),
            sortable: true,
            render: (value: any, row: any) => `${row.first_name || ''} ${row.last_name || ''}`.trim() || '-'
        },
        {
            key: 'email',
            header: t('Email'),
            sortable: true
        },
        {
            key: 'job_posting_title',
            header: t('Job'),
            sortable: false,
            render: (value: any, row: any) => row.job_posting?.title || '-'
        },
        {
            key: 'candidate_source_name',
            header: t('Source'),
            sortable: false,
            render: (value: any, row: any) => row.candidate_source?.name || '-'
        },
        {
            key: 'application_date',
            header: t('Application Date'),
            sortable: true,
            render: (value: any) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: any, row: any) => {
                const statusOptions = { "0": "New", "1": "Screening", "2": "Interview", "3": "Offer", "4": "Hired", "5": "Rejected" };
                const statusColors = { "0": "bg-blue-100 text-blue-800", "1": "bg-yellow-100 text-yellow-800", "2": "bg-purple-100 text-purple-800", "3": "bg-orange-100 text-orange-800", "4": "bg-green-100 text-green-800", "5": "bg-red-100 text-red-800" };
                const displayValue = statusOptions[value] || value || '-';
                const colorClass = statusColors[value] || 'bg-gray-100 text-gray-800';

                if (auth.user?.permissions?.includes('edit-candidates')) {
                    return (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className={`px-2 py-1 rounded-full text-sm h-auto ${colorClass}`}>
                                    {t(displayValue)} <ChevronDown className="h-3 w-3 ml-1" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '0')}>
                                    {t('New')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '1')}>
                                    {t('Screening')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '2')}>
                                    {t('Interview')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '3')}>
                                    {t('Offer')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '4')}>
                                    {t('Hired')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => updateStatus(row.id, '5')}>
                                    {t('Rejected')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                }

                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${colorClass}`}>
                        {t(displayValue)}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-candidates', 'edit-candidates', 'delete-candidates'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, candidate: Candidate) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-candidates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.candidates.show', candidate.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-candidates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', candidate)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-candidates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(candidate.id)}
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
                { label: t('Candidates') }
            ]}
            pageTitle={t('Manage Candidates')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {dropboxBtn.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {auth.user?.permissions?.includes('create-candidates') && (
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
            <Head title={t('Candidates')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({ ...filters, name: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Candidates...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="recruitment.candidates.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="recruitment.candidates.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.job_id !== 'all' ? filters.job_id : '', filters.source_id !== 'all' ? filters.source_id : '', filters.status, filters.application_date].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Job')}</label>
                                <Select value={filters.job_id} onValueChange={(value) => setFilters({ ...filters, job_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Job')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Job')}</SelectItem>
                                        {jobpostings?.map((jobPosting: any) => (
                                            <SelectItem key={jobPosting.id} value={jobPosting.id.toString()}>
                                                {jobPosting.title}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Source')}</label>
                                <Select value={filters.source_id} onValueChange={(value) => setFilters({ ...filters, source_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Source')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Source')}</SelectItem>
                                        {candidatesources?.map((candidateSource: any) => (
                                            <SelectItem key={candidateSource.id} value={candidateSource.id.toString()}>
                                                {candidateSource.name}
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
                                        <SelectItem value="0">{t('New')}</SelectItem>
                                        <SelectItem value="1">{t('Screening')}</SelectItem>
                                        <SelectItem value="2">{t('Interview')}</SelectItem>
                                        <SelectItem value="3">{t('Offer')}</SelectItem>
                                        <SelectItem value="4">{t('Hired')}</SelectItem>
                                        <SelectItem value="5">{t('Rejected')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Application Date')}</label>
                                <DatePicker
                                    value={filters.application_date}
                                    onChange={(date) => setFilters({ ...filters, application_date: date })}
                                    placeholder={t('Select Application Date')}
                                />
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
                                    data={candidates?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={UsersIcon}
                                            title={t('No Candidates found')}
                                            description={t('Get started by creating your first Candidate.')}
                                            hasFilters={!!(filters.name || (filters.job_id !== 'all' && filters.job_id) || (filters.source_id !== 'all' && filters.source_id) || filters.status || filters.application_date)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-candidates"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Candidate')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {candidates?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {candidates?.data?.map((candidate) => {
                                        const statusOptions: any = { "0": "New", "1": "Screening", "2": "Interview", "3": "Offer", "4": "Hired", "5": "Rejected" };
                                        const statusColors: any = { "0": "bg-blue-100 text-blue-800", "1": "bg-yellow-100 text-yellow-800", "2": "bg-purple-100 text-purple-800", "3": "bg-orange-100 text-orange-800", "4": "bg-green-100 text-green-800", "5": "bg-red-100 text-red-800" };
                                        const statusInfo = { label: statusOptions[candidate.status] || candidate.status || '-', class: statusColors[candidate.status] || 'bg-gray-100 text-gray-800' };

                                        return (
                                            <Card key={candidate.id} className="flex flex-col h-full hover:shadow-md transition-shadow duration-200">
                                                <div className="flex items-center gap-3 p-3 border-b bg-gray-50/50">
                                                    <div className="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                                        <UsersIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm leading-tight">{`${candidate.first_name || ''} ${candidate.last_name || ''}`.trim() || '-'}</h3>
                                                        {auth.user?.permissions?.includes('view-candidates') ? (
                                                            <p className="text-xs text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('recruitment.candidates.show', candidate.id))}>#{candidate.tracking_id || candidate.id}</p>
                                                        ) : (
                                                            <p className="text-xs text-muted-foreground">#{candidate.tracking_id || candidate.id}</p>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex-1 p-3 space-y-3">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Email')}</p>
                                                        <p className="font-medium">{candidate.email || '-'}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Job')}</p>
                                                            <p className="font-medium">{candidate.job_posting?.title || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Source')}</p>
                                                            <p className="font-medium">{candidate.candidate_source?.name || '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Application Date')}</p>
                                                        <p className="font-medium">{candidate.application_date ? formatDate(candidate.application_date) : '-'}</p>
                                                    </div>
                                                </div>
                                                <div className="flex justify-between items-center p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-xs ${statusInfo.class}`}>
                                                        {t(statusInfo.label)}
                                                    </span>
                                                    <div className="flex gap-2">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('view-candidates') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.candidates.show', candidate.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-candidates') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', candidate)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-candidates') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(candidate.id)}
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
                                    icon={UsersIcon}
                                    title={t('No Candidates found')}
                                    description={t('Get started by creating your first Candidate.')}
                                    hasFilters={!!(filters.name || (filters.job_id !== 'all' && filters.job_id) || (filters.source_id !== 'all' && filters.source_id) || filters.status || filters.application_date)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-candidates"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Candidate')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={candidates || { data: [], links: [], meta: {} }}
                        routeName="recruitment.candidates.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditCandidate
                        candidate={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Candidate')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}