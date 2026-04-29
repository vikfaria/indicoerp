import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateBudgetPeriodProps, CreateBudgetPeriodFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function Create({ onSuccess }: CreateBudgetPeriodProps) {
    const { users } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateBudgetPeriodFormData>({
        period_name: '',
        financial_year: '',
        start_date: '',
        end_date: '',
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('budget-planner.budget-periods.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Budget Period')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="period_name">{t('Period Name')}</Label>
                    <Input
                        id="period_name"
                        type="text"
                        value={data.period_name}
                        onChange={(e) => setData('period_name', e.target.value)}
                        placeholder={t('Enter Period Name')}
                        required
                    />
                    <InputError message={errors.period_name} />
                </div>

                <div>
                    <Label htmlFor="financial_year">{t('Financial Year')}</Label>
                    <Input
                        id="financial_year"
                        type="text"
                        value={data.financial_year}
                        onChange={(e) => setData('financial_year', e.target.value)}
                        placeholder={t('Enter Financial Year')}
                        required
                    />
                    <InputError message={errors.financial_year} />
                </div>

                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                    />
                    <InputError message={errors.start_date} />
                </div>

                <div>
                    <Label required>{t('End Date')}</Label>
                    <DatePicker
                        value={data.end_date}
                        onChange={(date) => setData('end_date', date)}
                        placeholder={t('Select End Date')}
                    />
                    <InputError message={errors.end_date} />
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
