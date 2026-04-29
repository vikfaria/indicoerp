import { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import { formatDate } from '@/utils/helpers';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, FileText, Play, Eye } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog } from '@/components/ui/dialog';
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';
import EditEmployeeReview from './Edit';
import Show from './Show';

interface EmployeeReview {
    id: number;
    user: { name: string };
    reviewer: { name: string };
    review_cycle: { name: string };
    review_date: string;
    status: string;
    average_rating: number | null;
    created_at: string;
}

interface EmployeeReviewFilters {
    employee_name: string;
    reviewer_name: string;
    review_cycle_id: string;
    status: string;
}

interface EmployeeReviewModalState {
    isOpen: boolean;
    mode: string;
    data: EmployeeReview | null;
}

interface ShowModalState {
    isOpen: boolean;
    data: EmployeeReview | null;
    performanceIndicators: any;
    averageRating: number | null;
}

const EmployeeReviewActionButtons = ({ review, auth, t, openModal, openDeleteDialog, openShowModal, router, isGrid = false }: any) => {
    const { additionalButtons } = usePageButtons('employee-reviews-actions', { review, auth, t, openModal, openDeleteDialog, openShowModal, router });
    const buttonClass = isGrid ? "h-9 w-9 p-0" : "h-8 w-8 p-0";

    return (
        <>
            {auth.user?.permissions?.includes('conduct-employee-reviews') && (
                <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                        <Button variant="ghost" size="sm" onClick={() => router.get(route('performance.employee-reviews.conduct', review.id))} className={`${buttonClass} text-purple-600 hover:text-purple-700 ${isGrid ? 'hover:bg-purple-50' : ''}`}>
                            <Play className="h-4 w-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent><p>{t('Conduct Review')}</p></TooltipContent>
                </Tooltip>
            )}
            {auth.user?.permissions?.includes('view-employee-reviews') && (
                <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                        <Button variant="ghost" size="sm" onClick={() => openShowModal(review)} className={`${buttonClass} text-green-600 hover:text-green-700 ${isGrid ? 'hover:bg-green-50' : ''}`}>
                            <Eye className="h-4 w-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent><p>{t('View')}</p></TooltipContent>
                </Tooltip>
            )}
            {auth.user?.permissions?.includes('edit-employee-reviews') && (
                <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', review)} className={`${buttonClass} text-blue-600 hover:text-blue-700 ${isGrid ? 'hover:bg-blue-50' : ''}`}>
                            <Edit className="h-4 w-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                </Tooltip>
            )}
            {auth.user?.permissions?.includes('delete-employee-reviews') && (
                <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openDeleteDialog(review.id)}
                            className={`${buttonClass} ${isGrid ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : 'text-destructive hover:text-destructive'}`}
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                </Tooltip>
            )}
            {additionalButtons}
        </>
    );
};

export default function Index() {
    const { t } = useTranslation();
    const { employeeReviews, employees = [], reviewers = [], reviewCycles = [], auth } = usePage<{ 
        employeeReviews: any; 
        employees: any[]; 
        reviewers: any[]; 
        reviewCycles: any[]; 
        auth: any 
    }>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), [window.location.search]);

    const [filters, setFilters] = useState<EmployeeReviewFilters>({
        employee_name: urlParams.get('employee_name') || '',
        reviewer_name: urlParams.get('reviewer_name') || '',
        review_cycle_id: urlParams.get('review_cycle_id') || '',
        status: urlParams.get('status') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState<EmployeeReviewModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    
    const [showModalState, setShowModalState] = useState<ShowModalState>({
        isOpen: false,
        data: null,
        performanceIndicators: {},
        averageRating: null
    });


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'performance.employee-reviews.destroy',
        defaultMessage: t('Are you sure you want to delete this employee review?')
    });

    const handleFilter = () => {
        router.get(route('performance.employee-reviews.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('performance.employee-reviews.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ employee_name: '', reviewer_name: '', review_cycle_id: '', status: '' });
        router.get(route('performance.employee-reviews.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: EmployeeReview | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };
    
    const openShowModal = async (review: EmployeeReview) => {
        try {
            const response = await fetch(route('performance.employee-reviews.show', review.id), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            setShowModalState({
                isOpen: true,
                data: data.props.employeeReview,
                performanceIndicators: data.props.performanceIndicators,
                averageRating: data.props.averageRating
            });
        } catch (error) {
            console.error('Error fetching review details:', error);
        }
    };
    
    const closeShowModal = () => {
        setShowModalState({ isOpen: false, data: null, performanceIndicators: {}, averageRating: null });
    };

    const getStatusLabel = (status: string) => {
        const labels: { [key: string]: string } = {
            pending: t('Pending'),
            in_progress: t('In Progress'),
            completed: t('Completed'),
            cancelled: t('Cancelled')
        };
        return labels[status] || status;
    };

    const getStatusColor = (status: string) => {
        const colors: { [key: string]: string } = {
            pending: 'bg-yellow-100 text-yellow-800',
            in_progress: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusColorGrid = (status: string) => {
        const colors: { [key: string]: string } = {
            pending: 'bg-yellow-100 text-yellow-700',
            in_progress: 'bg-blue-100 text-blue-700',
            completed: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700'
        };
        return colors[status] || 'bg-gray-100 text-gray-700';
    };



    const tableColumns = [
        {
            key: 'user_id',
            header: t('Employee'),
            sortable: true,
            render: (value: any, item: EmployeeReview) => {
                return item.user?.name || '-';
            }
        },
        {
            key: 'reviewer_id',
            header: t('Reviewer'),
            sortable: true,
            render: (value: any, item: EmployeeReview) => item.reviewer?.name || '-'
        },
        {
            key: 'review_cycle_id',
            header: t('Review Cycle'),
            sortable: true,
            render: (value: any, item: EmployeeReview) => item.review_cycle?.name || '-'
        },
        {
            key: 'review_date',
            header: t('Review Date'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },


        {
            key: 'average_rating',
            header: t('Rating'),
            render: (value: number | null) => {
                if (!value) return '-';
                const rating = Math.round(value * 10) / 10;
                const fullStars = Math.floor(rating);
                const hasHalfStar = rating % 1 >= 0.5;
                
                return (
                    <div className="flex items-center gap-1">
                        <div className="flex">
                            {[...Array(5)].map((_, i) => (
                                <span key={i} className={`text-lg ${
                                    i < fullStars ? 'text-yellow-500' : 
                                    i === fullStars && hasHalfStar ? 'text-yellow-300' : 'text-gray-300'
                                }`}>
                                    {i < fullStars ? '★' : i === fullStars && hasHalfStar ? '★' : '☆'}
                                </span>
                            ))}
                        </div>
                        <span className="text-sm text-gray-600">({rating})</span>
                    </div>
                );
            }
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(value)}`}>
                    {getStatusLabel(value)}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['manage-employee-reviews', 'manage-any-employee-reviews', 'manage-own-employee-reviews'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: EmployeeReview) => (
                <div className="flex gap-1">
                    <EmployeeReviewActionButtons 
                        review={item} 
                        auth={auth} 
                        t={t} 
                        openModal={openModal} 
                        openDeleteDialog={openDeleteDialog} 
                        openShowModal={openShowModal} 
                        router={router} 
                    />
                </div>
            )
        }] : [])
    ];

    return (
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    { label: t('Performance')},
                    { label: t('Employee Reviews') }
                ]}
                pageTitle={t('Manage Employee Reviews')}
                pageActions={
                    <div className="flex gap-2">
                        {auth.user?.permissions?.includes('create-employee-reviews') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => openModal('add')}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent><p>{t('Create')}</p></TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                }
            >
                <Head title={t('Employee Reviews')} />

                <Card className="shadow-sm">
                    <CardContent className="p-6 border-b bg-gray-50/50">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 max-w-md">
                                <SearchInput
                                    value={filters.employee_name}
                                    onChange={(value) => setFilters({ ...filters, employee_name: value })}
                                    onSearch={handleFilter}
                                    placeholder={t('Search by employee name...')}
                                />
                            </div>
                            <div className="flex items-center gap-3">
                                <ListGridToggle
                                    currentView={viewMode}
                                    routeName="performance.employee-reviews.index"
                                    filters={{ ...filters, per_page: perPage }}
                                />
                                <PerPageSelector
                                    routeName="performance.employee-reviews.index"
                                    filters={{ ...filters, view: viewMode }}
                                />
                                <div className="relative">
                                    <FilterButton
                                        showFilters={showFilters}
                                        onToggle={() => setShowFilters(!showFilters)}
                                    />
                                    {(() => {
                                        const activeFilters = [filters.reviewer_name, filters.review_cycle_id, filters.status].filter(Boolean).length;
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
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Reviewer')}</label>
                                    <Select value={filters.reviewer_name} onValueChange={(value) => setFilters({ ...filters, reviewer_name: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Reviewers')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {reviewers?.map((reviewer) => (
                                                <SelectItem key={reviewer.id} value={reviewer.name}>
                                                    {reviewer.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Review Cycle')}</label>
                                    <Select value={filters.review_cycle_id} onValueChange={(value) => setFilters({ ...filters, review_cycle_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Cycles')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {reviewCycles?.map((cycle) => (
                                                <SelectItem key={cycle.id} value={cycle.id.toString()}>
                                                    {cycle.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                    <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('All Status')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                                            <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                            <SelectItem value="completed">{t('Completed')}</SelectItem>
                                            <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
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

                    <CardContent className="p-0">
                        {viewMode === 'list' ? (
                            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                                <div className="min-w-[800px]">
                                    <DataTable
                                        data={employeeReviews?.data || []}
                                        columns={tableColumns}
                                        onSort={handleSort}
                                        sortKey={sortField}
                                        sortDirection={sortDirection as 'asc' | 'desc'}
                                        className="rounded-none"
                                        emptyState={
                                            <NoRecordsFound
                                                icon={FileText}
                                                title={t('No employee reviews found')}
                                                description={t('Get started by creating your first employee review.')}
                                                hasFilters={!!(filters.employee_name || filters.reviewer_name || filters.review_cycle_id || filters.status)}
                                                onClearFilters={clearFilters}
                                                createPermission="create-employee-reviews"
                                                onCreateClick={() => openModal('add')}
                                                createButtonText={t('Create Employee Review')}
                                                className="h-auto"
                                            />
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-auto max-h-[70vh] p-6">
                                {employeeReviews?.data?.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                        {employeeReviews.data.map((review: EmployeeReview) => (
                                            <Card key={review.id} className="p-0 hover:shadow-lg transition-all duration-200 flex flex-col h-full min-w-0">
                                                {/* Header */}
                                                <div className="p-4 border-b flex-shrink-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-primary/10 rounded-lg">
                                                            <FileText className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-semibold text-sm text-gray-900">{review.user?.name || 'N/A'}</h3>
                                                            <p className="text-xs text-gray-500 truncate">{review.review_cycle?.name || '-'}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Body */}
                                                <div className="p-4 flex-1 min-h-0">
                                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Cycle')}</p>
                                                            <p className="font-medium text-xs">{review.review_cycle?.name || '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Reviewer')}</p>
                                                            <p className="font-medium text-xs">{review.reviewer?.name || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Date')}</p>
                                                            <p className="font-medium text-xs">{review.review_date ? formatDate(review.review_date) : '-'}</p>
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Rating')}</p>
                                                            {review.average_rating ? (
                                                                <div className="flex items-center gap-1">
                                                                    <div className="flex">
                                                                        {[...Array(5)].map((_, i) => {
                                                                            const rating = Math.round(review.average_rating! * 10) / 10;
                                                                            const fullStars = Math.floor(rating);
                                                                            const hasHalfStar = rating % 1 >= 0.5;
                                                                            return (
                                                                                <span key={i} className={`text-sm ${
                                                                                    i < fullStars ? 'text-yellow-500' : 
                                                                                    i === fullStars && hasHalfStar ? 'text-yellow-300' : 'text-gray-300'
                                                                                }`}>
                                                                                    {i < fullStars ? '★' : i === fullStars && hasHalfStar ? '★' : '☆'}
                                                                                </span>
                                                                            );
                                                                        })}
                                                                    </div>
                                                                    <span className="text-xs text-gray-600 font-medium">({Math.round(review.average_rating * 10) / 10})</span>
                                                                </div>
                                                            ) : (
                                                                <p className="font-medium text-xs">-</p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Actions Footer */}
                                                <div className="flex justify-between items-center gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(review.status)}`}>
                                                        {getStatusLabel(review.status)}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        <TooltipProvider>
                                                            {(() => {
                                                                const { additionalButtons } = usePageButtons('employee-reviews-actions', { review, auth, t, openModal, openDeleteDialog, openShowModal, router });
                                                                return additionalButtons;
                                                            })()}
                                                            {auth.user?.permissions?.includes('conduct-employee-reviews') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => router.get(route('performance.employee-reviews.conduct', review.id))} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                                                            <Play className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Conduct Review')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('view-employee-reviews') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openShowModal(review)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-employee-reviews') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', review)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <Edit className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-employee-reviews') && (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(review.id)}
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
                                        ))}
                                    </div>
                                ) : (
                                    <NoRecordsFound
                                        icon={FileText}
                                        title={t('No employee reviews found')}
                                        description={t('Get started by creating your first employee review.')}
                                        hasFilters={!!(filters.employee_name || filters.reviewer_name || filters.review_cycle_id || filters.status)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-employee-reviews"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Employee Review')}
                                        className="h-auto"
                                    />
                                )}
                            </div>
                        )}
                    </CardContent>

                    <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                        <Pagination
                            data={employeeReviews || { data: [], links: [], meta: {} }}
                            routeName="performance.employee-reviews.index"
                            filters={{ ...filters, per_page: perPage, view: viewMode }}
                        />
                    </CardContent>
                </Card>

                <ConfirmationDialog
                    open={deleteState.isOpen}
                    onOpenChange={closeDeleteDialog}
                    title={t('Delete Employee Review')}
                    message={deleteState.message}
                    confirmText={t('Delete')}
                    onConfirm={confirmDelete}
                    variant="destructive"
                />

                <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                    {modalState.mode === 'add' && (
                        <Create 
                            onSuccess={closeModal} 
                            employees={employees}
                            reviewers={reviewers}
                            reviewCycles={reviewCycles}
                        />
                    )}
                    {modalState.mode === 'edit' && modalState.data && (
                        <EditEmployeeReview 
                            employeeReview={modalState.data} 
                            onSuccess={closeModal}
                            employees={employees}
                            reviewers={reviewers}
                            reviewCycles={reviewCycles}
                        />
                    )}
                </Dialog>
                
                <Dialog open={showModalState.isOpen} onOpenChange={closeShowModal}>
                    {showModalState.data && (
                        <Show 
                            employeeReview={showModalState.data}
                            performanceIndicators={showModalState.performanceIndicators}
                            averageRating={showModalState.averageRating}
                        />
                    )}
                </Dialog>
            </AuthenticatedLayout>
        </TooltipProvider>
    );
}