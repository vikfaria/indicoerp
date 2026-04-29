import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';

import { CreateEmployeeDocumentTypeProps, EmployeeDocumentTypeFormData } from './types';

export default function Create({ onSuccess }: CreateEmployeeDocumentTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<EmployeeDocumentTypeFormData>({
        document_name: '',
        description: '',
        is_required: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.employee-document-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Document Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="document_name">{t('Document Name')}</Label>
                    <Input
                        id="document_name"
                        type="text"
                        value={data.document_name}
                        onChange={(e) => setData('document_name', e.target.value)}
                        placeholder={t('Enter Document Name')}
                        required
                    />
                    <InputError message={errors.document_name} />
                </div>
                
                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_required"
                        checked={data.is_required || false}
                        onCheckedChange={(checked) => setData('is_required', !!checked)}
                    />
                    <Label htmlFor="is_required" className="cursor-pointer">{t('Is Required')}</Label>
                    <InputError message={errors.is_required} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
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