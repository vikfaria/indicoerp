import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useFormFields } from '@/hooks/useFormFields';
import { CreateHolidayProps, CreateHolidayFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateHolidayProps) {
    const { holidayTypes } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateHolidayFormData>({
        name: '',
        start_date: '',
        end_date: '',
        holiday_type_id: '',
        description: '',
        is_paid: false,
        is_sync_google_calendar: false,
        is_sync_outlook_calendar: false,
    });

    // AI hooks for name and description fields
    const nameAI = useFormFields('aiField', data, setData, errors, 'create', 'name', 'Name', 'hrm', 'holiday');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'holiday');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.holidays.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Holiday')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="name" required>{t('Name')}</Label>
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
                        {nameAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>
                
                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                        required
                    />
                    <InputError message={errors.start_date} />
                </div>
                
                <div>
                    <Label required>{t('End Date')}</Label>
                    <DatePicker
                        value={data.end_date}
                        onChange={(date) => setData('end_date', date)}
                        placeholder={t('Select End Date')}
                        required
                    />
                    <InputError message={errors.end_date} />
                </div>
                
                <div>
                    <Label htmlFor="holiday_type_id" required >{t('Holiday Type')}</Label>
                    <Select value={data.holiday_type_id?.toString() || ''} onValueChange={(value) => setData('holiday_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Holiday Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {holidayTypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.holiday_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.holiday_type_id} />
                </div>
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description" required>{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description} />
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
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_sync_google_calendar"
                        checked={data.is_sync_google_calendar || false}
                        onCheckedChange={(checked) => setData('is_sync_google_calendar', !!checked)}
                    />
                    <Label htmlFor="is_sync_google_calendar" className="cursor-pointer">{t('Is Sync Google Calendar')}</Label>
                    <InputError message={errors.is_sync_google_calendar} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_sync_outlook_calendar"
                        checked={data.is_sync_outlook_calendar || false}
                        onCheckedChange={(checked) => setData('is_sync_outlook_calendar', !!checked)}
                    />
                    <Label htmlFor="is_sync_outlook_calendar" className="cursor-pointer">{t('Is Sync Outlook Calendar')}</Label>
                    <InputError message={errors.is_sync_outlook_calendar} />
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