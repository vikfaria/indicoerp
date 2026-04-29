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
import { Plus, Edit as EditIcon, Trash2, Eye, MessageSquare as MessageSquareIcon, Download, FileImage, Star } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import Edit from './Edit';
import View from './View';
import NoRecordsFound from '@/components/no-records-found';
import { InterviewFeedback, InterviewFeedbacksIndexProps, InterviewFeedbackFilters, InterviewFeedbackModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { interviewfeedbacks, auth, interviews, users } = usePage<InterviewFeedbacksIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<InterviewFeedbackFilters>({
        strengths: urlParams.get('strengths') || '',
        weaknesses: urlParams.get('weaknesses') || '',
        comments: urlParams.get('comments') || '',
        interview_id: urlParams.get('interview_id') || 'all',
        interviewer_id: urlParams.get('interviewer_id') || 'all',
        recommendation: urlParams.get('recommendation') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<InterviewFeedbackModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<InterviewFeedback | null>(null);
    const [filteredInterviewers, setFilteredInterviewers] = useState(users || []);
    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'recruitment.interview-feedbacks.destroy',
        defaultMessage: t('Are you sure you want to delete this interview feedback?')
    });

    const handleFilter = () => {
        router.get(route('recruitment.interview-feedbacks.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('recruitment.interview-feedbacks.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            strengths: '',
            weaknesses: '',
            comments: '',
            interview_id: 'all',
            interviewer_id: 'all',
            recommendation: '',
        });
        router.get(route('recruitment.interview-feedbacks.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: InterviewFeedback | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'candidate',
            header: t('Candidate'),
            sortable: true,
            render: (value: any, row: any) => (
                <div>
                    <div className="font-medium">
                        {(row.interview?.candidate?.first_name && row.interview?.candidate?.last_name)
                            ? `${row.interview.candidate.first_name} ${row.interview.candidate.last_name}`
                            : '-'
                        }
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {row.interview?.job_posting?.title || '-'}
                    </div>
                </div>
            )
        },
        {
            key: 'interviewer_names',
            header: t('Interviewer'),
            sortable: true,
            render: (value: any, row: any) => row.interviewer_names || '-'
        },
        {
            key: 'created_at',
            header: t('Submitted Date'),
            sortable: true,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'overall_rating',
            header: t('Overall Rating'),
            sortable: false,
            render: (value: number) => {
                if (!value) return '-';
                return (
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-small">{value}/5</span>
                        <div className="flex items-center gap-1">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <Star
                                    key={star}
                                    className={`h-4 w-4 ${star <= value
                                            ? 'text-yellow-400 fill-yellow-400'
                                            : 'text-gray-300'
                                        }`}
                                />
                            ))}
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'recommendation',
            header: t('Recommendation'),
            sortable: false,
            render: (value: string) => {
                const options: any = { "0": "Strong Hire", "1": "Hire", "2": "Maybe", "3": "Reject", "4": "Strong Reject" };
                const displayValue = options[value] || value || '-';
                const getBadgeColor = (val: string) => {
                    switch (val) {
                        case '0': return 'bg-green-100 text-green-800'; // Strong Hire
                        case '1': return 'bg-blue-100 text-blue-800';   // Hire
                        case '2': return 'bg-yellow-100 text-yellow-800'; // Maybe
                        case '3': case '4': return 'bg-red-100 text-red-800'; // Reject & Strong Reject
                        default: return 'bg-gray-100 text-gray-800';
                    }
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${getBadgeColor(value)}`}>
                        {t(displayValue)}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-interview-feedbacks', 'edit-interview-feedbacks', 'delete-interview-feedbacks'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, interviewfeedback: InterviewFeedback) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-interview-feedbacks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(interviewfeedback)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-interview-feedbacks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', interviewfeedback)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-interview-feedbacks') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(interviewfeedback.id)}
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
                { label: t('Interview Feedback') }
            ]}
            pageTitle={t('Manage Interview Feedback')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-interview-feedbacks') && (
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
            <Head title={t('Interview Feedback')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.strengths}
                                onChange={(value) => setFilters({ ...filters, strengths: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Interview Feedback...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="recruitment.interview-feedbacks.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="recruitment.interview-feedbacks.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.interview_id !== 'all' ? filters.interview_id : '', filters.interviewer_id !== 'all' ? filters.interviewer_id : '', filters.recommendation].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Interview')}</label>
                                <Select value={filters.interview_id} onValueChange={(value) => setFilters({ ...filters, interview_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Interviews')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Interviews')}</SelectItem>
                                        {interviews?.map((interview: any) => (
                                            <SelectItem key={interview.id} value={interview.id.toString()}>
                                                {interview.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Interviewer')}</label>
                                <Select value={filters.interviewer_id} onValueChange={(value) => setFilters({ ...filters, interviewer_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Interviewers')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Interviewers')}</SelectItem>
                                        {users?.map((interviewer: any) => (
                                            <SelectItem key={interviewer.id} value={interviewer.id.toString()}>
                                                {interviewer.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Recommendation')}</label>
                                <Select value={filters.recommendation} onValueChange={(value) => setFilters({ ...filters, recommendation: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Recommendation')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">{t('Strong Hire')}</SelectItem>
                                        <SelectItem value="1">{t('Hire')}</SelectItem>
                                        <SelectItem value="2">{t('Maybe')}</SelectItem>
                                        <SelectItem value="3">{t('Reject')}</SelectItem>
                                        <SelectItem value="4">{t('Strong Reject')}</SelectItem>
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
                                    data={interviewfeedbacks?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={MessageSquareIcon}
                                            title={t('No Interview Feedback found')}
                                            description={t('Get started by creating your first Interview Feedback.')}
                                            hasFilters={!!(filters.strengths || filters.weaknesses || filters.comments || (filters.interview_id !== 'all' && filters.interview_id) || (filters.interviewer_id !== 'all' && filters.interviewer_id) || filters.recommendation)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-interview-feedbacks"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Interview Feedback')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {interviewfeedbacks?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {interviewfeedbacks?.data?.map((interviewfeedback) => {
                                        const options: any = { "0": "Strong Hire", "1": "Hire", "2": "Maybe", "3": "Reject", "4": "Strong Reject" };
                                        const recommendationColors: any = { "0": "bg-green-100 text-green-800", "1": "bg-blue-100 text-blue-800", "2": "bg-yellow-100 text-yellow-800", "3": "bg-red-100 text-red-800", "4": "bg-red-100 text-red-800" };
                                        const recommendationInfo = { label: options[interviewfeedback.recommendation] || 'No Recommendation', class: recommendationColors[interviewfeedback.recommendation] || 'bg-gray-100 text-gray-800' };

                                        return (
                                            <Card key={interviewfeedback.id} className="flex flex-col h-full hover:shadow-md transition-shadow duration-200">
                                                <div className="flex items-center gap-3 p-3 border-b bg-gray-50/50">
                                                    <div className="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                                        <MessageSquareIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm leading-tight">
                                                            {(interviewfeedback.interview?.candidate?.first_name && interviewfeedback.interview?.candidate?.last_name)
                                                                ? `${interviewfeedback.interview.candidate.first_name} ${interviewfeedback.interview.candidate.last_name}`
                                                                : 'Unknown Candidate'
                                                            }
                                                        </h3>
                                                        <p className="text-xs text-muted-foreground">{interviewfeedback.interview?.job_posting?.title || 'No Job Title'}</p>
                                                    </div>
                                                </div>
                                                <div className="flex-1 p-3 space-y-3">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Interviewer')}</p>
                                                        <p className="font-medium">{interviewfeedback.interviewer_names || 'Unknown'}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Overall Rating')}</p>
                                                            {interviewfeedback.overall_rating ? (
                                                                <div className="flex items-center gap-2">
                                                                    <div className="flex">
                                                                        {[1, 2, 3, 4, 5].map((star) => (
                                                                            <Star
                                                                                key={star}
                                                                                className={`h-4 w-4 ${star <= interviewfeedback.overall_rating
                                                                                        ? 'text-yellow-400 fill-yellow-400'
                                                                                        : 'text-gray-300'
                                                                                    }`}
                                                                            />
                                                                        ))}
                                                                    </div>
                                                                    <span className="text-sm font-medium">{interviewfeedback.overall_rating}/5</span>
                                                                </div>
                                                            ) : (
                                                                <span className="text-sm text-gray-400">{t('No rating')}</span>
                                                            )}
                                                        </div>
                                                        <div className="text-xs min-w-0">
                                                            <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Date')}</p>
                                                            <p className="font-medium">{interviewfeedback.created_at ? formatDate(interviewfeedback.created_at) : '-'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex justify-between items-center p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                    <span className={`px-2 py-1 rounded-full text-xs ${recommendationInfo.class}`}>
                                                        {t(recommendationInfo.label)}
                                                    </span>
                                                    <div className="flex gap-2">
                                                        <TooltipProvider>
                                                            {auth.user?.permissions?.includes('view-interview-feedbacks') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => setViewingItem(interviewfeedback)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                            <Eye className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('View')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('edit-interview-feedbacks') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', interviewfeedback)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                            <EditIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Edit')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                            {auth.user?.permissions?.includes('delete-interview-feedbacks') && (
                                                                <Tooltip delayDuration={300}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => openDeleteDialog(interviewfeedback.id)}
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
                                    icon={MessageSquareIcon}
                                    title={t('No Interview Feedback found')}
                                    description={t('Get started by creating your first Interview Feedback.')}
                                    hasFilters={!!(filters.strengths || filters.weaknesses || filters.comments || (filters.interview_id !== 'all' && filters.interview_id) || (filters.interviewer_id !== 'all' && filters.interviewer_id) || filters.recommendation)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-interview-feedbacks"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Interview Feedback')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={interviewfeedbacks || { data: [], links: [], meta: {} }}
                        routeName="recruitment.interview-feedbacks.index"
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
                        interviewfeedback={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View interviewfeedback={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Interview Feedback')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}