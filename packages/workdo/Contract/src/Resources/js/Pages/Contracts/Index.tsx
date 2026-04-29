import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Eye, FileSignature, FileText } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import Create from './Create';
import EditContract from './Edit';
import DuplicateButton from './components/DuplicateButton';
import NoRecordsFound from '@/components/no-records-found';
import { Contract, ContractsIndexProps, ContractFilters, ContractModalState } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

const getContractStatusColor = (status: any) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'pending':
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        case 'accepted':
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'declined':
            return 'bg-red-100 text-red-800';
        case 'closed':
            return 'bg-slate-100 text-slate-800';
        case 'archived':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
};

const getContractStatusText = (status: any, t: (key: string) => string) => {
    const statusValue = status?.toString().toLowerCase();
    switch (statusValue) {
        case 'pending':
            return t('Pending');
        case 'accepted':
            return t('Accepted');
        case 'declined':
            return t('Declined');
        case 'closed':
            return t('Closed');
        case 'draft':
            return t('Draft');
        case 'active':
            return t('Active');
        case 'archived':
            return t('Archived');
        default:
            return t('Pending');
    }
};

export default function Index() {
    const { t } = useTranslation();
    const { contracts, auth, users, contracttypes } = usePage<ContractsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ContractFilters>({
        subject: urlParams.get('subject') || '',
        description: urlParams.get('description') || '',
        type_id: urlParams.get('type_id') || '',
        status: urlParams.get('status') || '',
        user_id: urlParams.get('user_id') || '',
        start_date: urlParams.get('start_date') || '',
        end_date: urlParams.get('end_date') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<ContractModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const pageButtons = usePageButtons('contractStackBtn', 'Contracts');
    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Contracts', settingKey: 'GoogleDrive Contracts' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Contracts', settingKey: 'OneDrive Contracts' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'contract.destroy',
        defaultMessage: t('Are you sure you want to delete this contract?')
    });

    const handleFilter = () => {
        router.get(route('contract.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: false,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('contract.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: false,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            subject: '',
            description: '',
            type_id: '',
            status: '',
            user_id: '',
            start_date: '',
            end_date: '',
        });
        router.get(route('contract.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Contract | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'contract_number',
            header: t('Contract Number'),
            sortable: true,
            render: (value: any, contract: Contract) =>
                auth.user?.permissions?.includes('view-contracts') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('contract.show', contract.id))}>{value || '-'}</span>
                ) : (
                    value || '-'
                )
        },
        {
            key: 'subject',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'user.name',
            header: t('User Name'),
            sortable: false,
            render: (value: any, row: any) => row.user?.name || '-'
        },

        {
            key: 'value',
            header: t('Value'),
            sortable: false,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'contract_type.name',
            header: t('Type'),
            sortable: false,
            render: (value: any, row: any) => row.contract_type?.name || '-'
        },
        {
            key: 'start_date',
            header: t('Start Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'end_date',
            header: t('End Date'),
            sortable: false,
            render: (value: string) => value ? formatDate(value) : '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: false,
            render: (value: string) => {
                const displayValue = getContractStatusText(value, t);
                const colorClass = getContractStatusColor(value);
                return (
                    <span className={`px-2 py-1 rounded-full text-sm ${colorClass}`}>
                        {displayValue}
                    </span>
                );
            }
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-contracts', 'edit-contracts', 'delete-contracts', 'duplicate-contracts'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, contract: Contract) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {usePageButtons('contractActionBtn', contract).map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        <DuplicateButton contract={contract} />
                        {auth.user?.permissions?.includes('preview-contracts') && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('contract.preview', contract.id))} className="h-8 w-8 p-0 text-purple-600 hover:text-purple-700">
                                        <FileText className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Preview')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-contracts') && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('contract.show', contract.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-contracts') && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', contract)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-contracts') && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(contract.id)}
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
                { label: t('Contracts') }
            ]}
            pageTitle={t('Manage Contracts')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {googleDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {auth.user?.permissions?.includes('create-contracts') && (
                            <Tooltip >
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
            <Head title={t('Contracts')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.subject}
                                onChange={(value) => setFilters({ ...filters, subject: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Contracts...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="contract.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="contract.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.type_id, filters.status, filters.user_id, filters.start_date, filters.end_date].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                            {(auth.user?.permissions?.includes('manage-contract-types')) && (

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Type')}</label>
                                    <Select value={filters.type_id} onValueChange={(value) => setFilters({ ...filters, type_id: value })}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by Type')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {contracttypes?.map((item: any) => (
                                                <SelectItem key={item.id} value={item.id.toString()}>
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{t('Pending')}</SelectItem>
                                        <SelectItem value="accepted">{t('Accepted')}</SelectItem>
                                        <SelectItem value="declined">{t('Declined')}</SelectItem>
                                        <SelectItem value="closed">{t('Closed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('User')}</label>
                                <Select value={filters.user_id} onValueChange={(value) => setFilters({ ...filters, user_id: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by User')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users?.map((item: any) => (
                                            <SelectItem key={item.id} value={item.id.toString()}>
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Start Date')}</label>
                                <DatePicker
                                    value={filters.start_date}
                                    onChange={(date) => setFilters({ ...filters, start_date: date })}
                                    placeholder={t('Filter by Start Date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('End Date')}</label>
                                <DatePicker
                                    value={filters.end_date}
                                    onChange={(date) => setFilters({ ...filters, end_date: date })}
                                    placeholder={t('Filter by End Date')}
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
                                    data={contracts?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={FileSignature}
                                            title={t('No Contracts found')}
                                            description={t('Get started by creating your first Contract.')}
                                            hasFilters={!!(filters.subject || filters.description || filters.type_id || filters.status || filters.user_id || filters.start_date || filters.end_date)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-contracts"
                                            onCreateClick={() => openModal('add')}
                                            createButtonText={t('Create Contract')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {contracts?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {contracts?.data?.map((contract) => (
                                        <Card key={contract.id} className="p-0 hover:shadow-lg transition-all duration-200 relative overflow-hidden flex flex-col h-full min-w-0">
                                            {/* Arrow decoration */}
                                            <div className="absolute top-0 right-0 w-0 h-0 border-l-[20px] border-l-transparent border-t-[20px] border-t-primary/20"></div>
                                            {/* Header */}
                                            <div className="p-4 bg-gradient-to-r from-primary/5 to-transparent border-b flex-shrink-0">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg">
                                                        <FileSignature className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="font-semibold text-sm text-gray-900">{contract.subject}</h3>
                                                        {auth.user?.permissions?.includes('view-contracts') ? (
                                                            <p className="text-xs font-medium text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('contract.show', contract.id))}>{contract.contract_number || '-'}</p>
                                                        ) : (
                                                            <p className="text-xs font-medium text-primary">{contract.contract_number || '-'}</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Body */}
                                            <div className="p-4 flex-1 min-h-0">
                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Value')}</p>
                                                        <p className="font-medium text-xs">{contract.value ? formatCurrency(contract.value) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Type')}</p>
                                                        <p className="font-medium text-xs">{contract.contract_type?.name || '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4 mb-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Start Date')}</p>
                                                        <p className="font-medium text-xs">{contract.start_date ? formatDate(contract.start_date) : '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('End Date')}</p>
                                                        <p className="font-medium text-xs">{contract.end_date ? formatDate(contract.end_date) : '-'}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Assigned To')}</p>
                                                        <p className="font-medium text-xs">{contract.user?.name || '-'}</p>
                                                    </div>
                                                    <div className="text-xs min-w-0">
                                                        <p className="text-muted-foreground mb-1 text-xs uppercase tracking-wide">{t('Status')}</p>
                                                        <span className={`px-2 py-1 rounded-full text-xs font-medium inline-block ${getContractStatusColor(contract.status)}`}>
                                                            {getContractStatusText(contract.status, t)}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions Footer */}
                                            <div className="flex justify-center gap-2 p-3 border-t bg-gray-50/50 flex-shrink-0 mt-auto">
                                                <TooltipProvider>
                                                    {usePageButtons('contractActionBtn', contract).map((button) => (
                                                        <div key={button.id}>{button.component}</div>
                                                    ))}
                                                    <DuplicateButton
                                                        contract={contract}
                                                        className="h-9 w-9 p-0 text-orange-600 hover:text-orange-700"
                                                    />
                                                    {auth.user?.permissions?.includes('preview-contracts') && (
                                                        <Tooltip >
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => router.get(route('contract.preview', contract.id))} className="h-9 w-9 p-0 text-purple-600 hover:text-purple-700">
                                                                    <FileText className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Preview')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('view-contracts') && (
                                                        <Tooltip >
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => router.get(route('contract.show', contract.id))} className="h-9 w-9 p-0 text-green-600 hover:text-green-700">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('View')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-contracts') && (
                                                        <Tooltip >
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => openModal('edit', contract)} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700">
                                                                    <EditIcon className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t('Edit')}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-contracts') && (
                                                        <Tooltip >
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(contract.id)}
                                                                    className="h-9 w-9 p-0 text-destructive hover:text-destructive"
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
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={FileSignature}
                                    title={t('No Contracts found')}
                                    description={t('Get started by creating your first Contract.')}
                                    hasFilters={!!(filters.subject || filters.description || filters.type_id || filters.status || filters.user_id || filters.start_date || filters.end_date)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-contracts"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText={t('Create Contract')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={contracts || { data: [], links: [], meta: {} }}
                        routeName="contract.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditContract
                        contract={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Contract')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}