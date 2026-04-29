import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';

import { EditInterviewTypeProps, InterviewTypeFormData } from './types';

export default function Edit({ interviewtype, onSuccess }: EditInterviewTypeProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<InterviewTypeFormData>({
        name: interviewtype.name ?? '',
        description: interviewtype.description ?? '',
        is_active: interviewtype.is_active ?? false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.interview-types.update', interviewtype.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Interview Type')}</DialogTitle>
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
                        maxLength={50}
                    />
                    <div className="flex justify-between text-xs text-gray-500 mt-1">
                        <span></span>
                        <span>{data.name.length}/50</span>
                    </div>
                    <InputError message={errors.name} />
                </div>
                
                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                        maxLength={100}
                    />
                    <div className="flex justify-between text-xs text-gray-500 mt-1">
                        <span></span>
                        <span>{data.description.length}/100</span>
                    </div>
                    <InputError message={errors.description} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_active"
                        checked={data.is_active || false}
                        onCheckedChange={(checked) => setData('is_active', !!checked)}
                    />
                    <Label htmlFor="is_active" className="cursor-pointer">{t('Is Active')}</Label>
                    <InputError message={errors.is_active} />
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