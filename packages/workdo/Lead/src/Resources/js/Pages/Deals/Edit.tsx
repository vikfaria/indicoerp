import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { useEffect, useState } from 'react';
import { useFormFields } from '@/hooks/useFormFields';

interface EditDealProps {
    deal: any;
    onSuccess: () => void;
}

interface EditDealFormData {
    name: string;
    price: string;
    pipeline_id: string;
    stage_id: string;
    sources: string[];
    products: string[];
    phone: string;
    notes: string;
}

export default function EditDeal({ deal, onSuccess }: EditDealProps) {
    const { pipelines, stages, sources, products } = usePage<any>().props;
    const [filteredStages, setFilteredStages] = useState(stages || []);
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<EditDealFormData>({
        name: deal.name ?? '',
        price: deal.price?.toString() ?? '0',
        pipeline_id: deal.pipeline_id?.toString() ?? '',
        stage_id: deal.stage_id?.toString() ?? '',
        sources: deal.sources ? (Array.isArray(deal.sources) ? deal.sources.map(String) : []) : [],
        products: deal.products ? (Array.isArray(deal.products) ? deal.products.map(String) : []) : [],
        phone: deal.phone ?? '',
        notes: deal.notes ?? '',
    });

    const dealNameAI = useFormFields('aiField', data, setData, errors, 'edit', 'name', 'Deal Name', 'lead', 'deal');
    const [notesEditorKey, setNotesEditorKey] = useState(0);
    const dealNotesAI = useFormFields('aiField', data, (field, value) => {
        setData('notes', value);
        setNotesEditorKey(prev => prev + 1);
    }, errors, 'edit', 'notes', 'Notes', 'lead', 'deal');


    useEffect(() => {
        if (data.pipeline_id) {
            const pipelineStages = stages?.filter(stage => stage.pipeline_id?.toString() === data.pipeline_id) || [];
            setFilteredStages(pipelineStages);
        } else {
            setFilteredStages(stages || []);
            setData('stage_id', '');
        }
    }, [data.pipeline_id, stages]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('lead.deals.update', deal.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Edit Deal')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="name">{t('Deal Name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder={t('Enter Deal Name')}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        {dealNameAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>

                    <div>
                        <Label htmlFor="price">{t('Price')}</Label>
                        <Input
                            id="price"
                            type="number"
                            step="0.01"
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                            placeholder={t('Enter Price')}
                            required
                        />
                        <InputError message={errors.price} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="pipeline_id">{t('Pipeline')}</Label>
                        <Select value={data.pipeline_id} onValueChange={(value) => setData('pipeline_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Pipeline')} />
                            </SelectTrigger>
                            <SelectContent>
                                {pipelines?.map((pipeline: any) => (
                                    <SelectItem key={pipeline.id} value={pipeline.id.toString()}>
                                        {pipeline.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.pipeline_id} />
                    </div>

                    <div>
                        <Label htmlFor="stage_id">{t('Stage')}</Label>
                        <Select
                            value={data.stage_id}
                            onValueChange={(value) => setData('stage_id', value)}
                            disabled={!data.pipeline_id}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={data.pipeline_id ? t('Select Stage') : t('Select Pipeline first')} />
                            </SelectTrigger>
                            <SelectContent>
                                {filteredStages?.map((stage: any) => (
                                    <SelectItem key={stage.id} value={stage.id.toString()}>
                                        {stage.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.stage_id} />
                    </div>
                </div>

                <div>
                    <Label>{t('Sources')}</Label>
                    <MultiSelectEnhanced
                        options={sources?.map((source: any) => ({
                            value: source.id.toString(),
                            label: source.name
                        })) || []}
                        value={data.sources}
                        onValueChange={(value) => setData('sources', value)}
                        placeholder={t('Select Sources...')}
                        searchable={true}
                    />
                    <InputError message={errors.sources} />
                </div>

                <div>
                    <Label>{t('Products')}</Label>
                    <MultiSelectEnhanced
                        options={products?.map((product: any) => ({
                            value: product.id.toString(),
                            label: product.name
                        })) || []}
                        value={data.products}
                        onValueChange={(value) => setData('products', value)}
                        placeholder={t('Select Products...')}
                        searchable={true}
                    />
                    <InputError message={errors.products} />
                </div>

                <div>
                    <Label htmlFor="phone">{t('Phone No')}</Label>
                    <PhoneInputComponent
                        value={data.phone}
                        onChange={(value) => setData('phone', value)}
                        placeholder={t('Enter Phone Number')}
                        error={errors.phone}
                    />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="notes">{t('Notes')}</Label>
                        <div className="flex gap-2">
                            {dealNotesAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`notes-editor-${notesEditorKey}`}
                        content={data.notes}
                        onChange={(content) => setData('notes', content)}
                        placeholder={t('Enter Notes')}
                    />
                    <InputError message={errors.notes} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
