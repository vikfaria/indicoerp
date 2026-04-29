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
import { Plus, Edit as EditIcon, Trash2, Play, Eye, ArrowRightLeft } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import Create from './Create';
import Edit from './Edit';
import View from './View';
import NoRecordsFound from '@/components/no-records-found';
import { BankTransfer, BankTransfersIndexProps, BankTransferFilters, BankTransferModalState } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { banktransfers, auth, bankaccounts } = usePage<BankTransfersIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<BankTransferFilters>({
        transfer_number: urlParams.get('transfer_number') || '',
        status: urlParams.get('status') || '',
        from_account_id: urlParams.get('from_account_id') || '',
        to_account_id: urlParams.get('to_account_id') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [modalState, setModalState] = useState<BankTransferModalState>({
        isOpen: false,
        mode: '',
        data: null
    });

    const [showFilters, setShowFilters] = useState(false);
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [viewingTransfer, setViewingTransfer] = useState<BankTransfer | null>(null);


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.bank-transfers.destroy',
        defaultMessage: t('Are you sure you want to delete this bank transfer?')
    });

    const getStatusBadge = (status: string) => {
        const variants = {
            pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            completed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            failed: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
        };

        return (
            <Badge className={variants[status as keyof typeof variants]}>
                {t(status.charAt(0).toUpperCase() + status.slice(1))}
            </Badge>
        );
    };

    const handleFilter = () => {
        router.get(route('account.bank-transfers.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('account.bank-transfers.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            transfer_number: '',
            status: '',
            from_account_id: '',
            to_account_id: '',
        });
        router.get(route('account.bank-transfers.index'), {per_page: perPage, sort: sortField, direction: sortDirection});
    };

    const openModal = (mode: 'add' | 'edit', data: BankTransfer | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleProcess = (transfer: BankTransfer) => {
        setProcessingId(transfer.id);
        router.post(route('account.bank-transfers.process', transfer.id), {}, {
            onFinish: () => setProcessingId(null)
        });
    };

    const tableColumns = [
        {
            key: 'transfer_number',
            header: t('Transfer Number'),
            sortable: true,
            render: (value: string, transfer: BankTransfer) =>
                auth.user?.permissions?.includes('view-bank-transfers') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => setViewingTransfer(transfer)}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'transfer_date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'from_account',
            header: t('From Account'),
            render: (_: any, transfer: BankTransfer) => (
                <div>
                    <div className="font-medium">{transfer.from_account.account_name}</div>
                    <div className="text-sm text-gray-500">{transfer.from_account.account_number}</div>
                </div>
            )
        },
        {
            key: 'to_account',
            header: t('To Account'),
            render: (_: any, transfer: BankTransfer) => (
                <div>
                    <div className="font-medium">{transfer.to_account.account_name}</div>
                    <div className="text-sm text-gray-500">{transfer.to_account.account_number}</div>
                </div>
            )
        },
        {
            key: 'transfer_amount',
            header: t('Amount'),
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'completed' ? 'bg-green-100 text-green-800' :
                    value === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                }`}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-bank-transfers', 'edit-bank-transfers', 'delete-bank-transfers', 'process-bank-transfers'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, transfer: BankTransfer) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                    {transfer.status === 'pending' && auth.user?.permissions?.includes('process-bank-transfers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleProcess(transfer)}
                                        disabled={processingId === transfer.id}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Play className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Process Transfer')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-bank-transfers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setViewingTransfer(transfer)}
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
                        {transfer.status === 'pending' && auth.user?.permissions?.includes('edit-bank-transfers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', transfer)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {transfer.status === 'pending' && auth.user?.permissions?.includes('delete-bank-transfers') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(transfer.id)}
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
                {label: t('Banking')},
                {label: t('Bank Transfers')}
            ]}
            pageTitle={t('Manage Bank Transfers')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-bank-transfers') && (
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
            <Head title={t('Bank Transfers')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.transfer_number}
                                onChange={(value) => setFilters({...filters, transfer_number: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by transfer number or reference...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="account.bank-transfers.index"
                                filters={filters}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.status, filters.from_account_id, filters.to_account_id].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="completed">{t('Completed')}</SelectItem>
                                        <SelectItem value="failed">{t('Failed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Account')}</label>
                                <Select value={filters.from_account_id} onValueChange={(value) => setFilters({...filters, from_account_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by From Account')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankaccounts.map(account => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Account')}</label>
                                <Select value={filters.to_account_id} onValueChange={(value) => setFilters({...filters, to_account_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by To Account')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankaccounts.map(account => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex gap-2">
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
                                data={banktransfers?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={ArrowRightLeft}
                                        title={t('No Bank Transfers found')}
                                        description={t('Get started by creating your first Bank Transfer.')}
                                        hasFilters={!!(filters.transfer_number || filters.status || filters.from_account_id || filters.to_account_id)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-bank-transfers"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Bank Transfer')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={banktransfers || { data: [], links: [], meta: {} }}
                        routeName="account.bank-transfers.index"
                        filters={filters}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        banktransfer={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingTransfer} onOpenChange={() => setViewingTransfer(null)}>
                {viewingTransfer && <View banktransfer={viewingTransfer} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Bank Transfer')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
