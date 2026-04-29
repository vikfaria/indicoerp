import { useState, useMemo, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Package, Edit, Trash2, Eye, Image, Download } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import NoRecordsFound from '@/components/no-records-found';
import { formatCurrency, getImagePath } from '@/utils/helpers';
import { Item, ItemsIndexProps, ItemFilters } from './types';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Index() {
    const { t } = useTranslation();
    const { items, categories, auth } = usePage<ItemsIndexProps>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), []);

    // Item types same as Create page
    const itemTypes = ['product', 'service', 'part'];

    const [filters, setFilters] = useState<ItemFilters>({
        name: urlParams.get('name') || '',
        type: urlParams.get('type') || '',
        category_id: urlParams.get('category_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');


    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);

    const pageButtons = usePageButtons('googleDriveBtn', { module: 'Products', settingKey: 'GoogleDrive Products' });
    const oneDriveButtons = usePageButtons('oneDriveBtn', { module: 'Products', settingKey: 'OneDrive Products' });
    const hubspotButtons = usePageButtons('hubspotBtn', { module: 'Products', settingKey: 'HubSpot Products' });
    const dropboxBtn = usePageButtons('dropboxBtn', { module: 'Product & Service Products', settingKey: 'Dropbox Product & Service Products' });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'product-service.items.destroy',
        defaultMessage: t('Are you sure you want to delete this item?')
    });
    const handleFilter = () => {
        router.get(route('product-service.items.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('product-service.items.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', type: '', category_id: '' });
        router.get(route('product-service.items.index'), {per_page: perPage, view: viewMode});
    };



    const tableColumns = [
        {
            key: 'image',
            header: t('Image'),
            render: (value: string) => {
                if (!value) {
                    return (
                        <div className="w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center">
                            <Image className="w-6 h-6 text-gray-400" />
                        </div>
                    );
                }
                const isImage = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(value);
                let imageUrl = getImagePath(value);
                return isImage ? (
                    <div className="relative w-12 h-12">
                        <img
                            src={imageUrl}
                            alt="Image"
                            className="w-12 h-12 object-cover rounded-md border hover:scale-110 transition-transform cursor-pointer"
                            onClick={() => window.open(imageUrl, '_blank')}
                            onError={(e) => {
                                const target = e.target as HTMLImageElement;
                                target.style.display = 'none';
                                const fallback = target.nextElementSibling as HTMLElement;
                                if (fallback) fallback.classList.remove('hidden');
                            }}
                        />
                        <div className="hidden w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center">
                            <Image className="w-6 h-6 text-gray-400" />
                        </div>
                    </div>
                ) : (
                    <div className="w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors" onClick={() => {
                        const link = document.createElement('a');
                        link.href = getImagePath(value);
                        link.download = value.split('/').pop() || 'file';
                        link.click();
                    }}>
                        <Download className="w-6 h-6 text-gray-600" />
                    </div>
                );
            }
        },
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'sku',
            header: t('SKU'),
            sortable: true
        },
        {
            key: 'sale_price',
            header: t('Sale Price'),
            sortable: true,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'purchase_price',
            header: t('Purchase Price'),
            sortable: true,
            render: (value: number) => value ? formatCurrency(value) : '-'
        },
        {
            key: 'category_id',
            header: t('Category'),
            render: (value: number, item: Item) => item.category?.name || '-'
        },
        {
            key: 'unit',
            header: t('Unit'),
            render: (value: string, item: Item) => item.unit_relation?.unit_name || '-'
        },
        {
            key: 'total_quantity',
            header: t('Quantity'),
            sortable: false,
            render: (value: number) => Math.floor(value) || 0
        },
        {
            key: 'type',
            header: t('Type'),
            sortable: true,
            render: (value: string) => (
                <span className="px-2 py-1 rounded-full text-sm bg-green-100 text-green-800 capitalize">
                    {t(value)}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-product-service-item', 'edit-product-service-item', 'delete-product-service-item'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: Item) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('view-product-service-item') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.visit(route('product-service.items.show', item.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('edit-product-service-item') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="ghost" size="sm" onClick={() => router.visit(route('product-service.items.edit', item.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('delete-product-service-item') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteDialog(item.id)}
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
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    {label: t('Product & Service')},
                    {label: t('Items')}
                ]}
                pageTitle={t('Manage Items')}
                pageActions={
                    <div className="flex gap-2">
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {oneDriveButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {dropboxBtn.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {hubspotButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                        {auth.user?.permissions?.includes('manage-stock') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button variant="outline" size="sm" onClick={() => router.visit(route('product-service.stock.index'))}>
                                        <Package className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Add Stock')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('create-product-service-item') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => router.visit(route('product-service.items.create'))}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                }
            >
            <Head title={t('Items')} />

            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search items...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="product-service.items.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="product-service.items.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.type, filters.category_id].filter(Boolean).length;
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Item Type')}</label>
                                <Select value={filters.type} onValueChange={(value) => setFilters({...filters, type: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by item type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {itemTypes.map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {t(type)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Category')}</label>
                                <Select value={filters.category_id} onValueChange={(value) => setFilters({...filters, category_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.name}
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

                {/* Table Content */}
                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={items.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={Package}
                                            title={t('No items found')}
                                            description={t('Get started by creating your first item.')}
                                            hasFilters={!!(filters.name || filters.type || filters.category_id)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-product-service-item"
                                            onCreateClick={() => router.visit(route('product-service.items.create'))}
                                            createButtonText={t('Create Item')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-6">
                            {items.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {items.data.map((item) => (
                                        <Card key={item.id} className="border border-gray-200 h-full flex flex-col">
                                            <div className="p-4 flex flex-col flex-1">
                                                <div className="flex items-center gap-3 mb-3">
                                                {item.image ? (
                                                    <div className="relative w-12 h-12">
                                                        <img
                                                            src={getImagePath(item.image)}
                                                            alt={item.name}
                                                            className="w-12 h-12 object-cover rounded cursor-pointer hover:scale-110 transition-transform"
                                                            onClick={() => window.open(getImagePath(item.image), '_blank')}
                                                            onError={(e) => {
                                                                const target = e.target as HTMLImageElement;
                                                                target.style.display = 'none';
                                                                const fallback = target.nextElementSibling as HTMLElement;
                                                                if (fallback) fallback.classList.remove('hidden');
                                                            }}
                                                        />
                                                        <div className="hidden w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center">
                                                            <Image className="w-6 h-6 text-gray-400" />
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center">
                                                        <Image className="w-6 h-6 text-gray-400" />
                                                    </div>
                                                )}
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-base text-gray-900">{item.name}</h3>
                                                    </div>
                                                </div>

                                                <div className="space-y-2 mb-3 flex-1">
                                                    {item.sku && (
                                                        <div className="flex justify-between">
                                                            <span className="text-sm text-gray-600">{t('SKU')}</span>
                                                            <span className="text-sm font-medium">{item.sku}</span>
                                                        </div>
                                                    )}
                                                    {item.sale_price && (
                                                        <div className="flex justify-between">
                                                            <span className="text-sm text-gray-600">{t('Sale Price')}</span>
                                                            <span className="text-sm font-medium">{formatCurrency(item.sale_price)}</span>
                                                        </div>
                                                    )}
                                                    {item.purchase_price && (
                                                        <div className="flex justify-between">
                                                            <span className="text-sm text-gray-600">{t('Purchase Price')}</span>
                                                            <span className="text-sm font-medium">{formatCurrency(item.purchase_price)}</span>
                                                        </div>
                                                    )}
                                                    <div className="flex justify-between">
                                                        <span className="text-sm text-gray-600">{t('Quantity')}</span>
                                                        <span className="text-sm font-medium">{Math.floor(item.total_quantity) || 0}</span>
                                                    </div>
                                                    {item.category && (
                                                        <div className="flex justify-between">
                                                            <span className="text-sm text-gray-600">{t('Category')}</span>
                                                            <span className="text-sm font-medium">{item.category.name}</span>
                                                        </div>
                                                    )}
                                                    {item.unit_relation && (
                                                        <div className="flex justify-between">
                                                            <span className="text-sm text-gray-600">{t('Unit')}</span>
                                                            <span className="text-sm font-medium">{item.unit_relation.unit_name}</span>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex items-center justify-between pt-3 border-t">
                                                    <span className="px-2 py-1 rounded-full text-sm bg-green-100 text-green-800 capitalize">
                                                        {t(item.type)}
                                                    </span>
                                                    <div className="flex gap-1">
                                                <TooltipProvider>
                                                    {auth.user?.permissions?.includes('view-product-service-item') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => router.visit(route('product-service.items.show', item.id))} className="h-9 w-9 p-0 text-green-600 hover:text-green-700 hover:bg-green-50">
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent><p>{t('View')}</p></TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('edit-product-service-item') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button variant="ghost" size="sm" onClick={() => router.visit(route('product-service.items.edit', item.id))} className="h-9 w-9 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent><p>{t('Edit')}</p></TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {auth.user?.permissions?.includes('delete-product-service-item') && (
                                                        <Tooltip delayDuration={300}>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => openDeleteDialog(item.id)}
                                                                    className="h-9 w-9 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                        </TooltipProvider>
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={Package}
                                    title={t('No items found')}
                                    description={t('Get started by creating your first item.')}
                                    hasFilters={!!(filters.name || filters.type || filters.category_id)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-product-service-item"
                                    onCreateClick={() => router.visit(route('product-service.items.create'))}
                                    createButtonText={t('Create Item')}
                                    className="h-auto"
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={items}
                        routeName="product-service.items.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Item')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />

            </AuthenticatedLayout>
        </TooltipProvider>
    );
}
