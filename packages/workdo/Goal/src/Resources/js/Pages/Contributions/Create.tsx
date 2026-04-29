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
import { CreateContributionFormData, CreateContributionProps, ContributionsIndexProps } from './types';

export default function Create({ onSuccess }: CreateContributionProps) {
    const { t } = useTranslation();
    const { goals } = usePage<ContributionsIndexProps>().props;

    const { data, setData, post, processing, errors } = useForm<CreateContributionFormData>({
        goal_id: goals.length > 0 ? goals[0].id : 0,
        contribution_date: new Date().toISOString().split('T')[0],
        contribution_amount: 0,
        contribution_type: 'manual',
        reference_type: 'manual',
        reference_id: undefined,
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('goal.contributions.store'), {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Create Contribution')}</DialogTitle>
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
                            {processing ? t('Creating...') : t('Create')}
                        </Button>
                    </div>
                </form>
        </DialogContent>
    );
}
