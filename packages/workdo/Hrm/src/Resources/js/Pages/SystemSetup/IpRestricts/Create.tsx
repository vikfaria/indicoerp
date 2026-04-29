import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateIpRestrictProps, IpRestrictFormData } from './types';

export default function Create({ onSuccess }: CreateIpRestrictProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<IpRestrictFormData>({
        ip: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.ip-restricts.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Ip Restrict')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="ip">{t('Ip')}</Label>
                    <Input
                        id="ip"
                        type="text"
                        value={data.ip}
                        onChange={(e) => setData('ip', e.target.value)}
                        placeholder={t('Enter Ip')}
                        required
                    />
                    <InputError message={errors.ip} />
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