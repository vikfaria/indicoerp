import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFormFields } from '@/hooks/useFormFields';
import { EditTerminationProps, EditTerminationFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditTermination({ termination, onSuccess }: EditTerminationProps) {
    const { users, terminationtypes } = usePage<any>().props;
    const [filteredTerminationTypes, setFilteredTerminationTypes] = useState(terminationtypes || []);
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditTerminationFormData>({
        notice_date: termination.notice_date || '',
        termination_date: termination.termination_date || '',
        reason: termination.reason ?? '',
        description: termination.description ?? '',
        document: termination.document || '',
        employee_id: termination.employee_id?.toString() || '',
        termination_type_id: termination.termination_type_id?.toString() || '',
    });

    // AI hooks for reason and description fields
    const reasonAI = useFormFields('aiField', data, setData, errors, 'edit', 'reason', 'Reason', 'hrm', 'termination');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'hrm', 'termination');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.terminations.update', termination.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Termination')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id?.toString() || ''} onValueChange={(value) => setData('employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {users.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="termination_type_id" required>{t('Termination Type')}</Label>
                    <Select value={data.termination_type_id?.toString() || ''} onValueChange={(value) => setData('termination_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Termination Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {terminationtypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.termination_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.termination_type_id} />
                </div>
                
                <div>
                    <Label required>{t('Notice Date')}</Label>
                    <DatePicker
                        value={data.notice_date}
                        onChange={(date) => setData('notice_date', date)}
                        placeholder={t('Select Notice Date')}
                        required
                    />
                    <InputError message={errors.notice_date} />
                </div>
                
                <div>
                    <Label required>{t('Termination Date')}</Label>
                    <DatePicker
                        value={data.termination_date}
                        onChange={(date) => setData('termination_date', date)}
                        placeholder={t('Select Termination Date')}
                        required
                    />
                    <InputError message={errors.termination_date} />
                </div>
                
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="reason" required>{t('Reason')}</Label>
                            <Input
                                id="reason"
                                type="text"
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                                placeholder={t('Enter Reason')}
                                required
                            />
                            <InputError message={errors.reason} />
                        </div>
                        {reasonAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description">{t('Description')}</Label>
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
                    />
                    <InputError message={errors.description} />
                </div>
                
                <div>
                    <MediaPicker
                        label={t('Document')}
                        value={data.document}
                        onChange={(value) => setData('document', Array.isArray(value) ? value[0] || '' : value)}
                        placeholder={t('Select Document...')}
                        showPreview={true}
                        multiple={false}
                    />
                    <InputError message={errors.document} />
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