import { useState, useMemo } from 'react';
import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Plus, Package } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import NoRecordsFound from '@/components/no-records-found';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface StockItem {
    id: number;
    name: string;
    sku: string;
    total_quantity: number;
}

interface Warehouse {
    id: number;
    name: string;
}

interface StockIndexProps {
    stocks: {
        data: StockItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    warehouses: Warehouse[];
    auth: {
        user: {
            permissions: string[];
        };
    };
}

interface StockFilters {
    name: string;
}

export default function Index() {
    const { t } = useTranslation();
    const { stocks, warehouses, auth } = usePage<StockIndexProps>().props;
    const urlParams = useMemo(() => new URLSearchParams(window.location.search), []);

    const [filters, setFilters] = useState<StockFilters>({
        name: urlParams.get('name') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<StockItem | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        product_id: '',
        warehouse_id: '',
        quantity: ''
    });


    const handleFilter = () => {
        router.get(route('product-service.stock.index'), {...filters, per_page: perPage}, {
            preserveState: true,
            replace: true
        });
    };

    const openModal = (item: StockItem) => {
        setSelectedItem(item);
        setData('product_id', item.id.toString());
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setSelectedItem(null);
        reset();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('product-service.stock.store'), {
            onSuccess: () => {
                closeModal();
            }
        });
    };



    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: false
        },
        {
            key: 'sku',
            header: t('SKU'),
            sortable: false
        },
        {
            key: 'total_quantity',
            header: t('Quantity'),
            sortable: false,
            render: (value: number) => Math.floor(value) || 0
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, item: StockItem) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    onClick={() => openModal(item)}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Add Stock')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            )
        }
    ];

    return (
        <TooltipProvider>
            <AuthenticatedLayout
                breadcrumbs={[
                    {label: t('Product & Service'), url: route('product-service.items.index'), onClick: () => router.visit(route('product-service.items.index'))},
                    {label: t('Product Stock')}
                ]}
                pageTitle={t('Product Stock')}
                backUrl={route('product-service.items.index')}
            >
            <Head title={t('Product Stock')} />

            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({...filters, name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by name...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="product-service.stock.index"
                                filters={filters}
                            />
                        </div>
                    </div>
                </CardContent>

                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <DataTable
                            data={stocks.data}
                            columns={tableColumns}
                            className="rounded-none"
                            emptyState={
                                <NoRecordsFound
                                    icon={Package}
                                    title={t('No stock found')}
                                    description={t('No product stock records available.')}
                                    hasFilters={!!filters.name}
                                    className="h-auto"
                                />
                            }
                        />
                    </div>
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={stocks}
                        routeName="product-service.stock.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>

            </AuthenticatedLayout>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Add Stock')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-gray-700">{t('Product Name')}</Label>
                            <div className="px-3 py-2 bg-gray-50 border rounded-md text-gray-900">
                                {selectedItem?.name || ''}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-gray-700">{t('SKU')}</Label>
                            <div className="px-3 py-2 bg-gray-50 border rounded-md text-gray-900">
                                {selectedItem?.sku || ''}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="warehouse_id">{t('Warehouse')}</Label>
                            <Select value={data.warehouse_id} onValueChange={(value) => setData('warehouse_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select warehouse')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((warehouse) => (
                                        <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.warehouse_id && <p className="text-sm text-red-600">{errors.warehouse_id}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="quantity">{t('Quantity')}</Label>
                            <Input
                                id="quantity"
                                type="number"
                                min="0"
                                step="1"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                placeholder={t('Enter quantity')}
                            />
                            {errors.quantity && <p className="text-sm text-red-600">{errors.quantity}</p>}
                        </div>
                        <div className="flex justify-end gap-2 pt-4">
                            <Button type="button" variant="outline" onClick={closeModal}>
                                {t('Cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? t('Creating...') : t('Create')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </TooltipProvider>
    );
}
