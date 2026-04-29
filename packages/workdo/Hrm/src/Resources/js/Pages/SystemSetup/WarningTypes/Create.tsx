import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateWarningTypeProps, WarningTypeFormData } from './types';

export default function Create({ onSuccess }: CreateWarningTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<WarningTypeFormData>({
        warning_type_name: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.warning-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Warning Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="warning_type_name">{t('Warning Type Name')}</Label>
                    <Input
                        id="warning_type_name"
                        type="text"
                        value={data.warning_type_name}
                        onChange={(e) => setData('warning_type_name', e.target.value)}
                        placeholder={t('Enter Warning Type Name')}
                        required
                    />
                    <InputError message={errors.warning_type_name} />
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