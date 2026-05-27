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
import { CreateTerminationProps, CreateTerminationFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateTerminationProps) {
    const { users, terminationtypes } = usePage<any>().props;
    const [filteredTerminationTypes, setFilteredTerminationTypes] = useState(terminationtypes || []);
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateTerminationFormData>({
        notice_date: '',
        termination_date: '',
        offboarding_letter_delivered_at: '',
        offboarding_assets_returned_at: '',
        offboarding_access_revoked_at: '',
        offboarding_final_payment_at: '',
        offboarding_certificate_issued_at: '',
        offboarding_inss_notified_at: '',
        offboarding_migration_notified_at: '',
        offboarding_archive_completed_at: '',
        offboarding_completed_at: '',
        offboarding_notes: '',
        reason: '',
        description: '',
        document: '',
        employee_id: '',
        termination_type_id: '',
    });

    // AI hooks for reason and description fields
    const reasonAI = useFormFields('aiField', data, setData, errors, 'create', 'reason', 'Reason', 'hrm', 'termination');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'termination');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.terminations.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Termination')}</DialogTitle>
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

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <Label>{t('Letter Delivered At')}</Label>
                        <Input type="date" value={data.offboarding_letter_delivered_at} onChange={(e) => setData('offboarding_letter_delivered_at', e.target.value)} />
                        <InputError message={errors.offboarding_letter_delivered_at} />
                    </div>
                    <div>
                        <Label>{t('Assets Returned At')}</Label>
                        <Input type="date" value={data.offboarding_assets_returned_at} onChange={(e) => setData('offboarding_assets_returned_at', e.target.value)} />
                        <InputError message={errors.offboarding_assets_returned_at} />
                    </div>
                    <div>
                        <Label>{t('Access Revoked At')}</Label>
                        <Input type="date" value={data.offboarding_access_revoked_at} onChange={(e) => setData('offboarding_access_revoked_at', e.target.value)} />
                        <InputError message={errors.offboarding_access_revoked_at} />
                    </div>
                    <div>
                        <Label>{t('Final Payment At')}</Label>
                        <Input type="date" value={data.offboarding_final_payment_at} onChange={(e) => setData('offboarding_final_payment_at', e.target.value)} />
                        <InputError message={errors.offboarding_final_payment_at} />
                    </div>
                    <div>
                        <Label>{t('Certificate Issued At')}</Label>
                        <Input type="date" value={data.offboarding_certificate_issued_at} onChange={(e) => setData('offboarding_certificate_issued_at', e.target.value)} />
                        <InputError message={errors.offboarding_certificate_issued_at} />
                    </div>
                    <div>
                        <Label>{t('INSS Notified At')}</Label>
                        <Input type="date" value={data.offboarding_inss_notified_at} onChange={(e) => setData('offboarding_inss_notified_at', e.target.value)} />
                        <InputError message={errors.offboarding_inss_notified_at} />
                    </div>
                    <div>
                        <Label>{t('Migration Notified At')}</Label>
                        <Input type="date" value={data.offboarding_migration_notified_at} onChange={(e) => setData('offboarding_migration_notified_at', e.target.value)} />
                        <InputError message={errors.offboarding_migration_notified_at} />
                    </div>
                    <div>
                        <Label>{t('Archive Completed At')}</Label>
                        <Input type="date" value={data.offboarding_archive_completed_at} onChange={(e) => setData('offboarding_archive_completed_at', e.target.value)} />
                        <InputError message={errors.offboarding_archive_completed_at} />
                    </div>
                    <div>
                        <Label>{t('Checklist Completed At')}</Label>
                        <Input type="date" value={data.offboarding_completed_at} onChange={(e) => setData('offboarding_completed_at', e.target.value)} />
                        <InputError message={errors.offboarding_completed_at} />
                    </div>
                    <div className="md:col-span-2">
                        <Label>{t('Offboarding Notes')}</Label>
                        <Textarea
                            value={data.offboarding_notes}
                            onChange={(e) => setData('offboarding_notes', e.target.value)}
                            rows={2}
                        />
                        <InputError message={errors.offboarding_notes} />
                    </div>
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
