import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { usePageButtons } from '@/hooks/usePageButtons';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Eye, XCircle, CheckCircle, Trash2 } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { formatCurrency, formatDate } from '@/utils/helpers';
import NoRecordsFound from '@/components/no-records-found';

interface DebitNote {
    id: number;
    debit_note_number: string;
    debit_note_date: string;
    vendor: {
        name: string;
    };
    total_amount: number;
    applied_amount: number;
    balance_amount: number;
    status: string;
    reason: string;
    purchase_return?: {
        return_number: string;
    };
    approved_by?: {
        name: string;
    };
}

interface DebitNoteFilters {
    search: string;
    vendor_id: string;
    status: string;
    purchase_return_id: string;
}

interface DebitNoteIndexProps {
    debitNotes: {
        data: DebitNote[];
        links: any[];
        meta: any;
    };
    vendors: Array<{id: number; name: string}>;
    purchaseReturns: Array<{id: number; return_number: string}>;
    filters: DebitNoteFilters;
    auth: any;
    [key: string]: any;
}

export default function Index() {
    const { t } = useTranslation();
    const { debitNotes, vendors, purchaseReturns, filters: initialFilters, auth } = usePage<DebitNoteIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<DebitNoteFilters>({
        search: initialFilters?.search || urlParams.get('search') || '',
        vendor_id: initialFilters?.vendor_id || urlParams.get('vendor_id') || '',
        status: initialFilters?.status || urlParams.get('status') || '',
        purchase_return_id: initialFilters?.purchase_return_id || urlParams.get('purchase_return_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);


    const pageButtons = usePageButtons('debitNoteBtn', 'Debit Note data');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.debit-notes.destroy',
        defaultMessage: t('Are you sure you want to delete this debit note?')
    });

    const getStatusBadgeClasses = (status: string) => {
        switch (status) {
            case 'draft': return 'px-2 py-1 rounded-full text-sm bg-gray-100 text-gray-800';
            case 'partial': return 'px-2 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800';
            case 'approved': return 'px-2 py-1 rounded-full text-sm bg-green-100 text-green-800';
            case 'applied': return 'px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800';
            default: return 'px-2 py-1 rounded-full text-sm bg-gray-100 text-gray-800';
        }
    };

    const handleFilter = () => {
        router.get(route('account.debit-notes.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('account.debit-notes.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', vendor_id: '', status: '', purchase_return_id: '' });
        router.get(route('account.debit-notes.index'), {per_page: perPage, view: viewMode});
    };

    const tableColumns = [
        {
            key: 'debit_note_number',
            header: t('Debit Note Number'),
            sortable: true,
            render: (value: string, debitNote: DebitNote) =>
                auth.user?.permissions?.includes('view-debit-notes') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('account.debit-notes.show', debitNote.id))}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'purchase_return',
            header: t('Purchase Return'),
            render: (value: any, debitNote: DebitNote) =>
                value?.return_number ? (
                    auth.user?.permissions?.includes('view-purchase-return-invoices') ? (
                        <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('purchase-returns.show', debitNote.purchase_return?.id))}>
                            {value.return_number}
                        </span>
                    ) : (
                        value.return_number
                    )
                ) : '-'
        },
        {
            key: 'vendor',
            header: t('Vendor'),
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'debit_note_date',
            header: t('Date'),
            sortable: false,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'total_amount',
            header: t('Total Amount'),
            sortable: false,
            render: (value: number) => formatCurrency(parseFloat(value.toString()))
        },
        {
            key: 'balance_amount',
            header: t('Balance'),
            sortable: false,
        render: (value: number) => formatCurrency(parseFloat(value.toString()))
        },

        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => (
                <span className={getStatusBadgeClasses(value)}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        {
            key: 'approved_by',
            header: t('Approved By'),
            render: (value: any) => value?.name || '-'
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-debit-notes', 'approve-debit-notes', 'delete-debit-notes'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, debitNote: DebitNote) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {debitNote.status === 'draft' && auth.user?.permissions?.includes('approve-debit-notes') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.post(route('account.debit-notes.approve', debitNote.id))}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <CheckCircle className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Approve')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}

                        {auth.user?.permissions?.includes('view-debit-notes') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.get(route('account.debit-notes.show', debitNote.id))}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}

                        {debitNote.status === 'draft' && auth.user?.permissions?.includes('delete-debit-notes') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(debitNote.id)}
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
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Accounting'), url: route('account.index')},
                {label: t('Debit Notes')}
            ]}
            pageTitle={t('Manage Debit Notes')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Debit Notes')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search || ''}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by debit note number...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="account.debit-notes.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="account.debit-notes.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.vendor_id, filters.status, filters.purchase_return_id].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {auth.user?.permissions?.includes('manage-users') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Vendor')}</label>
                                    <Select value={filters.vendor_id} onValueChange={(value) => setFilters({...filters, vendor_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by vendor')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {vendors.map((vendor) => (
                                                <SelectItem key={vendor.id} value={vendor.id.toString()}>
                                                    {vendor.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="partial">{t('Partial')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="applied">{t('Applied')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Purchase Return')}</label>
                                <Select value={filters.purchase_return_id} onValueChange={(value) => setFilters({...filters, purchase_return_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by purchase return')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {purchaseReturns.map((purchaseReturn) => (
                                            <SelectItem key={purchaseReturn.id} value={purchaseReturn.id.toString()}>
                                                {purchaseReturn.return_number}
                                            </SelectItem>
                                        ))}
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
                                    data={debitNotes.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={XCircle}
                                            title={t('No debit notes found')}
                                            description={t('Debit notes are automatically created from purchase returns.')}
                                            hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.purchase_return_id)}
                                            onClearFilters={clearFilters}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {debitNotes.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {debitNotes.data.map((debitNote) => (
                                        <Card key={debitNote.id} className="border border-gray-200 flex flex-col">
                                            <div className="p-4 flex-1">
                                                <div className="mb-3">
                                                    {auth.user?.permissions?.includes('view-debit-notes') ? (
                                                        <h3 className="font-semibold text-base text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('account.debit-notes.show', debitNote.id))}>{debitNote.debit_note_number}</h3>
                                                    ) : (
                                                        <h3 className="font-semibold text-base text-gray-900">{debitNote.debit_note_number}</h3>
                                                    )}
                                                </div>

                                                <div className="space-y-3 mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Vendor')}</p>
                                                        <p className="text-sm text-gray-900 truncate font-medium">{debitNote.vendor?.name}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(debitNote.debit_note_date)}</p>
                                                        </div>
                                                        {debitNote.purchase_return && (
                                                            <div>
                                                                <p className="text-xs font-medium text-gray-600 mb-1 text-end">{t('Purchase Return')}</p>
                                                                {auth.user?.permissions?.includes('view-purchase-return-invoices') ? (
                                                                    <p className="text-xs text-blue-600 cursor-pointer hover:text-blue-700 text-end" onClick={() => router.get(route('purchase-returns.show', debitNote.purchase_return.id))}>{debitNote.purchase_return.return_number}</p>
                                                                ) : (
                                                                    <p className="text-xs text-gray-900 text-end">{debitNote.purchase_return.return_number}</p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="grid grid-cols-2 gap-2 text-xs">
                                                            <div className="flex justify-between">
                                                                <span className="text-gray-600">{t('Total')}:</span>
                                                                <span className="font-medium">{formatCurrency(parseFloat(debitNote.total_amount.toString()))}</span>
                                                            </div>
                                                            <div className="flex justify-between">
                                                                <span className="text-gray-600">{t('Applied')}:</span>
                                                                <span className="font-medium">{formatCurrency(parseFloat(debitNote.applied_amount.toString()))}</span>
                                                            </div>
                                                        </div>
                                                        <div className="border-t mt-2 pt-2">
                                                            <div className="flex justify-between items-center">
                                                                <span className="text-sm font-semibold text-gray-900">{t('Balance')}</span>
                                                                <span className="text-lg font-bold text-green-600">{formatCurrency(parseFloat(debitNote.balance_amount.toString()))}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Reason')}</p>
                                                        <p className="text-xs text-gray-900 line-clamp-2">{debitNote.reason}</p>
                                                    </div>
                                                    {debitNote.approved_by && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Approved By')}</p>
                                                            <p className="text-xs text-gray-900">{debitNote.approved_by.name}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-between p-3 border-t bg-gray-50/50">
                                                <span className={getStatusBadgeClasses(debitNote.status)}>
                                                    {t(debitNote.status.charAt(0).toUpperCase() + debitNote.status.slice(1))}
                                                </span>
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {debitNote.status === 'draft' && auth.user?.permissions?.includes('approve-debit-notes') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => router.post(route('account.debit-notes.approve', debitNote.id))}
                                                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                    >
                                                                        <CheckCircle className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Approve')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {auth.user?.permissions?.includes('view-debit-notes') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => router.get(route('account.debit-notes.show', debitNote.id))}
                                                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                                    >
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('View')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {debitNote.status === 'draft' && auth.user?.permissions?.includes('delete-debit-notes') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => openDeleteDialog(debitNote.id)}
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
                                    icon={XCircle}
                                    title={t('No debit notes found')}
                                    description={t('Debit notes are automatically created from purchase returns.')}
                                    hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.purchase_return_id)}
                                    onClearFilters={clearFilters}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={{...debitNotes, ...debitNotes.meta}}
                        routeName="account.debit-notes.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Debit Note')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
