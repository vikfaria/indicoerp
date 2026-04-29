import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { TaxEditProps, TaxFormData } from './types';

export default function Edit({ tax, onSuccess }: TaxEditProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<TaxFormData>({
        tax_name: tax.tax_name,
        rate: tax.rate,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('product-service.taxes.update', tax.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Edit Tax')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="tax_name">{t('Tax Name')}</Label>
                    <Input
                        id="tax_name"
                        type="text"
                        value={data.tax_name}
                        onChange={(e) => setData('tax_name', e.target.value)}
                        placeholder={t('Enter tax name')}
                        className={errors.tax_name ? 'border-red-500' : ''}
                    />
                    {errors.tax_name && <p className="text-sm text-red-500">{errors.tax_name}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="rate">{t('Rate (%)')}</Label>
                    <Input
                        id="rate"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={data.rate}
                        onChange={(e) => setData('rate', parseFloat(e.target.value) || 0)}
                        placeholder={t('Enter tax rate')}
                        className={errors.rate ? 'border-red-500' : ''}
                    />
                    {errors.rate && <p className="text-sm text-red-500">{errors.rate}</p>}
                </div>

                <div className="flex justify-end gap-2 pt-4">
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
