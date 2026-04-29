import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { EditCategoryProps, EditCategoryFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditCategory({ category, onSuccess }: EditCategoryProps) {
    const {  } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditCategoryFormData>({
        category_name: category.category_name ?? '',
        category_code: category.category_code ?? '',
        description: category.description ?? '',
        is_active: category.is_active ?? false,
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('goal.categories.update', category.id), {
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
                    <Label htmlFor="category_name">{t('Category Name')}</Label>
                    <Input
                        id="category_name"
                        type="text"
                        value={data.category_name}
                        onChange={(e) => setData('category_name', e.target.value)}
                        placeholder={t('Enter Category Name')}
                        required
                    />
                    <InputError message={errors.category_name} />
                </div>
                
                <div>
                    <Label htmlFor="category_code">{t('Category Code')}</Label>
                    <Input
                        id="category_code"
                        type="text"
                        value={data.category_code}
                        onChange={(e) => setData('category_code', e.target.value)}
                        placeholder={t('Enter Category Code')}
                        required
                    />
                    <InputError message={errors.category_code} />
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
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                    <Label htmlFor="is_active">{t('Is Active')}</Label>
                    <InputError message={errors.is_active} />
                </div>
                
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
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