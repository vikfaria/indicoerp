import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import { CurrencyInput } from '@/components/ui/currency-input';
import { EditPayrollProps, EditPayrollFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useFormFields } from '@/hooks/useFormFields';

export default function EditPayroll({ payroll, onSuccess }: EditPayrollProps) {
    const {  } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditPayrollFormData>({
        title: payroll.title ?? '',
        payroll_frequency: payroll.payroll_frequency || 'weekly',
        pay_period_start: payroll.pay_period_start || '',
        pay_period_end: payroll.pay_period_end || '',
        pay_date: payroll.pay_date || '',
        notes: payroll.notes ?? '',
        total_gross_pay: payroll.total_gross_pay ?? '',
        total_deductions: payroll.total_deductions ?? '',
        total_net_pay: payroll.total_net_pay ?? '',
        employee_count: payroll.employee_count ?? '',
        status: payroll.status || 'draft',
        is_payroll_paid: payroll.is_payroll_paid || 'unpaid',
        bank_account_id: payroll.bank_account_id?.toString() ?? '',
    });

        // Bank Account Field
        const bankAccountField = useFormFields('bankAccountField', data, setData, errors, 'edit');


    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.payrolls.update', payroll.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Payroll')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        type="text"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter Title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label required>{t('Payroll Frequency')}</Label>
                    <Select value={data.payroll_frequency || 'weekly'} onValueChange={(value) => setData('payroll_frequency', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Payroll Frequency')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="weekly">{t('Weekly')}</SelectItem>
                            <SelectItem value="biweekly">{t('Bi-Weekly')}</SelectItem>
                            <SelectItem value="monthly">{t('Monthly')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.payroll_frequency} />
                </div>

                <div>
                    <Label required>{t('Pay Period Start')}</Label>
                    <DatePicker
                        value={data.pay_period_start}
                        onChange={(date) => setData('pay_period_start', date)}
                        placeholder={t('Select Pay Period Start')}
                        required
                    />
                    <InputError message={errors.pay_period_start} />
                </div>

                <div>
                    <Label required>{t('Pay Period End')}</Label>
                    <DatePicker
                        value={data.pay_period_end}
                        onChange={(date) => setData('pay_period_end', date)}
                        placeholder={t('Select Pay Period End')}
                    />
                    <InputError message={errors.pay_period_end} />
                </div>

                <div>
                    <Label>{t('Pay Date')}</Label>
                    <DatePicker
                        value={data.pay_date}
                        onChange={(date) => setData('pay_date', date)}
                        placeholder={t('Select Pay Date')}
                    />
                    <InputError message={errors.pay_date} />
                </div>

                {bankAccountField.map((field) => (
                    <div key={field.id}>{field.component}</div>
                ))}

                <div>
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={t('Enter Notes')}
                        rows={3}
                    />
                    <InputError message={errors.notes} />
                </div>



                <div>
                    <Label>{t('Status')}</Label>
                    <Select value={data.status || 'draft'} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">{t('Draft')}</SelectItem>
                            <SelectItem value="processing">{t('Processing')}</SelectItem>
                            <SelectItem value="completed">{t('Completed')}</SelectItem>
                            <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div>
                    <Label>{t('Payment Status')}</Label>
                    <Select value={data.is_payroll_paid || 'unpaid'} onValueChange={(value) => setData('is_payroll_paid', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Payment Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="paid">{t('Paid')}</SelectItem>
                            <SelectItem value="unpaid">{t('Unpaid')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.is_payroll_paid} />
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
