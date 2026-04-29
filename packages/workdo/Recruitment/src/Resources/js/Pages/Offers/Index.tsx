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
import { Plus, Edit as EditIcon, Trash2, Eye, FileText as FileTextIcon, Download, FileImage, UserPlus, Mail, User } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { DatePicker } from '@/components/ui/date-picker';
import Create from './Create';
import EditOffer from './Edit';
import View from './View';
import NoRecordsFound from '@/components/no-records-found';
import { Offer, OffersIndexProps, OfferFilters, OfferModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { offers, auth, candidates, jobpostings, users } = usePage<OffersIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<OfferFilters>({
        position: urlParams.get('position') || '',
        candidate_id: urlParams.get('candidate_id') || 'all',
        start_date: urlParams.get('start_date') || '',
        expiration_date: urlParams.get('expiration_date') || '',
        status: urlParams.get('status') || '',
        approval_status: urlParams.get('approval_status') || 'all',
        offer_date: urlParams.get('offer_date') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<OfferModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Offer | null>(null);
    const [filteredJobs, setFilteredJobs] = useState(jobpostings || []);
    const [showFilters, setShowFilters] = useState(false);


    // Handle dependent dropdown for job filters
    useEffect(() => {
        if (filters.candidate_id && filters.candidate_id !== 'all') {
            // Fetch jobs for selected candidate
            fetch(route('recruitment.candidates.jobs', filters.candidate_id))
                .then(response => response.json())
                .then(data => {
                    setFilteredJobs(data);
                    // Clear job if it doesn't belong to selected candidate
                    if (filters.job_id && filters.job_id !== 'all') {
                        const jobExists = data.find((sub: any) => sub.id.toString() === filters.job_id);
                        if (!jobExists) {
                            setFilters(prev => ({ ...prev, job_id: 'all' }));
                        }
                    }
                })
                .catch(() => setFilteredJobs([]));
        } else {
            setFilteredJobs(jobpostings || []);
            setFilters(prev => ({ ...prev, job_id: 'all' }));
        }
    }, [filters.candidate_id]);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'recruitment.offers.destroy',
        defaultMessage: t('Are you sure you want to delete this offer?')
    });

    const handleFilter = () => {
        router.get(route('recruitment.offers.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('recruitment.offers.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            position: '',
            candidate_id: 'all',
            start_date: '',
            expiration_date: '',
            status: '',
            approval_status: 'all',
            offer_date: '',
        });
        router.get(route('recruitment.offers.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Offer | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleSendEmail = (offerId: number) => {
        router.post(route('recruitment.offers.send-email', offerId));
    };



    const tableColumns = [

        {
            key: 'candidate_id',
            header: t('Candidate'),
            sortable: true,
            render: (value: any, row: any) => {
                const firstName = row.candidate?.first_name || '';
                const lastName = row.candidate?.last_name || '';
                const candidateName = firstName || lastName ? `${firstName} ${lastName}`.trim() : '-';
                const jobTitle = row.job?.title || '';

                return (
                    <div>
                        <div className="font-medium">{candidateName}</div>
                        {jobTitle && <div className="text-xs text-gray-500">{jobTitle}</div>}
                    </div>
                );
            }
        },
        {
            key: 'position',
            header: t('Position'),
            sortable: true
        },
        {
            key: 'salary',
            header: t('Salary'),
            sortable: false,
            render: (value: number, row: any) => {
                const salary = value ? formatCurrency(value) : '-';
                const bonus = row.bonus ? formatCurrency(row.bonus) : null;

                return (
                    <div>
                        <div className="font-medium">{salary}</div>
                        {bonus && <div className="text-xs text-gray-500">+{bonus}</div>}
                    </div>
                );
            }
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'expiration_date',
            header: t('Expiration Date'),
            sortable: true,
            render: (value: string) => {
                if (!value) return '-';
                const today = new Date().toISOString().split('T')[0];
                const isExpired = value <= today;

                return (
                    <div className={isExpired ? 'text-red-600' : ''}>
                        <div>{formatDate(value)}</div>
                        {isExpired && <div className="text-xs text-red-500">expire</div>}
                    </div>
                );
            }
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const options: any = { "0": "Draft", "1": "Sent", "2": "Accepted", "3": "Negotiating", "4": "Declined", "5": "Expired" };
                const colors: any = {
                    "0": "bg-gray-100 text-gray-800",
                    "1": "bg-blue-100 text-blue-800",
                    "2": "bg-green-100 text-green-800",
                    "3": "bg-yellow-100 text-yellow-800",
                    "4": "bg-red-100 text-red-800",
                    "5": "bg-orange-100 text-orange-800"
                };
                const displayValue = options[value] || value || '-';
                const colorClass = colors[value] || "bg-gray-100 text-gray-800";
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${colorClass}`}>
                        {t(displayValue)}
                    </span>
                );
            }
        },
        {
            key: 'offer_date',
            header: t('Offer Date'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'approval_status',
            header: t('Approval Status'),
            sortable: false,
            render: (_: any, offer: Offer) => {
                const getApprovalStatusColor = (status: string) => {
                    switch (status) {
                        case 'approved': return 'bg-green-100 text-green-800';
                        case 'rejected': return 'bg-red-100 text-red-800';
                        default: return 'bg-yellow-100 text-yellow-800';
                    }
                };

                const getApprovalStatusText = (status: string) => {
                    switch (status) {
                        case 'approved': return t('Approved');
                        case 'rejected': return t('Rejected');
                        default: return t('Pending');
                    }
                };

                return (
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getApprovalStatusColor(offer.approval_status)}`}>
                        {getApprovalStatusText(offer.approval_status)}
                    </span>
                );
            }
        },
        {
            key: 'actions',
            header: t('Actions'),
            sortable: false,
            render: (_: any, offer: Offer) => (
                <div className="flex items-center gap-1 min-w-fit">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('send-offer-emails') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => handleSendEmail(offer.id)} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                        <Mail className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Send Email')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {offer.status === '2' && auth.user?.permissions?.includes('download-offer-letters') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => window.open(offer.download_url, '_blank')} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Download className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Download Offer Letter')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {offer.status === '2' && !Boolean(offer.converted_to_employee) && auth.user?.permissions?.includes('convert-offers-to-employees') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.offers.convert-to-employee', offer.id))} className="h-8 w-8 p-0 text-emerald-600 hover:text-emerald-700">
                                        <UserPlus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Convert to Employee')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {Boolean(offer.converted_to_employee) && offer.employee_id && auth.user?.permissions?.includes('view-offer-employees') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('hrm.employees.show', offer.employee_id))} className="h-8 w-8 p-0 text-indigo-600 hover:text-indigo-700">
                                        <User className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Employee Details')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}

                        {auth.user?.permissions?.includes('view-offers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(offer)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-offers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', offer)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-offers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(offer.id)}
                                        className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
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
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                { label: t('Offers') }
            ]}
            pageTitle={t('Manage Offers')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-offers') && (
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
            <Head title={t('Offers')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.position}
                                onChange={(value) => setFilters({ ...filters, position: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Offers...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="recruitment.offers.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="recruitment.offers.index"
                                filters={{ ...filters, view: viewMode }}
                            />

                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.candidate_id !== 'all' ? filters.candidate_id : '', filters.job_id !== 'all' ? filters.job_id : '', filters.status, filters.start_date, filters.expiration_date, filters.approval_status !== 'all' ? filters.approval_status : '', filters.offer_date].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Candidate')}</label>
                                <Select value={filters.candidate_id} onValueChange={(value) => setFilters({ ...filters, candidate_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Candidates')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Candidates')}</SelectItem>
                                        {candidates?.map((candidate: any) => (
                                            <SelectItem key={candidate.id} value={candidate.id.toString()}>
                                                {candidate.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Approval Status')}</label>
                                <Select value={filters.approval_status} onValueChange={(value) => setFilters({ ...filters, approval_status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Status')}</SelectItem>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Offer Date')}</label>
                                <DatePicker
                                    value={filters.offer_date}
                                    onChange={(value) => setFilters({ ...filters, offer_date: value })}
                                    placeholder={t('Select Offer Date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Start Date')}</label>
                                <DatePicker
                                    value={filters.start_date}
                                    onChange={(value) => setFilters({ ...filters, start_date: value })}
                                    placeholder={t('Select Start Date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Expiration Date')}</label>
                                <DatePicker
                                    value={filters.expiration_date}
                                    onChange={(value) => setFilters({ ...filters, expiration_date: value })}
                                    placeholder={t('Select Expiration Date')}
                                    minDate={filters.start_date}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">{t('Draft')}</SelectItem>
                                        <SelectItem value="1">{t('Sent')}</SelectItem>
                                        <SelectItem value="2">{t('Accepted')}</SelectItem>
                                        <SelectItem value="3">{t('Negotiating')}</SelectItem>
                                        <SelectItem value="4">{t('Declined')}</SelectItem>
                                        <SelectItem value="5">{t('Expired')}</SelectItem>
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
                                    data={offers?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={FileTextIcon}
                                            title={t('No Offers found')}
                                            description={t('Get started by creating your first Offer.')}
                                            hasFilters={!!(filters.position || (filters.candidate_id !== 'all' && filters.candidate_id) || filters.status || (filters.approval_status !== 'all' && filters.approval_status) || filters.offer_date || filters.start_date || filters.expiration_date)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-offers"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Offer')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {offers?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {offers?.data?.map((offer) => {
                                        const firstName = offer.candidate?.first_name || '';
                                        const lastName = offer.candidate?.last_name || '';
                                        const fullName = firstName && lastName ? `${firstName} ${lastName}` : (firstName || lastName || '-');
                                        const statusOptions: any = { "0": "Draft", "1": "Sent", "2": "Accepted", "3": "Negotiating", "4": "Declined", "5": "Expired" };
                                        const statusColors: any = { "0": "bg-gray-100 text-gray-800", "1": "bg-blue-100 text-blue-800", "2": "bg-green-100 text-green-800", "3": "bg-yellow-100 text-yellow-800", "4": "bg-red-100 text-red-800", "5": "bg-orange-100 text-orange-800" };
                                        const statusInfo = { label: statusOptions[offer.status] || 'Draft', class: statusColors[offer.status] || 'bg-gray-100 text-gray-800' };
                                        const isExpired = offer.expiration_date && offer.expiration_date <= new Date().toISOString().split('T')[0];

                                        return (
                                            <Card key={offer.id} className="flex flex-col h-full hover:shadow-md transition-shadow duration-200">
                                                <div className="flex items-center gap-3 p-3 border-b bg-gray-50/50">
                                                    <div className="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                                        <FileTextIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm leading-tight">{fullName}</h3>
                                                        <p className="text-xs text-muted-foreground">{offer.job?.title || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="flex-1 p-3 space-y-3">
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Position')}</p>
                                                            <p className="font-medium">{offer.position || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Salary')}</p>
                                                            <p className="font-medium">{offer.salary ? formatCurrency(offer.salary) : '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Start Date')}</p>
                                                            <p className="font-medium">{offer.start_date ? formatDate(offer.start_date) : '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Expiration')}</p>
                                                            <p className={`font-medium ${isExpired ? 'text-red-600' : ''}`}>{offer.expiration_date ? formatDate(offer.expiration_date) : '-'}</p>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Offer Date')}</p>
                                                            <p className="font-medium">{offer.offer_date ? formatDate(offer.offer_date) : '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                            <span className={`px-2 py-1 rounded-full text-xs ${statusInfo.class}`}>
                                                                {t(statusInfo.label)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Department')}</p>
                                                        <p className="font-medium">{offer.department?.department_name || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="flex justify-end items-center p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <div className="flex flex-wrap gap-1 sm:gap-2">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('send-offer-emails') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => handleSendEmail(offer.id)} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                                                            <Mail className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Send Email')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {offer.status === '2' && auth.user?.permissions?.includes('download-offer-letters') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => window.open(offer.download_url, '_blank')} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                                                            <Download className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Download Offer Letter')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {offer.status === '2' && !Boolean(offer.converted_to_employee) && auth.user?.permissions?.includes('convert-offers-to-employees') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('recruitment.offers.convert-to-employee', offer.id))} className="h-8 w-8 p-0 text-emerald-600 hover:text-emerald-700">
                                                                            <UserPlus className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Convert to Employee')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {Boolean(offer.converted_to_employee) && offer.employee_id && auth.user?.permissions?.includes('view-offer-employees') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('hrm.employees.show', offer.employee_id))} className="h-8 w-8 p-0 text-indigo-600 hover:text-indigo-700">
                                                                            <User className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Employee Details')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('view-offers') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => setViewingItem(offer)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-offers') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', offer)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-offers') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(offer.id)}
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
                                    icon={FileTextIcon}
                                    title={t('No Offers found')}
                                    description={t('Get started by creating your first Offer.')}
                                    hasFilters={!!(filters.position || (filters.candidate_id !== 'all' && filters.candidate_id) || filters.status || (filters.approval_status !== 'all' && filters.approval_status) || filters.offer_date || filters.start_date || filters.expiration_date)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-offers"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Offer')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={offers || { data: [], links: [], meta: {} }}
                        routeName="recruitment.offers.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditOffer
                        offer={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View offer={viewingItem} onClose={() => setViewingItem(null)} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Offer')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}