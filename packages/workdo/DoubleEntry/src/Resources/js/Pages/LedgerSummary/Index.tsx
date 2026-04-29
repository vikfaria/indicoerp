import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { DataTable } from "@/components/ui/data-table";
import { FileText, Printer } from "lucide-react";
import NoRecordsFound from '@/components/no-records-found';
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { SearchInput } from "@/components/ui/search-input";
import { formatDate, formatCurrency } from '@/utils/helpers';

interface Account {
    id: number;
    account_code: string;
    account_name: string;
}

interface LedgerEntry {
    id: number;
    journal_date: string;
    reference_type: string;
    journal_description: string;
    description: string;
    debit_amount: number;
    credit_amount: number;
    account_code: string;
    account_name: string;
}

interface LedgerSummaryProps {
    entries: {
        data: LedgerEntry[];
        links: any[];
        meta: any;
    };
    accounts: Account[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { entries, accounts, auth } = usePage<LedgerSummaryProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        search: urlParams.get('search') || '',
        account_id: urlParams.get('account_id') || '',
        from_date: urlParams.get('from_date') || '',
        to_date: urlParams.get('to_date') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [showFilters, setShowFilters] = useState(false);


    const handleFilter = () => {
        router.get(route('double-entry.ledger-summary.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('double-entry.ledger-summary.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', account_id: '', from_date: '', to_date: '' });
        router.get(route('double-entry.ledger-summary.index'), {per_page: perPage});
    };

    const tableColumns = [
        {
            key: 'journal_date',
            header: t('Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'account_code',
            header: t('Account Code'),
            sortable: true
        },
        {
            key: 'account_name',
            header: t('Account Name'),
            sortable: true
        },
        {
            key: 'reference_type',
            header: t('Reference'),
            sortable: false
        },
        {
            key: 'description',
            header: t('Description'),
            sortable: false,
            render: (value: string, entry: LedgerEntry) => value || entry.journal_description
        },
        {
            key: 'debit_amount',
            header: t('Debit'),
            sortable: false,
            render: (value: number) => value > 0 ? formatCurrency(value) : '-'
        },
        {
            key: 'credit_amount',
            header: t('Credit'),
            sortable: false,
            render: (value: number) => value > 0 ? formatCurrency(value) : '-'
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Double Entry')},
                {label: t('Ledger Summary')}
            ]}
            pageTitle={t('Ledger Summary')}
        >
            <Head title={t('Ledger Summary')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search ledger entries...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="double-entry.ledger-summary.index"
                                filters={filters}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.account_id, filters.from_date, filters.to_date].filter(f => f !== '').length;
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
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Account')}</label>
                                <Select value={filters.account_id} onValueChange={(value) => setFilters({...filters, account_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('All Accounts')} />
                                    </SelectTrigger>
                                    <SelectContent searchable>
                                        {accounts.map((account) => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_code} - {account.account_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('From Date')}</label>
                                <DatePicker
                                    value={filters.from_date}
                                    onChange={(value) => setFilters({...filters, from_date: value})}
                                    placeholder={t('Select from date')}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('To Date')}</label>
                                <DatePicker
                                    value={filters.to_date}
                                    onChange={(value) => setFilters({...filters, to_date: value})}
                                    placeholder={t('Select to date')}
                                />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                                {auth.user?.permissions?.includes('print-ledger-summary') && (
                                    <Button variant="outline" size="sm" onClick={() => {
                                        const printUrl = route('double-entry.ledger-summary.print') + `?from_date=${filters.from_date}&to_date=${filters.to_date}&account_id=${filters.account_id}&download=pdf`;
                                        window.open(printUrl, '_blank');
                                    }}>
                                        <Printer className="h-4 w-4 mr-2" />
                                        {t('Download PDF')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[1000px]">
                            <DataTable
                                data={entries?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={FileText}
                                        title={t('No ledger entries found')}
                                        description={t('No journal entries found for the selected filters.')}
                                        hasFilters={!!(filters.search || filters.account_id || filters.from_date || filters.to_date)}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={entries || { data: [], links: [], meta: {} }}
                        routeName="double-entry.ledger-summary.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
