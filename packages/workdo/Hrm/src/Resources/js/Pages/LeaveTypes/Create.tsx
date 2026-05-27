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
import { CreateLeaveTypeProps, CreateLeaveTypeFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateLeaveTypeProps) {
    const {  } = usePage<any>().props;

    const { t } = useTranslation();
    const legalCodes = [
        { value: 'annual', label: t('Annual') },
        { value: 'maternity', label: t('Maternity') },
        { value: 'paternity', label: t('Paternity') },
        { value: 'adoption', label: t('Adoption') },
        { value: 'foster_care', label: t('Foster Care') },
        { value: 'sick_leave', label: t('Sick Leave') },
        { value: 'bereavement', label: t('Bereavement') },
        { value: 'marriage', label: t('Marriage') },
        { value: 'family_assistance', label: t('Family Assistance') },
        { value: 'union_leave', label: t('Union Leave') },
        { value: 'work_accident', label: t('Work Accident') },
        { value: 'public_service', label: t('Public Service') },
        { value: 'other', label: t('Other') },
    ];

    const { data, setData, post, processing, errors } = useForm<CreateLeaveTypeFormData>({
        name: '',
        legal_code: '',
        description: '',
        max_days_per_year: '',
        is_paid: false,
        requires_supporting_document: false,
        must_be_consecutive: false,
        fixed_duration_days: '',
        min_advance_notice_days: '',
        pre_event_start_window_days: '',
        post_event_start_offset_days: '',
        allow_cash_out: false,
        min_effective_rest_days: '',
        color: '#FF6B6B',
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.leave-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Leave Type')}</DialogTitle>
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
                    <Label htmlFor="legal_code">{t('Legal Leave Code')}</Label>
                    <Select value={data.legal_code || ''} onValueChange={(value) => setData('legal_code', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Legal Code')} />
                        </SelectTrigger>
                        <SelectContent>
                            {legalCodes.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.legal_code} />
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

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="requires_supporting_document"
                            checked={data.requires_supporting_document || false}
                            onCheckedChange={(checked) => setData('requires_supporting_document', !!checked)}
                        />
                        <Label htmlFor="requires_supporting_document" className="cursor-pointer">{t('Requires Supporting Document')}</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="must_be_consecutive"
                            checked={data.must_be_consecutive || false}
                            onCheckedChange={(checked) => setData('must_be_consecutive', !!checked)}
                        />
                        <Label htmlFor="must_be_consecutive" className="cursor-pointer">{t('Must Be Consecutive')}</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="allow_cash_out"
                            checked={data.allow_cash_out || false}
                            onCheckedChange={(checked) => setData('allow_cash_out', !!checked)}
                        />
                        <Label htmlFor="allow_cash_out" className="cursor-pointer">{t('Allow Cash Out')}</Label>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="fixed_duration_days">{t('Fixed Duration (Calendar Days)')}</Label>
                        <Input
                            id="fixed_duration_days"
                            type="number"
                            min="1"
                            value={data.fixed_duration_days}
                            onChange={(e) => setData('fixed_duration_days', e.target.value)}
                            placeholder={t('Optional')}
                        />
                        <InputError message={errors.fixed_duration_days} />
                    </div>
                    <div>
                        <Label htmlFor="min_advance_notice_days">{t('Minimum Notice (Days)')}</Label>
                        <Input
                            id="min_advance_notice_days"
                            type="number"
                            min="0"
                            value={data.min_advance_notice_days}
                            onChange={(e) => setData('min_advance_notice_days', e.target.value)}
                            placeholder={t('Optional')}
                        />
                        <InputError message={errors.min_advance_notice_days} />
                    </div>
                    <div>
                        <Label htmlFor="pre_event_start_window_days">{t('Event Window Before Start (Days)')}</Label>
                        <Input
                            id="pre_event_start_window_days"
                            type="number"
                            min="0"
                            value={data.pre_event_start_window_days}
                            onChange={(e) => setData('pre_event_start_window_days', e.target.value)}
                            placeholder={t('Optional')}
                        />
                        <InputError message={errors.pre_event_start_window_days} />
                    </div>
                    <div>
                        <Label htmlFor="post_event_start_offset_days">{t('Event Start Offset (Days)')}</Label>
                        <Input
                            id="post_event_start_offset_days"
                            type="number"
                            min="0"
                            value={data.post_event_start_offset_days}
                            onChange={(e) => setData('post_event_start_offset_days', e.target.value)}
                            placeholder={t('Optional')}
                        />
                        <InputError message={errors.post_event_start_offset_days} />
                    </div>
                    <div>
                        <Label htmlFor="min_effective_rest_days">{t('Min Effective Rest Days')}</Label>
                        <Input
                            id="min_effective_rest_days"
                            type="number"
                            min="1"
                            value={data.min_effective_rest_days}
                            onChange={(e) => setData('min_effective_rest_days', e.target.value)}
                            placeholder={t('Optional')}
                        />
                        <InputError message={errors.min_effective_rest_days} />
                    </div>
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
