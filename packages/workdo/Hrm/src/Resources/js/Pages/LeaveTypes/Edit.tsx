import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { EditLeaveTypeProps, EditLeaveTypeFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditLeaveType({ leavetype, onSuccess }: EditLeaveTypeProps) {
    const { } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditLeaveTypeFormData>({
        name: leavetype.name ?? '',
        description: leavetype.description ?? '',
        max_days_per_year: leavetype.max_days_per_year ?? '',
        is_paid: leavetype.is_paid ?? false,
        color: leavetype.color ?? '#FF6B6B',
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.leave-types.update', leavetype.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Leave Type')}</DialogTitle>
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
                    <Label htmlFor="max_days_per_year" required>{t('Max Days Per Year')}</Label>
                    <Input
                        id="max_days_per_year"
                        type="number"
                        step="1"
                        min="0"
                        value={data.max_days_per_year}
                        onChange={(e) => setData('max_days_per_year', e.target.value)}
                        placeholder="0"
                        required
                    />
                    <InputError message={errors.max_days_per_year} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_paid"
                        checked={data.is_paid || false}
                        onCheckedChange={(checked) => setData('is_paid', !!checked)}
                    />
                    <Label htmlFor="is_paid" className="cursor-pointer">{t('Is Paid')}</Label>
                    <InputError message={errors.is_paid} />
                </div>

                <div>
                    <Label htmlFor="color" required>{t('Color')}</Label>
                    <div className="flex gap-2 mt-1">
                        <Input
                            id="color"
                            type="color"
                            value={data.color}
                            onChange={(e) => setData('color', e.target.value)}
                            className="w-16 h-10 p-1 border rounded"
                        />
                        <Input
                            type="text"
                            value={data.color}
                            onChange={(e) => setData('color', e.target.value)}
                            className="flex-1"
                            placeholder="#FF6B6B"
                        />
                    </div>
                    <InputError message={errors.color} />
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