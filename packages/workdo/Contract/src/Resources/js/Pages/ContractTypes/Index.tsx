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
import { Plus, Edit as EditIcon, Trash2, Eye, Tag as TagIcon, Download, FileImage } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";

import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Create from './Create';
import EditContractType from './Edit';

import NoRecordsFound from '@/components/no-records-found';
import { ContractType, ContractTypesIndexProps, ContractTypeFilters, ContractTypeModalState } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const pageProps = usePage<ContractTypesIndexProps>().props;
    const { contracttypes, auth } = pageProps;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ContractTypeFilters>({
        name: urlParams.get('name') || '',
        is_active: urlParams.get('is_active') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');

    const [modalState, setModalState] = useState<ContractTypeModalState>({
        isOpen: false,
        mode: '',
        data: null
    });


    const [showFilters, setShowFilters] = useState(false);




    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'contract-types.destroy',
        defaultMessage: t('Are you sure you want to delete this contract type?')
    });

    const handleFilter = () => {
        router.get(route('contract-types.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('contract-types.index'), { ...filters, per_page: perPage, sort: field, direction }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            name: '',
            is_active: '',
        });
        router.get(route('contract-types.index'), { per_page: perPage });
    };

    const openModal = (mode: 'add' | 'edit', data: ContractType | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'contracts_count',
            header: t('Contracts'),
            sortable: true,
            render: (value: number, contracttype: ContractType) => {
                const contracts = contracttype.contracts || [];
                const totalCount = contracts.length;

                if (totalCount === 0) {
                    return (
                        <div className="flex">
                            <span className="text-gray-400 text-sm font-medium">{t('No contracts')}</span>
                        </div>
                    );
                }

                const displayContracts = contracts.slice(0, 4);
                const remainingCount = totalCount - displayContracts.length;

                return (
                    <div className="space-y-1">
                        <div className="flex flex-wrap gap-1">
                            {displayContracts.map((contract) => (
                                <Badge
                                    key={contract.id}
                                    variant="outline"
                                    className="text-xs bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100"
                                >
                                    {contract.contract_number}
                                </Badge>
                            ))}
                            {remainingCount > 0 && (
                                <Badge
                                    variant="secondary"
                                    className="text-xs bg-gray-100 text-gray-600 hover:bg-gray-200"
                                >
                                    +{remainingCount} {t('more')}
                                </Badge>
                            )}
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'is_active',
            header: t('Status'),
            sortable: false,
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm ${value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`}>
                    {value ? t('Active') : t('Inactive')}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['edit-contract-types', 'delete-contract-types'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, contracttype: ContractType) => (
                <div className="flex gap-1">
                    <TooltipProvider>

                        {auth.user?.permissions?.includes('edit-contract-types') && (contracttype.creator_id === auth.user?.id || contracttype.created_by === auth.user?.id) && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => openModal('edit', contracttype)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <EditIcon className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-contract-types') && (contracttype.creator_id === auth.user?.id || contracttype.created_by === auth.user?.id) && (
                            <Tooltip >
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(contracttype.id)}
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
                { label: t('Contract') },
                { label: t('Contract Types') }
            ]}
            pageTitle={t('Manage Contract Types')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-contract-types') && (
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
            }
        >
            <Head title={t('Contract Types')} />

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
                                placeholder={t('Search by name or contract number...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">

                            <PerPageSelector
                                routeName="contract-types.index"
                                filters={{ ...filters }}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.is_active].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.is_active} onValueChange={(value) => setFilters({ ...filters, is_active: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">{t('Active')}</SelectItem>
                                        <SelectItem value="0">{t('Inactive')}</SelectItem>
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
                                data={contracttypes?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={TagIcon}
                                        title={t('No Contract Types found')}
                                        description={t('Get started by creating your first Contract Type.')}
                                        hasFilters={!!(filters.name || filters.is_active)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-contract-types"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Contract Type')}
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
                        data={contracttypes || { data: [], links: [], meta: {} }}
                        routeName="contract-types.index"
                        filters={{ ...filters, per_page: perPage }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <EditContractType
                        contracttype={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>



            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Contract Type')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}