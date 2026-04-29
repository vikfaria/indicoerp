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
import { Plus, Edit as EditIcon, Trash2, Eye, Calculator as CalculatorIcon, Download, FileImage, Play } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditPayroll from './Edit';
import View from './View';
import NoRecordsFound from '@/components/no-records-found';
import { Payroll, PayrollsIndexProps, PayrollFilters, PayrollModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { payrolls, auth } = usePage<PayrollsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<PayrollFilters>({
        title: urlParams.get('title') || '',
        payroll_frequency: urlParams.get('payroll_frequency') || '',
        status: urlParams.get('status') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const viewMode = 'list';
    const [modalState, setModalState] = useState<PayrollModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Payroll | null>(null);

    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'hrm.payrolls.destroy',
        defaultMessage: t('Are you sure you want to delete this payroll?')
    });

    const handleFilter = () => {
        router.get(route('hrm.payrolls.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('hrm.payrolls.index'), { ...filters, per_page: perPage, sort: field, direction }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            title: '',
            payroll_frequency: '',
            status: '',
        });
        router.get(route('hrm.payrolls.index'), { per_page: perPage });
    };

    const openModal = (mode: 'add' | 'edit', data: Payroll | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const runPayroll = (payrollId: number) => {
        router.post(route('hrm.payrolls.run', payrollId));
    };

    const tableColumns = [
        {
            key: 'title',
            header: t('Title'),
            sortable: true
        },
        {
            key: 'payroll_frequency',
            header: t('Payroll Frequency'),
            sortable: false,
            render: (value: string) => {
                const frequencyLabels = {
                    weekly: 'Weekly',
                    biweekly: 'Bi-Weekly',
                    monthly: 'Monthly'
                };
                return frequencyLabels[value as keyof typeof frequencyLabels] || value?.charAt(0).toUpperCase() + value?.slice(1) || '-';
            }
        },
        {
            key: 'pay_period_start',
            header: t('Pay Period Start'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'pay_period_end',
            header: t('Pay Period End'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'pay_date',
            header: t('Pay Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const statusColors = {
                    draft: 'bg-yellow-100 text-yellow-800',
                    processing: 'bg-blue-100 text-blue-800',
                    completed: 'bg-green-100 text-green-800',
                    cancelled: 'bg-red-100 text-red-800'
                };
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${statusColors[value as keyof typeof statusColors] || statusColors.draft}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1) || 'Draft')}
                    </span>
                );
            }
        },
        {
            key: 'total_net_pay',
            header: t('Total Net Pay'),
            sortable: false,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'employee_count',
            header: t('Employee Count'),
            sortable: false,
            render: (value: number) => value || '-'
        },
        {
            key: 'is_payroll_paid',
            header: t('Payment Status'),
            sortable: false,
            render: (value: string) => {
                const isPaid = value === 'paid';
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${
                        isPaid 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                    }`}>
                        {t(isPaid ? 'Paid' : 'Unpaid')}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['run-payrolls','view-payrolls', 'edit-payrolls', 'delete-payrolls'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, payroll: Payroll) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('run-payrolls') && payroll.is_payroll_paid !== 'paid' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => runPayroll(payroll.id)} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Run Payroll')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-payrolls') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('hrm.payrolls.show', payroll.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-payrolls') && payroll.is_payroll_paid !== 'paid' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', payroll)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-payrolls') && payroll.is_payroll_paid !== 'paid' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(payroll.id)}
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
                { label: t('Payrolls') }
            ]}
            pageTitle={t('Manage Payrolls')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-payrolls') && (
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
            <Head title={t('Payrolls')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({ ...filters, title: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Payrolls...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="hrm.payrolls.index"
                                filters={{ ...filters }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.payroll_frequency, filters.status].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Payroll Frequency')}</label>
                                <Select value={filters.payroll_frequency} onValueChange={(value) => setFilters({ ...filters, payroll_frequency: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Payroll Frequency')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="weekly">{t('Weekly')}</SelectItem>
                                        <SelectItem value="biweekly">{t('Bi-Weekly')}</SelectItem>
                                        <SelectItem value="monthly">{t('Monthly')}</SelectItem>
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
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="processing">{t('Processing')}</SelectItem>
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

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={payrolls?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={CalculatorIcon}
                                        title={t('No Payrolls found')}
                                        description={t('Get started by creating your first Payroll.')}
                                        hasFilters={!!(filters.title || filters.payroll_frequency || filters.status)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-payrolls"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Payroll')}
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
                        data={payrolls || { data: [], links: [], meta: {} }}
                        routeName="hrm.payrolls.index"
                        filters={{ ...filters, per_page: perPage }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditPayroll
                        payroll={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View payroll={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Payroll')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}