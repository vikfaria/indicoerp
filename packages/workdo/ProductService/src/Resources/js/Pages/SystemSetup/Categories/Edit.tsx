import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { EditItemCategoryProps, ItemCategoryFormData } from './types';

export default function Edit({ category, onSuccess }: EditItemCategoryProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<ItemCategoryFormData>({
        name: category.name,
        color: category.color,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('product-service.item-categories.update', category.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Category')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="edit_name">{t('Name')}</Label>
                    <Input
                        id="edit_name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter category name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>



                <div>
                    <Label htmlFor="edit_color">{t('Color')}</Label>
                    <Input
                        id="edit_color"
                        type="color"
                        value={data.color}
                        onChange={(e) => setData('color', e.target.value)}
                        className="mt-2 h-10 w-20"
                    />
                    <InputError message={errors.color} />
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
