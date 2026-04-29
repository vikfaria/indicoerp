import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { CreateJobLocationProps, CreateJobLocationFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateJobLocationProps) {
    const {  } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateJobLocationFormData>({
        name: '',
        remote_work: false,
        address: '',
        city: '',
        state: '',
        country: '',
        postal_code: '',
        status: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.job-locations.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Job Location')}</DialogTitle>
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

                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="remote_work"
                        checked={data.remote_work || false}
                        onCheckedChange={(checked) => setData('remote_work', !!checked)}
                    />
                    <Label htmlFor="remote_work" className="cursor-pointer">{t('Remote Work')}</Label>
                    <InputError message={errors.remote_work} />
                </div>

                <div>
                    <Label htmlFor="address">{t('Address')}</Label>
                    <Textarea
                        id="address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        placeholder={t('Enter Address')}
                        rows={3}
                    />
                    <InputError message={errors.address} />
                </div>

                <div>
                    <Label htmlFor="city">{t('City')}</Label>
                    <Input
                        id="city"
                        type="text"
                        value={data.city}
                        onChange={(e) => setData('city', e.target.value)}
                        placeholder={t('Enter City')}
                        required
                    />
                    <InputError message={errors.city} />
                </div>

                <div>
                    <Label htmlFor="state">{t('State')}</Label>
                    <Input
                        id="state"
                        type="text"
                        value={data.state}
                        onChange={(e) => setData('state', e.target.value)}
                        placeholder={t('Enter State')}
                        required
                    />
                    <InputError message={errors.state} />
                </div>

                <div>
                    <Label htmlFor="country">{t('Country')}</Label>
                    <Input
                        id="country"
                        type="text"
                        value={data.country}
                        onChange={(e) => setData('country', e.target.value)}
                        placeholder={t('Enter Country')}
                        required
                    />
                    <InputError message={errors.country} />
                </div>

                <div>
                    <Label htmlFor="postal_code">{t('Postal Code')}</Label>
                    <Input
                        id="postal_code"
                        type="text"
                        value={data.postal_code}
                        onChange={(e) => setData('postal_code', e.target.value)}
                        placeholder={t('Enter Postal Code')}
                        required
                    />
                    <InputError message={errors.postal_code} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="status"
                        checked={data.status || false}
                        onCheckedChange={(checked) => setData('status', !!checked)}
                    />
                    <Label htmlFor="status" className="cursor-pointer">{t('Status')}</Label>
                    <InputError message={errors.status} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
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