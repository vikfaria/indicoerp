import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Package, Trash2 } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { Deal } from '../types';

interface ProductsProps {
    deal: Deal;
    availableProducts: any[];
    onRegisterAddHandler: (handler: () => void) => void;
}

export default function Products({ deal, availableProducts, onRegisterAddHandler }: ProductsProps) {

    useEffect(() => {
        onRegisterAddHandler(() => openProductModal());
    }, [onRegisterAddHandler]);
    const { t } = useTranslation();
    const [productModalOpen, setProductModalOpen] = useState(false);
    const [availableProductsState, setAvailableProductsState] = useState([]);
    const [selectedProducts, setSelectedProducts] = useState([]);
    const [productNames, setProductNames] = useState({});
    const [productDeleteState, setProductDeleteState] = useState({ isOpen: false, productId: null, message: '' });

    const formatAvailableProducts = () => {
        setAvailableProductsState(availableProducts.map((product: any) => ({
            value: product.id.toString(),
            label: product.name
        })));
    };

    const handleAssignProducts = () => {
        if (selectedProducts.length === 0) return;
        
        router.post(route('lead.deals.assign-products', deal.id), {
            product_ids: selectedProducts.map(id => parseInt(id))
        }, {
            onSuccess: () => {
                setProductModalOpen(false);
                setSelectedProducts([]);
            }
        });
    };

    const openProductModal = () => {
        formatAvailableProducts();
        setProductModalOpen(true);
    };

    const openProductDeleteDialog = (productId: string) => {
        setProductDeleteState({
            isOpen: true,
            productId,
            message: t('Are you sure you want to delete this product?')
        });
    };

    const closeProductDeleteDialog = () => {
        setProductDeleteState({ isOpen: false, productId: null, message: '' });
    };

    const confirmProductDelete = () => {
        if (productDeleteState.productId) {
            router.delete(route('lead.deals.remove-product', {deal: deal.id, product: productDeleteState.productId}));
            closeProductDeleteDialog();
        }
    };

    useEffect(() => {
        if (deal.products && availableProducts.length > 0) {
            const names = {};
            availableProducts.forEach(product => {
                names[product.id] = product.name;
            });
            setProductNames(names);
        }
    }, [deal.products, availableProducts]);

    return (
        <>
            <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
                <div className="min-w-[600px]">
                    <DataTable
                        data={deal.products ? [...new Set((Array.isArray(deal.products) ? deal.products : deal.products.split(',')).filter(Boolean).map(id => id.toString().trim()))].map((productId: string, index: number) => ({ id: productId, key: `product-${productId}-${index}`, name: productNames[productId] || '' })) : []}
                        columns={[
                            {
                                key: 'name',
                                header: t('Product Name'),
                                render: (value: string, product: any) => product.name || '-'
                            },
                            {
                                key: 'actions',
                                header: t('Action'),
                                render: (_: any, product: any) => (
                                    <div className="flex gap-1">
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="sm" onClick={() => {
                                                        openProductDeleteDialog(product.id);
                                                    }} className="h-8 w-8 p-0 text-red-600 hover:text-red-700">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{t('Delete Product')}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                )
                            }
                        ]}
                        className="rounded-none"
                        emptyState={
                            <NoRecordsFound
                                icon={Package}
                                title={t('No Products added')}
                                description={t('Get started by adding products to this deal.')}
                                onCreateClick={() => openProductModal()}
                                createButtonText={t('Add Products')}
                                className="h-auto"
                            />
                        }
                    />
                </div>
            </div>

            <Dialog open={productModalOpen} onOpenChange={setProductModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Add Products')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label>{t('Select Products')}</Label>
                            <MultiSelectEnhanced
                                options={availableProductsState}
                                value={selectedProducts}
                                onValueChange={setSelectedProducts}
                                placeholder={t('Select products')}
                                searchable={true}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setProductModalOpen(false)}>{t('Cancel')}</Button>
                            <Button onClick={handleAssignProducts} disabled={selectedProducts.length === 0}>{t('Save')}</Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmationDialog
                open={productDeleteState.isOpen}
                onOpenChange={closeProductDeleteDialog}
                title={t('Delete Product')}
                message={productDeleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmProductDelete}
                variant="destructive"
            />
        </>
    );
}