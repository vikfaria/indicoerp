import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { EditContributionFormData, EditContributionProps, ContributionsIndexProps } from './types';

export default function Edit({ contribution, onSuccess }: EditContributionProps) {
    const { t } = useTranslation();
    const { goals } = usePage<ContributionsIndexProps>().props;

    const { data, setData, put, processing, errors } = useForm<EditContributionFormData>({
        goal_id: contribution.goal_id,
        contribution_date: contribution.contribution_date,
        contribution_amount: contribution.contribution_amount,
        contribution_type: contribution.contribution_type,
        reference_type: contribution.reference_type || 'manual',
        reference_id: contribution.reference_id,
        notes: contribution.notes || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('goal.contributions.update', contribution.id), {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Edit Contribution')}</DialogTitle>
            </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="goal_id" required>{t('Goal')}</Label>
                        <Select value={data.goal_id.toString()} onValueChange={(value) => setData('goal_id', parseInt(value))}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Goal')} />
                            </SelectTrigger>
                            <SelectContent>
                                {goals.map((goal) => (
                                    <SelectItem key={goal.id} value={goal.id.toString()}>
                                        {goal.goal_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.goal_id && <p className="text-sm text-red-600">{errors.goal_id}</p>}
                    </div>

                    <div>
                        <Label htmlFor="contribution_date" required>{t('Date')}</Label>
                        <DatePicker
                            value={data.contribution_date}
                            onChange={(value) => setData('contribution_date', value)}
                            placeholder={t('Select contribution date')}
                        />
                        {errors.contribution_date && <p className="text-sm text-red-600">{errors.contribution_date}</p>}
                    </div>

                    <div>
                        <Label htmlFor="contribution_amount" required>{t('Amount')}</Label>
                        <CurrencyInput
                            value={data.contribution_amount}
                            onChange={(value) => setData('contribution_amount', value)}
                            placeholder={t('Enter contribution amount')}
                        />
                        {errors.contribution_amount && <p className="text-sm text-red-600">{errors.contribution_amount}</p>}
                    </div>

                    <div>
                        <Label htmlFor="notes">{t('Notes')}</Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            placeholder={t('Enter notes...')}
                        />
                        {errors.notes && <p className="text-sm text-red-600">{errors.notes}</p>}
                    </div>

                    <div className="flex justify-end space-x-2">
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
