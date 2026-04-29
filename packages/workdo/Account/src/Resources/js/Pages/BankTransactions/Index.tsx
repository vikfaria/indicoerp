import { useState } from 'react';
import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { FilterButton } from '@/components/ui/filter-button';
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { Input } from '@/components/ui/input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination } from "@/components/ui/pagination";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import NoRecordsFound from '@/components/no-records-found';
import { CreditCard as CreditCardIcon, CheckCircle, Circle, Upload, Download, WandSparkles } from "lucide-react";
import { formatDate, formatCurrency } from '@/utils/helpers';
import { usePageButtons } from '@/hooks/usePageButtons';
interface BankTransaction {
    id: number;
    bank_account_id: number;
    transaction_date: string;
    transaction_type: 'debit' | 'credit';
    reference_number: string;
    description: string;
    amount: number;
    running_balance: number;
    transaction_status: 'pending' | 'cleared' | 'cancelled';
    reconciliation_status: 'unreconciled' | 'reconciled';
    bank_account: {
        id: number;
        account_name: string;
        account_number: string;
    };
}

interface BankAccount {
    id: number;
    account_name: string;
    account_number: string;
}

interface BankTransactionsIndexProps {
    transactions: {
        data: BankTransaction[];
        links: any[];
        meta: any;
    };
    bankAccounts: BankAccount[];
    filters: {
        bank_account_id?: string;
        transaction_type?: string;
        search?: string;
    };
    auth: any;
}

export default function Index() {
    const { t } = useTranslation();
    const { transactions, bankAccounts, auth } = usePage<BankTransactionsIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        bank_account_id: urlParams.get('bank_account_id') || '',
        transaction_type: urlParams.get('transaction_type') || '',
        search: urlParams.get('search') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [showImportTools, setShowImportTools] = useState(false);

    const importForm = useForm({
        bank_account_id: urlParams.get('bank_account_id') || '',
        file: null as File | null,
    });

    const reconcileForm = useForm({
        bank_account_id: urlParams.get('bank_account_id') || '',
        from_date: '',
        to_date: '',
        tolerance: '0.01',
        day_window: '3',
    });

    const googleDriveButtons = usePageButtons('googleDriveBtn', { module: 'Transaction', settingKey: 'GoogleDrive Transaction' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Transaction', settingKey: 'OneDrive Transaction' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Account Transaction', settingKey: 'Dropbox Account Transaction' });
    const handleFilter = () => {
        router.get(route('account.bank-transactions.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('account.bank-transactions.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({
            bank_account_id: '',
            transaction_type: '',
            search: '',
        });
        router.get(route('account.bank-transactions.index'), {per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode});
    };

    const markReconciled = (id: number) => {
        router.post(route('account.bank-transactions.mark-reconciled', id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Page will refresh automatically
            }
        });
    };

    const handleImportCsv = (e: React.FormEvent) => {
        e.preventDefault();

        importForm.post(route('account.bank-transactions.import-csv'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                importForm.reset('file');
            }
        });
    };

    const handleAutoReconcile = (e: React.FormEvent) => {
        e.preventDefault();

        reconcileForm.post(route('account.bank-transactions.auto-reconcile'), {
            preserveScroll: true,
        });
    };

    const tableColumns = [
        {
            key: 'transaction_date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'bank_account',
            header: t('Bank Account'),
            sortable: false,
            render: (value: any) => `${value.account_name} (${value.account_number})`
        },
        {
            key: 'reference_number',
            header: t('Reference'),
            sortable: true
        },
        {
            key: 'transaction_type',
            header: t('Type'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'debit' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                }`}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        {
            key: 'amount',
            header: t('Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'running_balance',
            header: t('Balance'),
            sortable: false,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'description',
            header: t('Description'),
            sortable: false
        },
        {
            key: 'transaction_status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm ${
                    value === 'cleared' ? 'bg-green-100 text-green-800' :
                    value === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                }`}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, transaction: BankTransaction) => (
                <div className="flex items-center justify-center">
                    <TooltipProvider>
                        {transaction.reconciliation_status === 'unreconciled' ? (
                            auth.user?.permissions?.includes('reconcile-bank-transactions') ? (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => markReconciled(transaction.id)}
                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                        >
                                            <Circle className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Mark as Reconciled')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            ) : (
                                <Circle className="h-4 w-4 text-gray-400" />
                            )
                        ) : (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <div className="inline-flex">
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Reconciled')}</p>
                                </TooltipContent>
                            </Tooltip>
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
                {label: t('Bank Transactions')}
            ]}
            pageTitle={t('Manage Bank Transactions')}
            pageActions={
                <div className="flex items-center gap-2">
                    <TooltipProvider>
                        {googleDriveButtons.map((button) => (
                            <span key={button.id}>{button.component}</span>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <span key={button.id}>{button.component}</span>
                        ))}
                        {dropboxBtn.map((button) => (
                            <span key={button.id}>{button.component}</span>
                        ))}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Bank Transactions')} />
            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search transactions...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setShowImportTools(!showImportTools)}
                            >
                                <Upload className="h-4 w-4 mr-2" />
                                {t('Import & Reconcile')}
                            </Button>
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="account.bank-transactions.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="account.bank-transactions.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.bank_account_id, filters.transaction_type].filter(f => f !== '' && f !== null && f !== undefined).length;
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
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Bank Account')}</label>
                                <Select value={filters.bank_account_id} onValueChange={(value) => setFilters({...filters, bank_account_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Bank Account')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankAccounts.map((account) => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_name} ({account.account_number})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Transaction Type')}</label>
                                <Select value={filters.transaction_type} onValueChange={(value) => setFilters({...filters, transaction_type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="debit">{t('Debit')}</SelectItem>
                                        <SelectItem value="credit">{t('Credit')}</SelectItem>
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

                {showImportTools && (
                    <CardContent className="p-6 bg-emerald-50/30 border-b space-y-4">
                        <div className="flex items-center justify-between">
                            <h4 className="font-medium text-sm">{t('Bank Statement Import')}</h4>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => window.open(route('account.bank-transactions.template'), '_blank')}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                {t('Download CSV Template')}
                            </Button>
                        </div>

                        <form className="grid grid-cols-1 md:grid-cols-4 gap-3" onSubmit={handleImportCsv}>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Bank Account')}</label>
                                <select
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={importForm.data.bank_account_id}
                                    onChange={(e) => importForm.setData('bank_account_id', e.target.value)}
                                    required
                                >
                                    <option value="">{t('Select bank account')}</option>
                                    {bankAccounts.map((account) => (
                                        <option key={account.id} value={String(account.id)}>
                                            {account.account_name} ({account.account_number})
                                        </option>
                                    ))}
                                </select>
                                {importForm.errors.bank_account_id && <p className="text-red-600 text-xs mt-1">{importForm.errors.bank_account_id}</p>}
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('CSV File')}</label>
                                <Input
                                    type="file"
                                    accept=".csv,.txt"
                                    onChange={(e) => importForm.setData('file', e.target.files?.[0] || null)}
                                    required
                                />
                                {importForm.errors.file && <p className="text-red-600 text-xs mt-1">{importForm.errors.file}</p>}
                            </div>
                            <div className="flex items-end">
                                <Button type="submit" size="sm" disabled={importForm.processing || !importForm.data.file} className="w-full">
                                    <Upload className="h-4 w-4 mr-2" />
                                    {importForm.processing ? t('Importing...') : t('Import CSV')}
                                </Button>
                            </div>
                        </form>

                        <div className="pt-2 border-t">
                            <h4 className="font-medium text-sm mb-3">{t('Auto Reconciliation')}</h4>
                            <form className="grid grid-cols-1 md:grid-cols-6 gap-3" onSubmit={handleAutoReconcile}>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Bank Account')}</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={reconcileForm.data.bank_account_id}
                                        onChange={(e) => reconcileForm.setData('bank_account_id', e.target.value)}
                                    >
                                        <option value="">{t('All accounts')}</option>
                                        {bankAccounts.map((account) => (
                                            <option key={account.id} value={String(account.id)}>
                                                {account.account_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('From')}</label>
                                    <Input type="date" value={reconcileForm.data.from_date} onChange={(e) => reconcileForm.setData('from_date', e.target.value)} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('To')}</label>
                                    <Input type="date" value={reconcileForm.data.to_date} onChange={(e) => reconcileForm.setData('to_date', e.target.value)} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Tolerance')}</label>
                                    <Input value={reconcileForm.data.tolerance} onChange={(e) => reconcileForm.setData('tolerance', e.target.value)} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Day Window')}</label>
                                    <Input value={reconcileForm.data.day_window} onChange={(e) => reconcileForm.setData('day_window', e.target.value)} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" size="sm" disabled={reconcileForm.processing} className="w-full">
                                        <WandSparkles className="h-4 w-4 mr-2" />
                                        {reconcileForm.processing ? t('Reconciling...') : t('Auto Reconcile')}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={transactions?.data || []}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={CreditCardIcon}
                                            title={t('No transactions found')}
                                            description={t('Bank transactions will appear here once created.')}
                                            hasFilters={!!(filters.search || filters.bank_account_id || filters.transaction_type)}
                                            onClearFilters={clearFilters}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {transactions?.data?.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {transactions?.data?.map((transaction) => (
                                        <Card key={transaction.id} className="border border-gray-200 flex flex-col">
                                            <div className="p-4 flex-1">
                                                <div className="flex items-center gap-3 mb-3">
                                                    <div className="w-12 h-12 bg-primary rounded-lg border flex items-center justify-center">
                                                        <CreditCardIcon className="w-6 h-6 text-primary-foreground" />
                                                    </div>
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900">{transaction.reference_number}</h3>
                                                    </div>
                                                </div>

                                                <div className="space-y-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-2">{t('Bank Account')}</p>
                                                        <p className="text-xs text-gray-900 truncate">{transaction.bank_account.account_name} ({transaction.bank_account.account_number})</p>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-sm text-gray-600">{t('Date')}</span>
                                                        <span className="text-sm font-medium">{formatDate(transaction.transaction_date)}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-sm text-gray-600">{t('Type')}</span>
                                                        <span className={`px-2 py-1 rounded-full text-sm ${
                                                            transaction.transaction_type === 'debit' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                                        }`}>
                                                            {t(transaction.transaction_type.charAt(0).toUpperCase() + transaction.transaction_type.slice(1))}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-sm text-gray-600">{t('Amount')}</span>
                                                        <span className="text-sm font-medium">{formatCurrency(transaction.amount)}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-sm text-gray-600">{t('Balance')}</span>
                                                        <span className="text-sm font-medium">{formatCurrency(transaction.running_balance)}</span>
                                                    </div>
                                                    {transaction.description && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Description')}</p>
                                                            <p className="text-xs text-gray-900">{transaction.description}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between p-3 border-t bg-gray-50/50">
                                                <span className={`px-2 py-1 rounded-full text-sm ${
                                                    transaction.transaction_status === 'cleared' ? 'bg-green-100 text-green-800' :
                                                    transaction.transaction_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {t(transaction.transaction_status.charAt(0).toUpperCase() + transaction.transaction_status.slice(1))}
                                                </span>
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {transaction.reconciliation_status === 'unreconciled' ? (
                                                            auth.user?.permissions?.includes('reconcile-bank-transactions') ? (
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => markReconciled(transaction.id)}
                                                                            className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                                        >
                                                                            <Circle className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{t('Mark as Reconciled')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            ) : (
                                                                <Circle className="h-4 w-4 text-gray-400" />
                                                            )
                                                        ) : (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <div className="inline-flex">
                                                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                                                    </div>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('Reconciled')}</p>
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
                                    icon={CreditCardIcon}
                                    title={t('No transactions found')}
                                    description={t('Bank transactions will appear here once created.')}
                                    hasFilters={!!(filters.search || filters.bank_account_id || filters.transaction_type)}
                                    onClearFilters={clearFilters}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={transactions || { data: [], links: [], meta: {} }}
                        routeName="account.bank-transactions.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
