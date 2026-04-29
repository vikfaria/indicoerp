import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import MediaPicker from '@/components/MediaPicker';
import { useFormFields } from '@/hooks/useFormFields';
import { CreateDocumentProps, CreateDocumentFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function Create({ onSuccess }: CreateDocumentProps) {
    const { documentcategories, users } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateDocumentFormData>({
        title: '',
        description: '',
        document_category_id: '',
        document: '',
    });

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, setData, errors, 'create', 'title', 'Title', 'hrm', 'document');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'document');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.documents.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Document')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="title">{t('Title')}</Label>
                            <Input
                                id="title"
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder={t('Enter Title')}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>
                        {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>
                
                <div>
                    <Label htmlFor="document_category_id" required>{t('Document Category')}</Label>
                    <Select value={data.document_category_id?.toString() || ''} onValueChange={(value) => setData('document_category_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Document Category')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {documentcategories?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.document_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.document_category_id} />
                </div>
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description" required>{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description} />
                </div>
                
                <div>
                    <MediaPicker
                        label={t('Document')}
                        value={data.document}
                        onChange={(value) => setData('document', value as string)}
                        placeholder={t('Select Document')}
                        showPreview={true}
                        required
                    />
                    <InputError message={errors.document} />
                </div>
                

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}