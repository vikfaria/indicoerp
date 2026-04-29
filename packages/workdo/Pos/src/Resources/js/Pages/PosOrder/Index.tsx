import { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/ui/data-table';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from '@/components/ui/pagination';
import { Eye, ShoppingCart } from 'lucide-react';
import { formatCurrency } from '@/utils/helpers';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import NoRecordsFound from '@/components/no-records-found';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { usePageButtons } from '@/hooks/usePageButtons';

interface PosSale {
    id: number;
    sale_number: string;
    customer_id?: number;
    customer?: {
        name: string;
        email: string;
    };
    warehouse?: {
        name: string;
    };
    tax_amount: number;
    pos_date: string;
    created_at: string;
    items_count: number;
    items?: Array<{
        total_amount: number;
    }>;
}

interface IndexProps {
    sales: {
        data: PosSale[];
        links: any[];
        meta: any;
    };
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { sales, auth } = usePage<IndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);
    
    const [filters, setFilters] = useState({
        search: urlParams.get('search') || '',
        customer: urlParams.get('customer') || '',
        warehouse: urlParams.get('warehouse') || ''
    });
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);

    const pageButtons = usePageButtons('googleDriveBtn', { module: 'POS Order', settingKey: 'GoogleDrive POS Order' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'POS Order', settingKey: 'OneDrive POS Order' });

    const handleFilter = () => {
        router.get(route('pos.orders'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', customer: '', warehouse: '' });
        router.get(route('pos.orders'), {per_page: perPage, view: viewMode});
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('pos.orders'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const tableColumns = [
        {
            key: 'sale_number',
            header: t('Sale Number'),
            sortable: true,
            render: (value: string, sale: PosSale) =>
                auth.user?.permissions?.includes('view-pos-orders') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('pos.show', sale.id))}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'customer',
            header: t('Customer'),
            render: (_: any, sale: PosSale) => sale.customer?.name || t('Walk-in Customer')
        },
        {
            key: 'warehouse',
            header: t('Warehouse'),
            render: (_: any, sale: PosSale) => sale.warehouse?.name
        },
        {
            key: 'total',
            header: t('Total'),
            sortable: false,
            render: (_: any, sale: PosSale) => (
                <span>
                    {formatCurrency(sale.total || 0)}
                </span>
            )
        },
        ...(auth.user?.permissions?.includes('view-pos-orders') ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, sale: PosSale) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="sm" onClick={() => router.get(route('pos.show', sale.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('View')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('POS'), url: route('pos.index') },
                { label: t('POS Orders')}
            ]}
            pageTitle={t('POS Orders')}
            pageActions={
                <div className="flex items-center gap-2">
                    {pageButtons.map((button) => (
                        <div key={button.id} >
                            {button.component}
                        </div>
                    ))}
                    {oneDriveButtons.map((button) => (
                        <div key={button.id} >
                            {button.component}
                        </div>
                    ))}
                </div>
            }
        >
            <Head title={t('POS Orders')} /> 
            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by order number, customer, warehouse...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="pos.orders"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="pos.orders"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.customer, filters.warehouse].filter(Boolean).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Customer')}</label>
                                <Input
                                    placeholder={t('Filter by customer')}
                                    value={filters.customer}
                                    onChange={(e) => setFilters({...filters, customer: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Warehouse')}</label>
                                <Input
                                    placeholder={t('Filter by warehouse')}
                                    value={filters.warehouse}
                                    onChange={(e) => setFilters({...filters, warehouse: e.target.value})}
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
                                    data={sales.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={ShoppingCart}
                                            title={t('No orders found')}
                                            description={t('Get started by creating your first POS order.')}
                                            hasFilters={!!(filters.search || filters.customer || filters.warehouse)}
                                            onClearFilters={clearFilters}
                                            createPermission="manage-pos"
                                            onCreateClick={() => router.visit(route('pos.create'))}
                                            createButtonText={t('Create Order')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {sales.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {sales.data.map((sale) => (
                                        <Card key={sale.id} className="border border-gray-200">
                                            <div className="p-4">
                                                <div className="flex items-center gap-3 mb-3">
                                                    <div className="p-2 bg-primary/10 rounded-lg">
                                                        <ShoppingCart className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div className="flex-1">
                                                        {auth.user?.permissions?.includes('view-pos-orders') ? (
                                                            <h3 className="font-semibold text-base text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('pos.show', sale.id))}>{sale.sale_number}</h3>
                                                        ) : (
                                                            <h3 className="font-semibold text-base text-gray-900">{sale.sale_number}</h3>
                                                        )}
                                                    </div>
                                                    {auth.user?.permissions?.includes('view-pos-orders') && (
                                                        <TooltipProvider>
                                                            <Tooltip delayDuration={300}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('pos.show', sale.id))} className="h-8 w-8 p-0 text-green-600">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>{t('View')}</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                    )}
                                                </div>

                                                <div className="space-y-3 mb-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Customer')}</p>
                                                        <p className="text-xs text-gray-900 truncate">{sale.customer?.name || t('Walk-in Customer')}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Warehouse')}</p>
                                                        <p className="text-xs text-gray-900 truncate">{sale.warehouse?.name}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Total')}</p>
                                                        <p className="text-sm">{formatCurrency(sale.total || 0)}</p>
                                                    </div>
                                                </div>


                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={ShoppingCart}
                                    title={t('No orders found')}
                                    description={t('Get started by creating your first POS order.')}
                                    hasFilters={!!(filters.search || filters.customer || filters.warehouse)}
                                    onClearFilters={clearFilters}
                                    createPermission="manage-pos"
                                    onCreateClick={() => router.visit(route('pos.create'))}
                                    createButtonText={t('Create Order')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={sales}
                        routeName="pos.orders"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}