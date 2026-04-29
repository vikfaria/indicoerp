import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit, Trash2, Eye, CheckCircle, DollarSign } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Badge } from "@/components/ui/badge";
import NoRecordsFound from '@/components/no-records-found';
import { formatDate, formatCurrency } from '@/utils/helpers';
import Create from './Create';
import EditExpense from './Edit';
import View from './View';

interface Expense {
    id: number;
    expense_number: string;
    expense_date: string;
    category: { id: number; category_name: string };
    bank_account: { id: number; account_name: string };
    chart_of_account?: { id: number; account_code: string; account_name: string };
    amount: string;
    reference_number: string;
    status: 'draft' | 'approved' | 'posted';
    approved_by: { id: number; name: string } | null;
    created_at: string;
}

interface Category {
    id: number;
    category_name: string;
}

interface BankAccount {
    id: number;
    account_name: string;
}

interface ChartOfAccount {
    id: number;
    account_code: string;
    account_name: string;
}

interface ExpenseFilters {
    search: string;
    category_id: string;
    status: string;
    date_range: string;
    bank_account_id: string;
}

interface ExpenseIndexProps {
    expenses: {
        data: Expense[];
        meta?: any;
    };
    categories: Category[];
    bankAccounts: BankAccount[];
    chartOfAccounts: ChartOfAccount[];
    filters?: ExpenseFilters;
    auth: any;
}

export default function Index() {
    const { t } = useTranslation();
    const { expenses, categories, bankAccounts, chartOfAccounts, filters: initialFilters, auth } = usePage<ExpenseIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<ExpenseFilters>({
        search: initialFilters?.search || '',
        category_id: initialFilters?.category_id || '',
        status: initialFilters?.status || '',
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
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState<{
        isOpen: boolean;
        mode: string;
        data: Expense | null;
    }>({
        isOpen: false,
        mode: '',
        data: null
    });
    const [editingItem, setEditingItem] = useState<Expense | null>(null);
    const [viewingItem, setViewingItem] = useState<Expense | null>(null);

    const openModal = (mode: string, data: Expense | null = null) => {
        setModalState({
            isOpen: true,
            mode,
            data
        });
    };

    const closeModal = () => {
        setModalState({
            isOpen: false,
            mode: '',
            data: null
        });
    };


    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'account.expenses.destroy',
        defaultMessage: t('Are you sure you want to delete this expense?')
    });

    const handleFilter = () => {
        const filterParams = {...filters};

        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('account.expenses.index'), {...filterParams, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);

        const filterParams = {...filters};
        if (filters.date_range) {
            const [fromDate, toDate] = filters.date_range.split(' - ');
            filterParams.date_from = fromDate;
            filterParams.date_to = toDate;
        }
        delete filterParams.date_range;

        router.get(route('account.expenses.index'), {...filterParams, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', category_id: '', status: '', date_range: '', bank_account_id: '' });
        router.get(route('account.expenses.index'), {per_page: perPage, view: viewMode});
    };

    const tableColumns = [
        {
            key: 'expense_number',
            header: t('Expense Number'),
            sortable: true,
            render: (value: string, expense: Expense) => (
                <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => setViewingItem(expense)}>{value}</span>
            )
        },
        {
            key: 'expense_date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'category.category_name',
            header: t('Category'),
            render: (value: any, row: Expense) => row.category?.category_name || '-'
        },
        {
            key: 'bank_account.account_name',
            header: t('Bank Account'),
            render: (value: any, row: Expense) => row.bank_account?.account_name || '-'
        },
        {
            key: 'chart_of_account.account_name',
            header: t('Chart of Account'),
            render: (value: any, row: Expense) => row.chart_of_account ? `${row.chart_of_account.account_code} - ${row.chart_of_account.account_name}` : '-'
        },
        {
            key: 'amount',
            header: t('Amount'),
            sortable: true,
            render: (value: string) => formatCurrency(value)
        },
        {
            key: 'reference_number',
            header: t('Reference'),
            render: (value: string) => value || '-'
        },
        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'posted' ? 'bg-green-100 text-green-800' :
                    value === 'approved' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                }`}>
                    {value === 'posted' ? t('Posted') :
                     value === 'approved' ? t('Approved') :
                     t('Draft')}
                </span>
            )
        },
        {
            key: 'approved_by.name',
            header: t('Approved By'),
            render: (value: any, row: Expense) => row.approved_by?.name || '-'
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, expense: Expense) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {expense.status === 'draft' && auth.user.permissions.includes('approve-expenses') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('account.expenses.approve', expense.id))} className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700">
                                        <CheckCircle className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Approve')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {expense.status === 'approved' && auth.user.permissions.includes('post-expenses') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('account.expenses.post', expense.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <CheckCircle className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Post')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user.permissions.includes('view-expenses') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(expense)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {expense.status === 'draft' && (
                            <>
                                {auth.user.permissions.includes('edit-expenses') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => setEditingItem(expense)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Edit')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user.permissions.includes('delete-expenses') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(expense.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Delete')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </>
                        )}
                    </TooltipProvider>
                </div>
            )
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Accounting'), url: route('account.index')},
                {label: t('Expenses')}
            ]}
            pageTitle={t('Manage Expenses')}
            pageActions={
                auth.user.permissions.includes('create-expenses') && (
                    <TooltipProvider>
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
                    </TooltipProvider>
                )
            }
        >
            <Head title={t('Expenses')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search || ''}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search expenses...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="account.expenses.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="account.expenses.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.category_id, filters.status, filters.date_range, filters.bank_account_id].filter(Boolean).length;
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
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Category')}</label>
                                <Select value={filters.category_id} onValueChange={(value) => setFilters({...filters, category_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.category_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="posted">{t('Posted')}</SelectItem>
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
                            {auth.user?.permissions?.includes('manage-bank-accounts') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Bank Account')}</label>
                                    <Select value={filters.bank_account_id} onValueChange={(value) => setFilters({...filters, bank_account_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by bank account')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {bankAccounts.map((account) => (
                                                <SelectItem key={account.id} value={account.id.toString()}>
                                                    {account.account_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
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
                                    data={expenses.data || expenses}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={DollarSign}
                                            title={t('No expenses found')}
                                            description={t('Get started by creating your first expense.')}
                                            hasFilters={!!(filters.search || filters.category_id || filters.status || filters.date_range || filters.bank_account_id)}
                                            onClearFilters={clearFilters}
                                            onCreateClick={auth.user.permissions.includes('create-expenses') ? () => openModal('add') : undefined}
                                            createButtonText={t('Create Expense')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {(expenses.data || expenses).length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5 gap-4">
                                    {(expenses.data || expenses).map((expense: Expense) => (
                                        <Card key={expense.id} className="border border-gray-200 flex flex-col">
                                            <div className="p-4 flex-1">
                                                <div className="mb-3">
                                                    <h3 className="font-semibold text-base text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => setViewingItem(expense)}>{expense.expense_number}</h3>
                                                </div>

                                                <div className="space-y-3 mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Category')}</p>
                                                        <p className="text-sm text-gray-900 truncate font-medium">{expense.category?.category_name}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Date')}</p>
                                                            <p className="text-xs text-gray-900">{formatDate(expense.expense_date)}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1 text-end">{t('Bank Account')}</p>
                                                            <p className="text-xs text-gray-900 text-end">{expense.bank_account?.account_name || '-'}</p>
                                                        </div>
                                                    </div>
                                                    {expense.chart_of_account && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Chart of Account')}</p>
                                                            <p className="text-xs text-gray-900">{expense.chart_of_account.account_code} - {expense.chart_of_account.account_name}</p>
                                                        </div>
                                                    )}
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Amount')}</span>
                                                            <span className="text-lg font-bold text-green-600">{formatCurrency(expense.amount)}</span>
                                                        </div>
                                                    </div>
                                                    {expense.reference_number && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Reference')}</p>
                                                            <p className="text-xs text-gray-900">{expense.reference_number}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-between p-3 border-t bg-gray-50/50">
                                                <span className={`px-2 py-1 rounded-full text-sm ${
                                                    expense.status === 'posted' ? 'bg-green-100 text-green-800' :
                                                    expense.status === 'approved' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {expense.status === 'posted' ? t('Posted') :
                                                     expense.status === 'approved' ? t('Approved') :
                                                     t('Draft')}
                                                </span>
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {expense.status === 'draft' && auth.user.permissions.includes('approve-expenses') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('account.expenses.approve', expense.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                        <CheckCircle className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Approve')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {expense.status === 'approved' && auth.user.permissions.includes('post-expenses') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('account.expenses.post', expense.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                        <CheckCircle className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Post')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {auth.user.permissions.includes('view-expenses') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => setViewingItem(expense)} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('View')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {expense.status === 'draft' && (
                                                            <>
                                                                {auth.user.permissions.includes('edit-expenses') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => setEditingItem(expense)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                                <Edit className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Edit')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {auth.user.permissions.includes('delete-expenses') && (
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(expense.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                                                <Trash2 className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{t('Delete')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                            </>
                                                        )}
                                                    </TooltipProvider>
                                                </div>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={DollarSign}
                                    title={t('No expenses found')}
                                    description={t('Get started by creating your first expense.')}
                                    hasFilters={!!(filters.search || filters.category_id || filters.status || filters.date_range || filters.bank_account_id)}
                                    onClearFilters={clearFilters}
                                    onCreateClick={auth.user.permissions.includes('create-expenses') ? () => openModal('add') : undefined}
                                    createButtonText={t('Create Expense')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={expenses}
                        routeName="account.expenses.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create
                        categories={categories}
                        bankAccounts={bankAccounts}
                        chartOfAccounts={chartOfAccounts}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <Dialog open={!!editingItem} onOpenChange={() => setEditingItem(null)}>
                {editingItem && (
                    <EditExpense
                        expense={editingItem}
                        categories={categories}
                        bankAccounts={bankAccounts}
                        chartOfAccounts={chartOfAccounts}
                        onSuccess={() => setEditingItem(null)}
                    />
                )}
            </Dialog>

            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View expense={viewingItem} />}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Expense')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
