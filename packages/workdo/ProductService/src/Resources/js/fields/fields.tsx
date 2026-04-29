import React, { useState, useEffect } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import InputError from "@/components/ui/input-error";
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { formatCurrency } from '@/utils/helpers';

export const createProductServiceField = (data: any, setData: any, errors: any, mode: string = 'create') => {
    const { t } = useTranslation();
    const { props } = usePage();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchProducts = async () => {
        try {
            const response = await axios.get(route('api.product-service.items.index'));
            
            setProducts(response.data);
        } catch (error) {
            console.error('Error fetching products:', error);
        } finally {
            setLoading(false);
        }
    };

    React.useEffect(() => {
        fetchProducts();
    }, []);

    const handleProductSelect = (productId: string) => {
        const selectedProduct = products.find(p => p.id.toString() === productId);

        
        if (selectedProduct) {
            const taxIds = selectedProduct.taxes?.map(tax => tax.id) || [];
            
            setData('item_id', productId);
            setData('price', selectedProduct.sale_price);
            setData('tax', taxIds);
            setData('description', selectedProduct.description || '');
        }
    };

    const selectedProduct = React.useMemo(() => {
        return data.item_id && products.length > 0 
            ? products.find(p => p.id.toString() === data.item_id.toString()) 
            : null;
    }, [data.item_id, products]);
    const fieldId = mode === 'edit' ? 'edit_item_id' : 'item_id';

    return [{
        id: 'product-service-item',
        order: 1,
        component: (
            <div key={`${data.item_id}-${products.length}`}>
                <Label htmlFor={fieldId}>{t('Product/Service')}</Label>
                <Select value={data.item_id ? data.item_id.toString() : ''} onValueChange={handleProductSelect}>
                    <SelectTrigger>
                        <SelectValue placeholder={loading ? t('Loading...') : t('Select a product')} />
                    </SelectTrigger>
                    <SelectContent>
                        {products.map((product) => (
                            <SelectItem key={product.id} value={product.id.toString()}>
                                <span className="font-medium">{product.name} - {formatCurrency(product.sale_price || 0, props)}</span>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                
                {selectedProduct?.taxes?.length > 0 && (
                    <div className="flex flex-wrap gap-1 mt-2">
                        {selectedProduct.taxes.map((tax, index) => (
                            <Badge 
                                key={tax.id} 
                                variant="secondary" 
                                className={`text-xs ${
                                    index % 2 === 0 ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
                                }`}
                            >
                                {tax.tax_name} ({tax.rate}%)
                            </Badge>
                        ))}
                    </div>
                )}
                
                <InputError message={errors.item_id} />
            </div>
        )
    }];
};