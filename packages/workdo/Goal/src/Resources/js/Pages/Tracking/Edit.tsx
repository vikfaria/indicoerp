import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EditTrackingFormData, EditTrackingProps } from './types';

export default function Edit({ tracking, goals, onSuccess }: EditTrackingProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<EditTrackingFormData>({
        goal_id: tracking.goal_id.toString(),
        tracking_date: tracking.tracking_date,
        previous_amount: tracking.previous_amount,
        contribution_amount: tracking.contribution_amount,
        current_amount: tracking.current_amount,
        progress_percentage: tracking.progress_percentage,
        days_remaining: tracking.days_remaining,
        projected_completion_date: tracking.projected_completion_date || '',
        on_track_status: tracking.on_track_status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('goal.tracking.update', tracking.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Edit Tracking')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="goal_id">{t('Goal')}</Label>
                    <Select value={data.goal_id} onValueChange={(value) => setData('goal_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Goal')} />
                        </SelectTrigger>
                        <SelectContent>
                            {goals?.map((goal) => (
                                <SelectItem key={goal.id} value={goal.id.toString()}>
                                    {goal.goal_name}
                                </SelectItem>
                            )) || []}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.goal_id} />
                </div>

                <div>
                    <Label required>{t('Tracking Date')}</Label>
                    <DatePicker
                        value={data.tracking_date}
                        onChange={(value) => setData('tracking_date', value)}
                        placeholder={t('Select tracking date')}
                    />
                    <InputError message={errors.tracking_date} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <CurrencyInput
                            label={t('Previous Amount')}
                            value={data.previous_amount.toString()}
                            onChange={(value) => setData('previous_amount', parseFloat(value) || 0)}
                            error={errors.previous_amount}
                            required
                        />
                    </div>

                    <div>
                        <CurrencyInput
                            label={t('Contribution Amount')}
                            value={data.contribution_amount.toString()}
                            onChange={(value) => setData('contribution_amount', parseFloat(value) || 0)}
                            error={errors.contribution_amount}
                            required
                        />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <CurrencyInput
                            label={t('Current Amount')}
                            value={data.current_amount.toString()}
                            onChange={(value) => setData('current_amount', parseFloat(value) || 0)}
                            error={errors.current_amount}
                            required
                        />
                    </div>

                    <div>
                        <Label htmlFor="progress_percentage">{t('Progress Percentage')}</Label>
                        <Input
                            id="progress_percentage"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={data.progress_percentage}
                            onChange={(e) => setData('progress_percentage', parseFloat(e.target.value) || 0)}
                            placeholder={t('Enter progress percentage')}
                            required
                        />
                        <InputError message={errors.progress_percentage} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="days_remaining">{t('Days Remaining')}</Label>
                        <Input
                            id="days_remaining"
                            type="number"
                            min="0"
                            value={data.days_remaining}
                            onChange={(e) => setData('days_remaining', parseInt(e.target.value) || 0)}
                            placeholder={t('Enter days remaining')}
                            required
                        />
                        <InputError message={errors.days_remaining} />
                    </div>

                    <div>
                        <Label>{t('Projected Completion Date')}</Label>
                        <DatePicker
                            value={data.projected_completion_date}
                            onChange={(value) => setData('projected_completion_date', value)}
                            placeholder={t('Select projected date')}
                        />
                        <InputError message={errors.projected_completion_date} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="on_track_status">{t('Status')}</Label>
                    <Select value={data.on_track_status} onValueChange={(value) => setData('on_track_status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ahead">{t('Ahead')}</SelectItem>
                            <SelectItem value="on_track">{t('On Track')}</SelectItem>
                            <SelectItem value="behind">{t('Behind')}</SelectItem>
                            <SelectItem value="critical">{t('Critical')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.on_track_status} />
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
