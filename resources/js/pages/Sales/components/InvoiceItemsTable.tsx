import React from 'react';
import { useTranslation } from 'react-i18next';
import { SalesInvoiceItem, VatCodeOption } from '../types';
import ProductSelector from './ProductSelector';
import { calculateLineItemAmounts } from './TaxCalculator';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Trash2 } from 'lucide-react';
import { formatCurrency } from '@/utils/helpers';

interface Props {
    items: SalesInvoiceItem[];
    onChange: (items: SalesInvoiceItem[]) => void;
    errors: any;
    products?: Array<{id: number; name: string; sale_price: number; unit?: string; stock_quantity?: number; taxes?: Array<{id: number; tax_name: string; rate: number}>}>;
    vatCodes?: VatCodeOption[];
    showAddButton?: boolean;
    invoiceType?: string;
}

export default function InvoiceItemsTable({ items, onChange, errors, products = [], vatCodes = [], showAddButton = true, invoiceType = 'product' }: Props) {
    const { t } = useTranslation();

    const getVatCodeOption = (vatCode?: string | null) => {
        if (!vatCode) {
            return null;
        }

        return vatCodes.find((option) => option.code === vatCode) ?? null;
    };

    const requiresExemptionReason = (vatCode?: string | null) => {
        const option = getVatCodeOption(vatCode);
        return option ? ['exempt', 'not_subject'].includes(String(option.type || '').toLowerCase()) : false;
    };

    const recalculateItem = (item: SalesInvoiceItem) => {
        const calculations = calculateLineItemAmounts(
            Number(item.quantity) || 0,
            Number(item.unit_price) || 0,
            Number(item.discount_percentage) || 0,
            Number(item.tax_percentage) || 0
        );

        item.discount_amount = calculations.discountAmount;
        item.tax_amount = calculations.taxAmount;
        item.total_amount = calculations.totalAmount;
    };

    const addItem = () => {
        const newItem: SalesInvoiceItem = {
            product_id: 0,
            quantity: 1,
            unit_price: 0,
            discount_percentage: 0,
            discount_amount: 0,
            tax_percentage: 0,
            vat_code: '',
            tax_exemption_reason: '',
            tax_amount: 0,
            total_amount: 0,
            taxes: []
        };
        onChange([...items, newItem]);
    };

    const removeItem = (index: number) => {
        const newItems = items.filter((_, i) => i !== index);
        onChange(newItems);
    };

    const updateItem = (index: number, field: keyof SalesInvoiceItem, value: any) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };

        const item = newItems[index];
        recalculateItem(item);

        onChange(newItems);
    };

    const handleProductSelect = (index: number, productId: number, product?: any) => {
        const newItems = [...items];
        const existingVatCode = getVatCodeOption(newItems[index]?.vat_code ?? null);
        const productTaxRate = product?.taxes?.reduce((sum: number, tax: any) => sum + Number(tax.rate), 0) || 0;
        const productTaxes = product?.taxes?.map((tax: any) => ({
            tax_name: tax.tax_name,
            tax_rate: tax.rate,
        })) || [];

        newItems[index] = {
            ...newItems[index],
            product_id: productId,
            unit_price: Number(product?.sale_price) || 0,
            tax_percentage: existingVatCode ? Number(existingVatCode.rate) || 0 : Number(productTaxRate) || 0,
            taxes: existingVatCode
                ? [{
                    tax_name: existingVatCode.description,
                    tax_rate: Number(existingVatCode.rate) || 0,
                    vat_code: existingVatCode.code,
                    tax_exemption_reason: newItems[index]?.tax_exemption_reason ?? null,
                }]
                : productTaxes,
        };

        const item = newItems[index];
        item.quantity = Number(item.quantity) || 1;
        item.discount_percentage = Number(item.discount_percentage) || 0;

        if (existingVatCode && requiresExemptionReason(existingVatCode.code)) {
            item.tax_exemption_reason = item.tax_exemption_reason || '';
        }

        recalculateItem(item);

        onChange(newItems);
    };

    const handleVatCodeSelect = (index: number, vatCodeValue: string) => {
        const newItems = [...items];
        const vatCode = getVatCodeOption(vatCodeValue);
        const item = newItems[index];
        const currentProduct = products.find((product) => product.id === item.product_id);

        if (vatCode) {
            item.vat_code = vatCode.code;
            item.tax_percentage = Number(vatCode.rate) || 0;
            item.taxes = [{
                tax_name: vatCode.description,
                tax_rate: Number(vatCode.rate) || 0,
                vat_code: vatCode.code,
                tax_exemption_reason: requiresExemptionReason(vatCode.code) ? (item.tax_exemption_reason || '') : null,
            }];
            if (!requiresExemptionReason(vatCode.code)) {
                item.tax_exemption_reason = '';
            }
        } else {
            item.vat_code = '';
            const productTaxRate = currentProduct?.taxes?.reduce((sum, tax) => sum + Number(tax.rate), 0) || 0;
            item.tax_percentage = Number(productTaxRate) || 0;
            item.taxes = currentProduct?.taxes?.map((tax) => ({
                tax_name: tax.tax_name,
                tax_rate: tax.rate,
            })) || [];
        }

        recalculateItem(item);

        onChange(newItems);
    };

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto">
                <table className="min-w-full">
                    <thead>
                        <tr className="border-b border-border">
                            <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                {t('Product')} <span className="text-red-500">*</span>
                            </th>
                            {invoiceType === 'product' && (
                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                    {t('Qty')} <span className="text-red-500">*</span>
                                </th>
                            )}
                            <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                {t('Unit Price')} <span className="text-red-500">*</span>
                            </th>
                            <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                {t('Discount')} %
                            </th>
                            <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                {t('Tax')}
                            </th>
                            <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">
                                {t('Total')}
                            </th>
                            <th className="px-4 py-3 text-center text-sm font-semibold text-foreground">
                                {t('Action')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {items.map((item, index) => (
                            <tr key={index}>
                                <td className="px-4 py-4">
                                    <ProductSelector
                                        products={products}
                                        value={item.product_id}
                                        onChange={(productId, product) => handleProductSelect(index, productId, product)}
                                    />
                                    <InputError message={errors[`items.${index}.product_id`]} />
                                </td>
                                {invoiceType === 'product' && (
                                    <td className="px-4 py-4">
                                        {(() => {
                                            const product = products.find(p => p.id === item.product_id);
                                            const maxQty = product?.stock_quantity || 999999;
                                            return (
                                                <div>
                                                    <Input
                                                        type="number"
                                                        value={item.quantity}
                                                        onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value) || 0)}
                                                        className="w-20 text-sm"
                                                        min="1"
                                                        max={maxQty}
                                                        step="1"
                                                        required
                                                    />
                                                    {product && (
                                                        <div className="text-xs text-muted-foreground mt-1">
                                                            {t('Stock')}: {product.stock_quantity || 0}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })()}
                                        <InputError message={errors[`items.${index}.quantity`]} />
                                    </td>
                                )}
                                <td className="px-4 py-4">
                                    <Input
                                        type="number"
                                        value={item.unit_price}
                                        onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                        className="w-24 text-sm"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                    <InputError message={errors[`items.${index}.unit_price`]} />
                                </td>
                                <td className="px-4 py-4">
                                    <Input
                                        type="number"
                                        value={item.discount_percentage}
                                        onChange={(e) => updateItem(index, 'discount_percentage', parseFloat(e.target.value) || 0)}
                                        className="w-20 text-sm"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                    />
                                </td>
                                <td className="px-4 py-4">
                                    <div className="space-y-2 min-w-[240px]">
                                        {vatCodes.length > 0 && (
                                            <Select value={item.vat_code || ''} onValueChange={(value) => handleVatCodeSelect(index, value)}>
                                                <SelectTrigger className="h-9">
                                                    <SelectValue placeholder={t('Select VAT Code')} />
                                                </SelectTrigger>
                                                <SelectContent searchable>
                                                    {vatCodes.map((vatCode) => (
                                                        <SelectItem key={vatCode.code} value={vatCode.code}>
                                                            {vatCode.code} - {vatCode.description} ({Number(vatCode.rate).toFixed(2)}%)
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}

                                        {requiresExemptionReason(item.vat_code) && (
                                            <Input
                                                type="text"
                                                value={item.tax_exemption_reason || ''}
                                                onChange={(e) => updateItem(index, 'tax_exemption_reason', e.target.value)}
                                                className="text-sm"
                                                placeholder={t('Exemption reason')}
                                            />
                                        )}

                                        {item.taxes && item.taxes.length > 0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {item.taxes.map((tax, taxIndex) => (
                                                    <span key={taxIndex} className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {tax.tax_name} ({tax.tax_rate}%)
                                                    </span>
                                                ))}
                                            </div>
                                        ) : item.tax_percentage > 0 ? (
                                            <span className="text-sm text-blue-800">Tax ({item.tax_percentage}%)</span>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">No tax</span>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-4">
                                    <span className="text-sm font-medium">
                                        {formatCurrency(item.total_amount)}
                                    </span>
                                </td>
                                <td className="px-4 py-4 text-center">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeItem(index)}
                                        className="text-red-600 hover:text-red-800 h-8 w-8 p-0"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {showAddButton && (
                <div className="flex justify-start">
                    <Button
                        type="button"
                        onClick={addItem}
                        variant="default"
                        size="sm"
                    >
                        + {t('Add Item')}
                    </Button>
                </div>
            )}

            <InputError message={errors.items} />
        </div>
    );
}
