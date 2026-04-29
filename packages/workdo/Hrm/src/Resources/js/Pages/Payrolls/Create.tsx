import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import { CurrencyInput } from '@/components/ui/currency-input';
import { CreatePayrollProps, CreatePayrollFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useFormFields } from '@/hooks/useFormFields';


export default function Create({ onSuccess }: CreatePayrollProps) {
    const {  } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreatePayrollFormData>({
        title: '',
        payroll_frequency: 'weekly',
        pay_period_start: '',
        pay_period_end: '',
        pay_date: '',
        notes: '',
        total_gross_pay: '',
        total_deductions: '',
        total_net_pay: '',
        employee_count: '',
        status: 'draft',
    });

    // Bank Account Field
    const bankAccountField = useFormFields('bankAccountField', data, setData, errors);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.payrolls.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Payroll')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title">{t('Title')}</Label>
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
                        required
                    />
                    <InputError message={errors.pay_period_end} />
                </div>

                <div>
                    <Label required>{t('Pay Date')}</Label>
                    <DatePicker
                        value={data.pay_date}
                        onChange={(date) => setData('pay_date', date)}
                        placeholder={t('Select Pay Date')}
                        required
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
