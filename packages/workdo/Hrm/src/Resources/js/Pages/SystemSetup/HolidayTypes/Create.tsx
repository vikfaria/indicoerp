import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateHolidayTypeProps, HolidayTypeFormData } from './types';

export default function Create({ onSuccess }: CreateHolidayTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<HolidayTypeFormData>({
        holiday_type: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.holiday-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Holiday Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="holiday_type">{t('Holiday Type')}</Label>
                    <Input
                        id="holiday_type"
                        type="text"
                        value={data.holiday_type}
                        onChange={(e) => setData('holiday_type', e.target.value)}
                        placeholder={t('Enter Holiday Type')}
                        required
                    />
                    <InputError message={errors.holiday_type} />
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