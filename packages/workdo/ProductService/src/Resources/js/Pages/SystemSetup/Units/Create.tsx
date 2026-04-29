import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { UnitCreateProps, UnitFormData } from './types';

export default function Create({ onSuccess }: UnitCreateProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm<UnitFormData>({
        unit_name: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('product-service.units.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Create Unit')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="unit_name">{t('Unit Name')}</Label>
                    <Input
                        id="unit_name"
                        type="text"
                        value={data.unit_name}
                        onChange={(e) => setData('unit_name', e.target.value)}
                        placeholder={t('Enter unit name')}
                        className={errors.unit_name ? 'border-red-500' : ''}
                    />
                    {errors.unit_name && <p className="text-sm text-red-500">{errors.unit_name}</p>}
                </div>

                <div className="flex justify-end gap-2 pt-4">
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
