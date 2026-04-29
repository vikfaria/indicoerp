import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { DatePicker } from '@/components/ui/date-picker';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { EditLeadProps, EditLeadFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { formatDate } from '@/utils/helpers';
import { useFormFields } from '@/hooks/useFormFields';

export default function EditLead({ lead, sources: propSources, products: propProducts, onSuccess }: EditLeadProps & { sources?: any, products?: any }) {
    const { users, pipelines, products } = usePage<any>().props;
    const [stages, setStages] = useState([]);
    const [sources, setSources] = useState(propSources || []);
    const [productOptions, setProductOptions] = useState(propProducts || []);

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditLeadFormData>({
        subject: lead.subject ?? '',
        user_id: lead.user_id?.toString() || '',
        name: lead.name ?? '',
        email: lead.email ?? '',
        phone: lead.phone ?? '',
        date: lead.date || '',
        pipeline_id: lead.pipeline_id?.toString() || '',
        stage_id: lead.stage_id?.toString() || '',
        sources: Array.isArray(lead.sources) ? lead.sources : (lead.sources ? lead.sources.split(',') : []),
        products: Array.isArray(lead.products) ? lead.products : (lead.products ? lead.products.split(',') : []),
        notes: lead.notes ?? '',
    });

    const nameAI = useFormFields('aiField', data, setData, errors, 'edit', 'name', 'Name', 'lead', 'lead');
    const subjectAI = useFormFields('aiField', data, setData, errors, 'edit', 'subject', 'Subject', 'lead', 'lead');
    const [notesEditorKey, setNotesEditorKey] = useState(0);
    const notesAI = useFormFields('aiField', data, (field, value) => {
        setData(field, value);
        setNotesEditorKey(prev => prev + 1);
    }, errors, 'edit', 'notes', 'Notes', 'lead', 'lead');

    useEffect(() => {
        if (lead) {
            setData({
                subject: lead.subject ?? '',
                user_id: lead.user_id?.toString() || '',
                name: lead.name ?? '',
                email: lead.email ?? '',
                phone: lead.phone ?? '',
                date: lead.date || '',
                pipeline_id: lead.pipeline_id?.toString() || '',
                stage_id: lead.stage_id?.toString() || '',
                sources: Array.isArray(lead.sources) ? lead.sources : (lead.sources ? lead.sources.split(',') : []),
                products: Array.isArray(lead.products) ? lead.products : (lead.products ? lead.products.split(',') : []),
                notes: lead.notes ?? '',
            });
        }
    }, [lead]);

    useEffect(() => {
        if (propSources) setSources(propSources);
        if (propProducts) setProductOptions(propProducts);
    }, [propSources, propProducts]);

    useEffect(() => {
        if (data.pipeline_id) {
            // Fetch stages for selected pipeline
            fetch(route('lead.stages.by-pipeline', data.pipeline_id))
                .then(res => res.json())
                .then(data => setStages(data))
                .catch(() => setStages([]));
        }
    }, [data.pipeline_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Convert arrays to comma-separated strings for backend
        const submitData = {
            ...data,
            sources: Array.isArray(data.sources) ? (data.sources.length > 0 ? data.sources.join(',') : '') : data.sources,
            products: Array.isArray(data.products) ? (data.products.length > 0 ? data.products.join(',') : '') : data.products,
        };

        put(route('lead.leads.update', lead.id), {
            data: submitData,
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Edit Lead')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="name">{t('Name')}</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder={t('Enter Name')}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        {nameAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>

                    <div>
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={t('Enter Email')}
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="subject">{t('Subject')}</Label>
                            <Input
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                placeholder={t('Enter Subject')}
                                required
                            />
                            <InputError message={errors.subject} />
                        </div>
                        {subjectAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>

                    <div>
                        <Label htmlFor="user_id" required>{t('User')}</Label>
                        <Select value={data.user_id?.toString() || ''} onValueChange={(value) => setData('user_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select User')} />
                            </SelectTrigger>
                            <SelectContent>
                                {users?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <PhoneInputComponent
                            label={t('Phone No')}
                            value={data.phone}
                            onChange={(value) => setData('phone', value || '')}
                            error={errors.phone}
                        />
                    </div>

                    <div>
                        <Label>{t('Follow Up Date')}</Label>
                        <DatePicker
                            value={data.date}
                            onChange={(date) => setData('date', formatDate(date))}
                            placeholder={t('Select Follow Up Date')}
                        />
                        <InputError message={errors.date} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="pipeline_id">{t('Pipeline')}</Label>
                        <Select value={data.pipeline_id?.toString() || ''} onValueChange={(value) => setData('pipeline_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Pipeline')} />
                            </SelectTrigger>
                            <SelectContent>
                                {pipelines?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.pipeline_id} />
                    </div>

                    <div>
                        <Label htmlFor="stage_id">{t('Stage')}</Label>
                        <Select value={data.stage_id?.toString() || ''} onValueChange={(value) => setData('stage_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Stage')} />
                            </SelectTrigger>
                            <SelectContent>
                                {stages?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.stage_id} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="sources">{t('Sources')}</Label>
                        <MultiSelectEnhanced
                            options={Object.entries(sources).map(([id, name]) => ({
                                value: id,
                                label: name as string
                            }))}
                            value={Array.isArray(data.sources) ? data.sources : []}
                            onValueChange={(values) => setData('sources', values)}
                            placeholder={t('Select Sources')}
                            searchable={true}
                        />
                        <InputError message={errors.sources} />
                    </div>

                    <div>
                        <Label htmlFor="products">{t('Products')}</Label>
                        <MultiSelectEnhanced
                            options={Object.entries(productOptions).map(([id, name]) => ({
                                value: id,
                                label: name as string
                            }))}
                            value={Array.isArray(data.products) ? data.products : []}
                            onValueChange={(values) => setData('products', values)}
                            placeholder={t('Select Products')}
                            searchable={true}
                        />
                        <InputError message={errors.products} />
                    </div>
                </div>
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="notes">{t('Notes')}</Label>
                        <div className="flex gap-2">
                            {notesAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`notes-editor-${notesEditorKey}`}
                        content={data.notes || ''}
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
