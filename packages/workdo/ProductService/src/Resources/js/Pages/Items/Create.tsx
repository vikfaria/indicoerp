import { useState } from 'react';
import { Head, useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { useFormFields } from '@/hooks/useFormFields';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { MultiSelectEnhanced } from "@/components/ui/multi-select-enhanced";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog } from "@/components/ui/dialog";
import MediaPicker from "@/components/MediaPicker";
import InputError from "@/components/ui/input-error";
import { RichTextEditor } from "@/components/ui/rich-text-editor";
import { CreateItemPageProps, ItemFormData } from './types';

export default function Create() {
    const { t } = useTranslation();
    const { taxes, categories, units, warehouses } = usePage<CreateItemPageProps>().props;
    const [activeTab, setActiveTab] = useState('details');


    const { data, setData, post, processing, errors } = useForm<ItemFormData>({
        name: '',
        sku: '',
        tax_ids: [],
        category_id: '',
        description: '',
        long_description: '',
        sale_price: '',
        purchase_price: '',
        unit: '',
        quantity: '',
        image: '',
        images: [],
        warehouse_id: '',
        type: 'product',
        custom_fields: {},
        has_warranty: 0,
        warranty_type: '',
        warranty_duration: '',
        warranty_terms: ''
    });

    // Get custom fields using useFormFields hook
    const customFields = useFormFields('getCustomFields', { ...data, module: 'ProductService', sub_module: 'Items' }, setData, errors, 'create', t);

    // AI hook for short description
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Short Description', 'productservice', 'item');
    // Inventory fields hook
    const inventoryFields = useFormFields('inventoryFields', data, setData, errors, 'create');
    // Warranty fields hook
    const warrantyFields = useFormFields('warrantyFields', data, setData, errors, 'create');

    const validateDetailsTab = () => {
        return data.name.trim() !== '' &&
               data.sku.trim() !== '' &&
               data.tax_ids.length > 0 &&
               data.category_id !== '';
    };

    const validatePricingTab = () => {
        const baseValidation = data.sale_price.trim() !== '' &&
               data.purchase_price.trim() !== '' &&
               data.unit !== '';

        if (data.type === 'service') {
            return baseValidation;
        }

        return baseValidation && data.quantity.trim() !== '';
    };

    const nextTab = () => {
        if (activeTab === 'details') {
            if (!validateDetailsTab()) {
                return; // Don't proceed if validation fails
            }
            setActiveTab('pricing');
        }
        else if (activeTab === 'pricing') {
            if (!validatePricingTab()) {
                return;
            }
            setActiveTab('media');
        }
        else if (activeTab === 'media') setActiveTab('warehouse');
    };

    const prevTab = () => {
        if (activeTab === 'pricing') setActiveTab('details');
        else if (activeTab === 'media') setActiveTab('pricing');
        else if (activeTab === 'warehouse') setActiveTab('media');
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('product-service.items.store'), {
            transform: (data) => {
                if (data.type === 'service') {
                    const { quantity, warehouse_id, ...serviceData } = data;
                    return serviceData;
                }

                return data;
            }
        });
    };

    return (
        <Dialog>
            <AuthenticatedLayout
                breadcrumbs={[
                    {label: t('Items'), url: route('product-service.items.index')},
                    {label: t('Create')}
                ]}
                pageTitle={
                    <div className="flex items-center justify-between">
                        <span>{t('Create Item')}</span>
                    </div>
                }
                backUrl={route('product-service.items.index')}
            >
                <Head title={t('Create Item')} />

                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="pt-5">
                            <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                                <TabsContent value="details" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="type">{t('Item Type')}</Label>
                                            <Select value={data.type || ''} onValueChange={(value) => {
                                                setData('type', value);
                                                if (value === 'service') {
                                                    setData('quantity', '');
                                                    setData('warehouse_id', '');
                                                }
                                            }}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder={t('Select Type')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="product">{t('Product')}</SelectItem>
                                                    <SelectItem value="service">{t('Service')}</SelectItem>
                                                    <SelectItem value="part">{t('Part')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.type} />
                                        </div>
                                        <div>
                                            <Label htmlFor="name">{t('Name')}</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                placeholder={t('Enter Name')}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                        <div>
                                            <Label htmlFor="sku">{t('SKU')}</Label>
                                            <div className="flex gap-2">
                                                <Input
                                                    id="sku"
                                                    value={data.sku}
                                                    onChange={(e) => setData('sku', e.target.value)}
                                                    placeholder={t('Enter SKU')}
                                                    required
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => setData('sku', 'SKU-' + Date.now())}
                                                >
                                                    {t('Generate')}
                                                </Button>
                                            </div>
                                            <InputError message={errors.sku} />
                                        </div>
                                        <div>
                                            <Label htmlFor="tax_ids" required>{t('Tax')}</Label>
                                            <MultiSelectEnhanced
                                                options={taxes.map(tax => ({
                                                    value: tax.id.toString(),
                                                    label: `${tax.tax_name} (${tax.rate}%)`
                                                }))}
                                                value={data.tax_ids}
                                                onValueChange={(value) => setData('tax_ids', value)}
                                                placeholder={t('Select Taxes')}
                                            />
                                            <InputError message={errors.tax_ids} />
                                        </div>
                                        <div>
                                            <Label htmlFor="category_id" required>{t('Category')}</Label>
                                            <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)} required>
                                                <SelectTrigger>
                                                    <SelectValue placeholder={t('Select Category')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {categories.map((category) => (
                                                        <SelectItem key={category.id} value={category.id.toString()}>
                                                            {category.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.category_id} />
                                        </div>
                                    </div>

                                    <div>
                                        <div className="flex items-center justify-between mb-2">
                                            <Label htmlFor="description">{t('Short Description')}</Label>
                                            <div className="flex gap-2">
                                                {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                                            </div>
                                        </div>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            placeholder={t('Enter Short Description')}
                                            rows={3}
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div>
                                        <Label htmlFor="long_description">{t('Description')}</Label>
                                        <RichTextEditor
                                            content={data.long_description || ''}
                                            onChange={(value) => setData('long_description', value)}
                                            placeholder={t('Enter Description')}
                                        />
                                        <InputError message={errors.long_description} />
                                    </div>

                                    {/* Custom Fields */}
                                    {customFields && customFields.length > 0 && (
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {customFields.map(field => field.component)}
                                        </div>
                                    )}

                                    {/* Warranty Fields */}
                                    {warrantyFields && warrantyFields.length > 0 && (
                                        <div className="border-t pt-6">
                                            <h3 className="text-lg font-medium mb-4">{t('Warranty Information')}</h3>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                                {warrantyFields.map(field => field.component)}
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex justify-end">
                                        <Button
                                            type="button"
                                            onClick={nextTab}
                                            disabled={!validateDetailsTab()}
                                        >
                                            {t('Next')}
                                        </Button>
                                    </div>
                                </TabsContent>

                                <TabsContent value="pricing" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="sale_price">{t('Sale Price')}</Label>
                                            <Input
                                                id="sale_price"
                                                type="number"
                                                step="0.01"
                                                value={data.sale_price}
                                                onChange={(e) => setData('sale_price', e.target.value)}
                                                placeholder={t('Enter Sale Price')}
                                                required
                                            />
                                            <InputError message={errors.sale_price} />
                                        </div>
                                        <div>
                                            <Label htmlFor="purchase_price">{t('Purchase Price')}</Label>
                                            <Input
                                                id="purchase_price"
                                                type="number"
                                                step="0.01"
                                                value={data.purchase_price}
                                                onChange={(e) => setData('purchase_price', e.target.value)}
                                                placeholder={t('Enter Purchase Price')}
                                                required
                                            />
                                            <InputError message={errors.purchase_price} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <Label htmlFor="unit" required>{t('Unit')}</Label>
                                            <Select value={data.unit} onValueChange={(value) => setData('unit', value)} required>
                                                <SelectTrigger>
                                                    <SelectValue placeholder={t('Select Unit')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {units.map((unit) => (
                                                        <SelectItem key={unit.id} value={unit.id.toString()}>
                                                            {unit.unit_name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.unit} />
                                        </div>
                                        {data.type !== 'service' && (
                                            <div>
                                                <Label htmlFor="quantity">{t('Quantity')}</Label>
                                                <Input
                                                    id="quantity"
                                                    type="number"
                                                    value={data.quantity}
                                                    onChange={(e) => setData('quantity', e.target.value)}
                                                    placeholder={t('Enter Quantity')}
                                                    required
                                                />
                                                <InputError message={errors.quantity} />
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex justify-between">
                                        <Button type="button" variant="outline" onClick={prevTab}>
                                            {t('Previous')}
                                        </Button>
                                        <Button
                                            type="button"
                                            onClick={nextTab}
                                            disabled={!validatePricingTab()}
                                        >
                                            {t('Next')}
                                        </Button>
                                    </div>
                                </TabsContent>

                                <TabsContent value="media" className="space-y-6 mt-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <MediaPicker
                                                label={t('Product Image')}
                                                value={data.image}
                                                onChange={(value) => setData('image', value)}
                                                placeholder={t('Select image...')}
                                                showPreview={true}
                                            />
                                            <InputError message={errors.image} />
                                        </div>
                                        <div>
                                            <MediaPicker
                                                label={t('Additional Images')}
                                                value={data.images}
                                                onChange={(value) => setData('images', Array.isArray(value) ? value : [value].filter(Boolean))}
                                                multiple={true}
                                                placeholder={t('Select multiple images')}
                                                showPreview={false}
                                            />
                                            <InputError message={errors.images} />
                                        </div>
                                    </div>
                                    <div className="flex justify-between">
                                        <Button type="button" variant="outline" onClick={prevTab}>
                                            {t('Previous')}
                                        </Button>
                                        {data.type === 'service' ? (
                                            <div className="flex gap-2">
                                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                                    {t('Cancel')}
                                                </Button>
                                                <Button type="submit" disabled={processing}>
                                                    {processing ? t('Creating...') : t('Create')}
                                                </Button>
                                            </div>
                                        ) : (
                                            <Button type="button" onClick={nextTab}>
                                                {t('Next')}
                                            </Button>
                                        )}
                                    </div>
                                </TabsContent>

                                <TabsContent value="warehouse" className="space-y-6 mt-6">
                                    {data.type !== 'service' && (
                                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                            <div>
                                                <Label htmlFor="warehouse_id" required>{t('Warehouse')}</Label>
                                                <Select value={data.warehouse_id} onValueChange={(value) => setData('warehouse_id', value)} required>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={t('Select Warehouse')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {warehouses.map((warehouse) => (
                                                            <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                                                {warehouse.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.warehouse_id} />
                                            </div>
                                        </div>
                                    )}

                                    {/* Inventory Fields */}
                                    {data.type !== 'service' && inventoryFields.length > 0 && (
                                        <div>
                                            {inventoryFields.map(field => (
                                                <div key={field.id}>{field.component}</div>
                                            ))}
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <Button type="button" variant="outline" onClick={prevTab}>
                                            {t('Previous')}
                                        </Button>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                                {t('Cancel')}
                                            </Button>
                                            <Button type="submit" disabled={processing}>
                                                {processing ? t('Creating...') : t('Create')}
                                            </Button>
                                        </div>
                                    </div>
                                </TabsContent>
                            </Tabs>
                        </form>
                    </CardContent>
                </Card>
            </AuthenticatedLayout>
        </Dialog>
    );
}
