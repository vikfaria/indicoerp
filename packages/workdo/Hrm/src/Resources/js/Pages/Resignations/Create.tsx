import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import MediaPicker from '@/components/MediaPicker';
import { useFormFields } from '@/hooks/useFormFields';
import { CreateResignationProps, CreateResignationFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateResignationProps) {
    const { employees, users, auth } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateResignationFormData>({
        employee_id: employees?.length === 1 ? employees[0].id.toString() : '',
        last_working_date: '',
        reason: '',
        description: '',
        document: '',
        settlement_unused_leave_days: '',
        settlement_other_earnings_amount: '',
        settlement_other_deductions_amount: '',
        settlement_apply_indemnity: false,
        settlement_indemnity_days_per_year: '45',
    });

    // AI hooks for description field
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'resignation');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.resignations.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Resignation')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id} onValueChange={(value) => setData('employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {employees?.map((employee: any) => (
                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                    {employee.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="last_working_date" required>{t('Last Working Date')}</Label>
                    <DatePicker
                        value={data.last_working_date}
                        onChange={(date) => setData('last_working_date', date)}
                        placeholder={t('Select Last Working Date')}
                        required
                    />
                    <InputError message={errors.last_working_date} />
                </div>
                
                <div>
                    <Label htmlFor="reason">{t('Reason')}</Label>
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
                    <Label htmlFor="document">{t('Document')}</Label>
                    <MediaPicker
                        value={data.document}
                        onChange={(value) => setData('document', value)}
                        placeholder={t('Select Document')}
                    />
                    <InputError message={errors.document} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 border rounded-md p-3">
                    <div className="md:col-span-2">
                        <p className="text-sm font-medium">{t('Final Settlement Inputs')}</p>
                    </div>
                    <div>
                        <Label>{t('Unused Leave Days')}</Label>
                        <Input
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.settlement_unused_leave_days}
                            onChange={(e) => setData('settlement_unused_leave_days', e.target.value)}
                        />
                        <InputError message={errors.settlement_unused_leave_days} />
                    </div>
                    <div>
                        <Label>{t('Other Earnings')}</Label>
                        <Input
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.settlement_other_earnings_amount}
                            onChange={(e) => setData('settlement_other_earnings_amount', e.target.value)}
                        />
                        <InputError message={errors.settlement_other_earnings_amount} />
                    </div>
                    <div>
                        <Label>{t('Other Deductions')}</Label>
                        <Input
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.settlement_other_deductions_amount}
                            onChange={(e) => setData('settlement_other_deductions_amount', e.target.value)}
                        />
                        <InputError message={errors.settlement_other_deductions_amount} />
                    </div>
                    <div>
                        <Label>{t('Indemnity Days / Year')}</Label>
                        <Input
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.settlement_indemnity_days_per_year}
                            onChange={(e) => setData('settlement_indemnity_days_per_year', e.target.value)}
                        />
                        <InputError message={errors.settlement_indemnity_days_per_year} />
                    </div>
                    <div className="md:col-span-2 flex items-center gap-2">
                        <input
                            id="resignation_settlement_apply_indemnity"
                            type="checkbox"
                            checked={data.settlement_apply_indemnity}
                            onChange={(e) => setData('settlement_apply_indemnity', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300"
                        />
                        <Label htmlFor="resignation_settlement_apply_indemnity">{t('Apply indemnity calculation')}</Label>
                    </div>
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
