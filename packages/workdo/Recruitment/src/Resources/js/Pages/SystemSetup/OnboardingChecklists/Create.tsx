import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { CreateOnboardingChecklistProps, OnboardingChecklistFormData } from './types';

export default function Create({ onSuccess }: CreateOnboardingChecklistProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<OnboardingChecklistFormData>({
        name: '',
        description: '',
        is_default: false,
        status: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.onboarding-checklists.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Onboarding Checklist')}</DialogTitle>
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
                        id="is_default"
                        checked={data.is_default || false}
                        onCheckedChange={(checked) => setData('is_default', !!checked)}
                    />
                    <Label htmlFor="is_default" className="cursor-pointer">{t('Is Default')}</Label>
                    <InputError message={errors.is_default} />
                </div>
                
                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status ? "1" : "0"} onValueChange={(value) => setData('status', value === "1")}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">{t('Active')}</SelectItem>
                            <SelectItem value="0">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
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