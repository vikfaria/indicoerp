import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Eye, Trash2, CreditCard, CheckCircle, X } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Input } from '@/components/ui/input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import Create from './Create';
import View from './View';
import NoRecordsFound from '@/components/no-records-found';
import { VendorPayment, VendorPaymentsIndexProps, VendorPaymentModalState } from './types';

interface VendorPaymentFilters {
    vendor_id: string;
    status: string;
    search: string;
    date_range: string;
    bank_account_id: string;
}
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { payments, vendors, bankAccounts, filters: initialFilters, auth } = usePage<VendorPaymentsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<VendorPaymentFilters>({
        vendor_id: initialFilters?.vendor_id || '',
        status: initialFilters?.status || '',
        search: initialFilters?.search || '',
        date_range: (() => {
            const fromDate = urlParams.get('date_from');
            const toDate = urlParams.get('date_to');
            return (fromDate && toDate) ? `${fromDate} - ${toDate}` : '';
        })(),
        bank_account_id: initialFilters?.bank_account_id || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || 'created_at');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<VendorPaymentModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<VendorPayment | null>(null);
    const [showFilters, setShowFilters] = useState(false);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.vendor-payments.destroy',
        defaultMessage: t('Are you sure you want to delete this payment?')
    });

    const paymentMethodLabel = (method?: string) => {
        const labels: Record<string, string> = {
            bank_transfer: t('Bank Transfer'),
            cash: t('Cash'),
            cheque: t('Cheque'),
            card: t('Card'),
            mobile_money: t('Mobile Money'),
            other: t('Other')
        };

        return labels[method || ''] || t('Bank Transfer');
    };

    const handleFilter = () => {
        const filterParams: any = {
            search: filters.search,
            vendor_id: filters.vendor_id,
            status: filters.status,
            bank_account_id: filters.bank_account_id,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection,
            view: viewMode
        };

        // Convert date_range to date_from and date_to for backend
        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }

        router.get(route('account.vendor-payments.index'), filterParams, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);

        const filterParams = { ...filters };

        // Convert date_range to date_from and date_to for backend
        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('account.vendor-payments.index'), {...filterParams, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            vendor_id: '',
            status: '',
            search: '',
            date_range: '',
            bank_account_id: ''
        });
        router.get(route('account.vendor-payments.index'), {per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode});
    };

    const openModal = (mode: 'add', data: VendorPayment | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'payment_number',
            header: t('Payment Number'),
            sortable: true,
            render: (value: string, payment: VendorPayment) =>
                auth.user?.permissions?.includes('view-vendor-payments') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => setViewingItem(payment)}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'payment_date',
            header: t('Payment Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'vendor.name',
            header: t('Vendor'),
            sortable: false,
            render: (_: any, payment: VendorPayment) => payment.vendor?.name || '-'
        },
        {
            key: 'bankAccount.account_name',
            header: t('Bank Account'),
            sortable: false,
            render: (_: any, payment: VendorPayment) => payment.bank_account?.account_name || '-'
        },
        {
            key: 'payment_amount',
            header: t('Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'payment_method',
            header: t('Payment Method'),
            sortable: false,
            render: (value: string, payment: VendorPayment) => {
                const providerLabel = payment.mobile_money_provider === 'mpesa'
                    ? 'M-Pesa'
                    : payment.mobile_money_provider === 'emola'
                        ? 'e-Mola'
                        : payment.mobile_money_provider === 'mkesh'
                            ? 'mKesh'
                            : null;

                return providerLabel
                    ? `${paymentMethodLabel(value)} (${providerLabel})`
                    : paymentMethodLabel(value);
            }
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'cleared' ? 'bg-green-100 text-green-800' :
                    value === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                }`}>
                    {t(value)}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-vendor-payments', 'delete-vendor-payments','cleared-vendor-payments'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, payment: VendorPayment) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                    {auth.user?.permissions?.includes('cleared-vendor-payments') && payment.status === 'pending' && (
                            <>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => router.post(route('account.vendor-payments.update-status', payment.id), { status: 'cleared' })}
                                            className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                        >
                                            <CheckCircle className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Mark as Cleared')}</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => router.post(route('account.vendor-payments.update-status', payment.id), { status: 'cancelled' })}
                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Cancel Payment')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </>
                        )}
                        {auth.user?.permissions?.includes('view-vendor-payments') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(payment)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-vendor-payments') && payment.status === 'pending' && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(payment.id)}
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
                {label: t('Accounting'), url: route('account.index')},
                {label: t('Vendor Payments')}
            ]}
            pageTitle={t('Manage Vendor Payments')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-vendor-payments') && (
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
            <Head title={t('Vendor Payments')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search payments...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="account.vendor-payments.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="account.vendor-payments.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const filtersToCheck = auth.user?.permissions?.includes('manage-users')
                                        ? [filters.vendor_id, filters.status, filters.date_range, filters.bank_account_id]
                                        : [filters.status, filters.date_range, filters.bank_account_id];
                                    const activeFilters = filtersToCheck.filter(f => f !== '').length;
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
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            {auth.user?.permissions?.includes('manage-users') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Vendor')}</label>
                                    <Select value={filters.vendor_id} onValueChange={(value) => setFilters({...filters, vendor_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by Vendor')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {vendors?.map((vendor) => (
                                                <SelectItem key={vendor.id} value={vendor.id.toString()}>
                                                    {vendor.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            {auth.user?.permissions?.includes('manage-bank-accounts') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Bank Account')}</label>
                                    <Select value={filters.bank_account_id} onValueChange={(value) => setFilters({...filters, bank_account_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by bank account')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {bankAccounts?.map((account) => (
                                                <SelectItem key={account.id} value={account.id.toString()}>
                                                    {account.account_name}
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
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="cleared">{t('Cleared')}</SelectItem>
                                        <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DateRangePicker
                                    value={filters.date_range}
                                    onChange={(value) => setFilters({...filters, date_range: value})}
                                    placeholder={t('Select date range')}
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
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={payments?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={CreditCard}
                                            title={t('No payments found')}
                                            description={t('Get started by creating your first vendor payment.')}
                                            hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.date_range || filters.bank_account_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-vendor-payments"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Payment')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {payments?.data && payments.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5 gap-4">
                                    {payments.data.map((payment) => (
                                        <Card key={payment.id} className="border border-gray-200 flex flex-col">
                                            <div className="p-4 flex-1">
                                                <div className="mb-3">
                                                    {auth.user?.permissions?.includes('view-vendor-payments') ? (
                                                        <h3 className="font-semibold text-base text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => setViewingItem(payment)}>{payment.payment_number}</h3>
                                                    ) : (
                                                        <h3 className="font-semibold text-base text-gray-900">{payment.payment_number}</h3>
                                                    )}
                                                </div>

                                                <div className="space-y-3 mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Vendor')}</p>
                                                        <p className="text-sm text-gray-900 truncate font-medium">{payment.vendor?.name}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(payment.payment_date)}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1 text-end">{t('Bank Account')}</p>
                                                            <p className="text-xs text-gray-900 text-end">{payment.bank_account?.account_name}</p>
                                                        </div>
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Amount')}</span>
                                                            <span className="text-lg font-bold text-green-600">{formatCurrency(payment.payment_amount)}</span>
                                                        </div>
                                                    </div>
                                                    {payment.notes && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Notes')}</p>
                                                            <p className="text-xs text-gray-900 line-clamp-2">{payment.notes}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-between p-3 border-t bg-gray-50/50">
                                                <span className={`px-2 py-1 rounded-full text-sm ${
                                                    payment.status === 'cleared' ? 'bg-green-100 text-green-800' :
                                                    payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {t(payment.status)}
                                                </span>
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {payment.status === 'pending' && auth.user?.permissions?.includes('cleared-vendor-payments') && (
                                                            <>
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => router.post(route('account.vendor-payments.update-status', payment.id), { status: 'cleared' })}
                                                                            className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                        >
                                                                            <CheckCircle className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Mark as Cleared')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => router.post(route('account.vendor-payments.update-status', payment.id), { status: 'cancelled' })}
                                                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                                        >
                                                                            <X className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Cancel Payment')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </>
                                                        )}
                                                        {auth.user?.permissions?.includes('view-vendor-payments') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => setViewingItem(payment)}
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
                                                        {payment.status === 'pending' && auth.user?.permissions?.includes('delete-vendor-payments') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => openDeleteDialog(payment.id)}
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
                                    icon={CreditCard}
                                    title={t('No payments found')}
                                    description={t('Get started by creating your first vendor payment.')}
                                    hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.date_range || filters.bank_account_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-vendor-payments"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Payment')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={payments || { data: [], links: [], meta: {} }}
                        routeName="account.vendor-payments.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create
                        vendors={vendors}
                        bankAccounts={bankAccounts}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View payment={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Payment')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
