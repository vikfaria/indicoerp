import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { EditLabelProps, LabelFormData } from './types';

export default function Edit({ label, onSuccess, pipelines }: EditLabelProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<LabelFormData>({
        name: label.name ?? '',
        color: label.color ?? '#FF6B6B',
        pipeline_id: label.pipeline_id?.toString() || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('lead.labels.update', label.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Label')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
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
                
                <div>
                    <Label htmlFor="color">{t('Color')}</Label>
                    <Input
                        id="color"
                        type="color"
                        value={data.color}
                        onChange={(e) => setData('color', e.target.value)}
                        className="mt-2 h-10 w-20"
                    />
                    <InputError message={errors.color} />
                </div>
                
                <div>
                    <Label htmlFor="pipeline_id">{t('Pipeline')}</Label>
                    <Select value={data.pipeline_id?.toString() || ''} onValueChange={(value) => setData('pipeline_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Pipeline')} />
                        </SelectTrigger>
                        <SelectContent>
                            {pipelines.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.pipeline_id} />
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