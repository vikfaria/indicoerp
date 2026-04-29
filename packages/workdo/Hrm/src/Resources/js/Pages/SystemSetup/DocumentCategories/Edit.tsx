import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';

import { EditDocumentCategoryProps, DocumentCategoryFormData } from './types';

export default function Edit({ documentcategory, onSuccess }: EditDocumentCategoryProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<DocumentCategoryFormData>({
        document_type: documentcategory.document_type ?? '',
        status: documentcategory.status ?? false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.document-categories.update', documentcategory.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Document Category')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="document_type">{t('Document Type')}</Label>
                    <Input
                        id="document_type"
                        type="text"
                        value={data.document_type}
                        onChange={(e) => setData('document_type', e.target.value)}
                        placeholder={t('Enter Document Type')}
                        required
                    />
                    <InputError message={errors.document_type} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="status"
                        checked={data.status || false}
                        onCheckedChange={(checked) => setData('status', !!checked)}
                    />
                    <Label htmlFor="status" className="cursor-pointer">{t('Enable/Disable')}</Label>
                    <InputError message={errors.status} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
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