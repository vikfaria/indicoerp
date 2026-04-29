import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

import { CreateRevenueCategoriesProps, RevenueCategoriesFormData } from './types';

export default function Create({ onSuccess, chartofaccounts }: CreateRevenueCategoriesProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<RevenueCategoriesFormData>({
        category_name: '',
        category_code: '',
        gl_account_id: '',
        description: '',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('account.revenue-categories.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Revenue Categories')}</DialogTitle>
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
                    <Label htmlFor="gl_account_id">{t('Gl Account')}</Label>
                    <Select value={data.gl_account_id?.toString() || ''} onValueChange={(value) => setData('gl_account_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Gl Account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {chartofaccounts.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.account_code} - {item.account_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.gl_account_id} />
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                    <Label htmlFor="is_active">{data.is_active ? t('Enabled') : t('Disabled')}</Label>
                    <InputError message={errors.is_active} />
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
