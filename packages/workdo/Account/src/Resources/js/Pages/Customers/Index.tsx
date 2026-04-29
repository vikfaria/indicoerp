import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Plus, Edit as EditIcon, Trash2, Building2, User as UserIcon, Lock, FileText, Eye } from "lucide-react";
import { getImagePath } from '@/utils/helpers';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { DataTable } from "@/components/ui/data-table";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from "@/components/ui/list-grid-toggle";
import { PerPageSelector } from "@/components/ui/per-page-selector";
import { FilterButton } from "@/components/ui/filter-button";
import { ConfirmationDialog } from "@/components/ui/confirmation-dialog";
import NoRecordsFound from '@/components/no-records-found';
import { Pagination } from "@/components/ui/pagination";
import Create from './Create';
import Edit from './Edit';
import View from './View';
import { Customer, User } from './types';
import { usePageButtons } from '@/hooks/usePageButtons';
interface CustomerFilters {
    company_name: string;
    customer_code: string;
    tax_number: string;
}

interface CustomerModalState {
    isOpen: boolean;
    mode: string;
    data: Customer | null;
}

interface CustomersIndexProps {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    users: User[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { customers, users, auth, is_demo } = usePage<any>().props;
    const { t } = useTranslation();
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<CustomerFilters>({
        company_name: urlParams.get('company_name') || '',
        customer_code: urlParams.get('customer_code') || '',
        tax_number: urlParams.get('tax_number') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [modalState, setModalState] = useState<CustomerModalState>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [viewingItem, setViewingItem] = useState<Customer | null>(null);
    const [showFilters, setShowFilters] = useState(false);

    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Customer', settingKey: 'GoogleDrive Customer' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Customer', settingKey: 'OneDrive Customer' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Account Customer', settingKey: 'Dropbox Account Customer' });
    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.customers.destroy',
        defaultMessage: 'Are you sure you want to delete this customer?'
    });

    const handleFilter = () => {
        router.get(route('account.customers.index'), {
            ...filters,
            per_page: perPage,
            sort: sortField,
            direction: sortDirection,
            view: viewMode
        }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('account.customers.index'), {
            ...filters,
            per_page: perPage,
            sort: field,
            direction,
            view: viewMode
        }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ company_name: '', customer_code: '', tax_number: '' });
        router.get(route('account.customers.index'), {per_page: perPage, view: viewMode});
    };

    const openModal = (mode: 'add' | 'edit', data: Customer | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'user',
            header: t('User'),
            render: (value: any, customer: any) => {
                if (!customer.user) return null;
                return (
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg overflow-hidden bg-gray-100 border flex items-center justify-center">
                            {customer.user.avatar ? (
                                <img
                                    src={getImagePath(customer.user.avatar)}
                                    alt="Avatar"
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <UserIcon className="w-4 h-4 text-gray-400" />
                            )}
                        </div>
                        <span className="text-sm">{customer.user.name}</span>
                    </div>
                );
            }
        },
        {
            key: 'customer_code',
            header: t('Customer Code'),
            sortable: true
        },
        {
            key: 'company_name',
            header: t('Company Name'),
            sortable: true
        },
        {
            key: 'contact_person_name',
            header: t('Contact Person'),
            sortable: true
        },
        {
            key: 'contact_person_email',
            header: t('Email'),
            sortable: false
        },
        {
            key: 'tax_number',
            header: t('Tax Number'),
            sortable: false
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-customers', 'edit-customers', 'delete-customers', 'view-customer-detail-report'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, customer: Customer) => (
                <div className="flex gap-1">
                    {customer.user?.is_disable === 1 ? (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <div className="h-8 w-8 p-0 flex items-center justify-center text-gray-400">
                                    <Lock className="h-4 w-4" />
                                </div>
                            </TooltipTrigger>
                            <TooltipContent><p>{t('User is disabled')}</p></TooltipContent>
                        </Tooltip>
                    ) : (
                        <TooltipProvider>
                            {auth.user?.permissions?.includes('view-customer-detail-report') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => {
                                            const params: any = { customer: customer.user_id };
                                            if (is_demo) {
                                                const year = new Date().getFullYear();
                                                params.start_date = `${year}-01-01`;
                                                params.end_date = `${year}-12-31`;
                                            }
                                            router.visit(route('account.reports.customer-detail', params));
                                        }} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                            <FileText className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('View Report')}</p></TooltipContent>
                                </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('view-customers') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => setViewingItem(customer)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                            <Eye className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('View')}</p></TooltipContent>
                                </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('edit-customers') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="sm" onClick={() => openModal('edit', customer)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                            <EditIcon className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                                </Tooltip>
                            )}
                            {auth.user?.permissions?.includes('delete-customers') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => openDeleteDialog(customer.id)}
                                            className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                </Tooltip>
                            )}
                        </TooltipProvider>
                    )}
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: 'Accounting', url:route('account.index')},{label: 'Customers'}]}
            pageTitle="Manage Customers"
            pageActions={
                <div className="flex gap-2">
                      {googleDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {dropboxBtn.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-customers') && (
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
            <Head title="Customers" />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.company_name}
                                onChange={(value) => setFilters({...filters, company_name: value})}
                                onSearch={handleFilter}
                                placeholder="Search customers..."
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="account.customers.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="account.customers.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.customer_code, filters.tax_number].filter(Boolean).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Customer Code')}</label>
                                <Input
                                    value={filters.customer_code}
                                    onChange={(e) => setFilters({...filters, customer_code: e.target.value})}
                                    placeholder={t('Filter by customer code')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Company Name')}</label>
                                <Input
                                    value={filters.company_name}
                                    onChange={(e) => setFilters({...filters, company_name: e.target.value})}
                                    placeholder={t('Filter by company name')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Tax Number')}</label>
                                <Input
                                    value={filters.tax_number}
                                    onChange={(e) => setFilters({...filters, tax_number: e.target.value})}
                                    placeholder={t('Filter by tax number')}
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
                                data={customers.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Building2}
                                        title="No customers found"
                                        description="Get started by creating your first customer."
                                        hasFilters={!!(filters.company_name || filters.customer_code || filters.tax_number)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-customers"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText="Create Customer"
                                        className="h-auto"
                                    />
                                }
                            />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {customers.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                    {customers.data.map((customer) => (
                                        <Card key={customer.id} className="border border-gray-200 hover:shadow-lg transition-all duration-200">
                                            <div className="p-4">
                                                <div className="flex items-start justify-between mb-3">
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900 truncate">{customer.company_name}</h3>
                                                        {auth.user?.permissions?.includes('view-customers') ? (
                                                            <p className="text-xs text-blue-600 font-medium mt-1 cursor-pointer" onClick={() => setViewingItem(customer)}>{customer.customer_code}</p>
                                                        ) : (
                                                            <p className="text-xs font-medium mt-1">{customer.customer_code}</p>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="space-y-2 mb-3">
                                                    <div className="flex justify-between items-center">
                                                        <span className="text-xs text-gray-500">{t('Contact')}</span>
                                                        <span className="text-xs font-medium text-gray-900 truncate ml-2">{customer.contact_person_name}</span>
                                                    </div>
                                                    {customer.contact_person_email && (
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-xs text-gray-500">{t('Email')}</span>
                                                            <span className="text-xs text-gray-900 truncate ml-2">{customer.contact_person_email}</span>
                                                        </div>
                                                    )}
                                                    {customer.tax_number && (
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-xs text-gray-500">{t('Tax Number')}</span>
                                                            <span className="text-xs font-medium text-gray-900">{customer.tax_number}</span>
                                                        </div>
                                                    )}
                                                    {customer.payment_terms && (
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-xs text-gray-500">{t('Payment Terms')}</span>
                                                            <span className="text-xs text-green-600 font-medium">{customer.payment_terms}</span>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                                                    {customer.user && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <span className="inline-flex items-center py-1">
                                                                    <div className="w-6 h-6 rounded-full overflow-hidden bg-gray-100 border flex items-center justify-center">
                                                                        {customer.user.avatar ? (
                                                                            <img
                                                                                src={getImagePath(customer.user.avatar)}
                                                                                alt="Avatar"
                                                                                className="w-full h-full object-cover"
                                                                            />
                                                                        ) : (
                                                                            <UserIcon className="w-3 h-3 text-gray-400" />
                                                                        )}
                                                                    </div>
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{customer.user.name}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    <div className="flex gap-1">
                                                        {customer.user?.is_disable === 1 ? (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <div className="h-8 w-8 p-0 flex items-center justify-center text-gray-400">
                                                                        <Lock className="h-4 w-4" />
                                                                    </div>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('User is disabled')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        ) : (
                                                            <TooltipProvider>
                                                                {auth.user?.permissions?.includes('view-customer-detail-report') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => {
                                                                                const params: any = { customer: customer.user_id };
                                                                                if (is_demo) {
                                                                                    const year = new Date().getFullYear();
                                                                                    params.start_date = `${year}-01-01`;
                                                                                    params.end_date = `${year}-12-31`;
                                                                                }
                                                                                router.visit(route('account.reports.customer-detail', params));
                                                                            }} className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700">
                                                                                <FileText className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('View Report')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user?.permissions?.includes('view-customers') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => setViewingItem(customer)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                                <Eye className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('View')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user?.permissions?.includes('edit-customers') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => openModal('edit', customer)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                                <EditIcon className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Edit')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user?.permissions?.includes('delete-customers') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => openDeleteDialog(customer.id)}
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
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={Building2}
                                    title="No customers found"
                                    description="Get started by creating your first customer."
                                    hasFilters={!!(filters.company_name || filters.customer_code || filters.tax_number)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-customers"
                                    onCreateClick={() => openModal('add')}
                                    createButtonText="Create Customer"
                                    className="h-auto"
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={customers}
                        routeName="account.customers.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} users={users} auth={auth} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Edit
                        customer={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View customer={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title="Delete Customer"
                message={deleteState.message}
                confirmText="Delete"
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
