import { useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2 } from "lucide-react";
import { formatCurrency } from '@/utils/helpers';

interface Product {
    id: number;
    name: string;
    sku: string;
    purchase_price: number;
    unit: string;
    type: string;
    taxes: Array<{
        id: number;
        tax_name: string;
        rate: number;
    }>;
}

interface DebitNoteItem {
    product_id: string;
    quantity: number;
    unit_price: number;
    tax_amount: number;
    total_amount: number;
}

interface CreateProps {
    vendors: Array<{id: number; name: string}>;
    products: Product[];
}

export default function Create() {
    const { t } = useTranslation();
    const { vendors, products } = usePage<CreateProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        vendor_id: '',
        debit_note_date: new Date().toISOString().split('T')[0],
        reason: '',
        notes: '',
        items: [
            {
                product_id: '',
                quantity: 1,
                unit_price: 0,
                tax_amount: 0,
                total_amount: 0
            }
        ] as DebitNoteItem[]
    });

    const addItem = () => {
        setData('items', [
            ...data.items,
            {
                product_id: '',
                quantity: 1,
                unit_price: 0,
                tax_amount: 0,
                total_amount: 0
            }
        ]);
    };

    const removeItem = (index: number) => {
        if (data.items.length > 1) {
            const newItems = data.items.filter((_, i) => i !== index);
            setData('items', newItems);
        }
    };

    const updateItem = (index: number, field: keyof DebitNoteItem, value: any) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };

        if (field === 'product_id') {
            const product = products.find(p => p.id.toString() === value);
            if (product) {
                newItems[index].unit_price = product.purchase_price;
                const taxRate = product.taxes.reduce((sum, tax) => sum + tax.rate, 0);
                const lineTotal = newItems[index].quantity * product.purchase_price;
                newItems[index].tax_amount = (lineTotal * taxRate) / 100;
                newItems[index].total_amount = lineTotal + newItems[index].tax_amount;
            }
        } else if (field === 'quantity' || field === 'unit_price') {
            const lineTotal = newItems[index].quantity * newItems[index].unit_price;
            const product = products.find(p => p.id.toString() === newItems[index].product_id);
            const taxRate = product ? product.taxes.reduce((sum, tax) => sum + tax.rate, 0) : 0;
            newItems[index].tax_amount = (lineTotal * taxRate) / 100;
            newItems[index].total_amount = lineTotal + newItems[index].tax_amount;
        }

        setData('items', newItems);
    };

    const calculateTotals = () => {
        const subtotal = data.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        const totalTax = data.items.reduce((sum, item) => sum + item.tax_amount, 0);
        const total = subtotal + totalTax;

        return { subtotal, totalTax, total };
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('account.debit-notes.store'));
    };

    const { subtotal, totalTax, total } = calculateTotals();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Debit Notes'), url: route('account.debit-notes.index')},
                {label: t('Create')}
            ]}
            pageTitle={t('Create Debit Note')}
        >
            <Head title={t('Create Debit Note')} />

            <form onSubmit={handleSubmit}>
                <div className="space-y-6">
                    <Card>
                        <CardContent className="space-y-4 pt-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="vendor_id">{t('Vendor')}</Label>
                                    <Select value={data.vendor_id} onValueChange={(value) => setData('vendor_id', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select vendor')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {vendors.map((vendor) => (
                                                <SelectItem key={vendor.id} value={vendor.id.toString()}>
                                                    {vendor.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.vendor_id && <p className="text-sm text-red-600">{errors.vendor_id}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="debit_note_date">{t('Date')}</Label>
                                    <Input
                                        id="debit_note_date"
                                        type="date"
                                        value={data.debit_note_date}
                                        onChange={(e) => setData('debit_note_date', e.target.value)}
                                    />
                                    {errors.debit_note_date && <p className="text-sm text-red-600">{errors.debit_note_date}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="reason">{t('Reason')}</Label>
                                    <Input
                                        id="reason"
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        placeholder={t('Enter reason for debit note')}
                                    />
                                    {errors.reason && <p className="text-sm text-red-600">{errors.reason}</p>}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="notes">{t('Notes')}</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder={t('Additional notes')}
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle>{t('Items')}</CardTitle>
                                <Button type="button" onClick={addItem} size="sm">
                                    <Plus className="h-4 w-4 mr-2" />
                                    {t('Add Item')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {data.items.map((item, index) => (
                                    <div key={index} className="border rounded-lg p-4">
                                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                            <div className="md:col-span-2">
                                                <Label>{t('Product')}</Label>
                                                <Select
                                                    value={item.product_id}
                                                    onValueChange={(value) => updateItem(index, 'product_id', value)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={t('Select product')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {products.map((product) => (
                                                            <SelectItem key={product.id} value={product.id.toString()}>
                                                                {product.name} ({product.sku})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div>
                                                <Label>{t('Quantity')}</Label>
                                                <Input
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(index, 'quantity', parseFloat(e.target.value) || 0)}
                                                />
                                            </div>

                                            <div>
                                                <Label>{t('Unit Price')}</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={item.unit_price}
                                                    onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                                />
                                            </div>

                                            <div className="flex items-end">
                                                <div className="flex-1">
                                                    <Label>{t('Total')}</Label>
                                                    <div className="text-sm font-medium text-gray-900 mt-2">
                                                        {formatCurrency(item.total_amount)}
                                                    </div>
                                                </div>
                                                {data.items.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => removeItem(index)}
                                                        className="ml-2"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-6 flex justify-end">
                                <div className="w-64">
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span className="text-sm font-medium">{t('Subtotal')}:</span>
                                            <span className="text-sm">{formatCurrency(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-sm font-medium">{t('Tax')}:</span>
                                            <span className="text-sm">{formatCurrency(totalTax)}</span>
                                        </div>
                                        <div className="flex justify-between border-t pt-2">
                                            <span className="text-base font-bold">{t('Total')}:</span>
                                            <span className="text-base font-bold">{formatCurrency(total)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(route('account.debit-notes.index'))}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? t('Creating...') : t('Create')}
                        </Button>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
