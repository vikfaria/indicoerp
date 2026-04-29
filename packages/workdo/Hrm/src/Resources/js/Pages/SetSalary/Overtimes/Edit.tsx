import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/ui/input-error';
import { DatePicker } from '@/components/ui/date-picker';

interface Overtime {
    id: number;
    title: string;
    total_days: number;
    hours: number;
    rate: number;
    start_date: string;
    end_date: string;
    notes: string;
    status: string;
}

interface EditOvertimeProps {
    overtime: Overtime;
    onSuccess: () => void;
}

export default function Edit({ overtime, onSuccess }: EditOvertimeProps) {
    const { t } = useTranslation();
    
    const { data, setData, put, processing, errors } = useForm({
        title: overtime.title,
        total_days: overtime.total_days.toString(),
        hours: overtime.hours.toString(),
        rate: overtime.rate.toString(),
        start_date: overtime.start_date ? new Date(overtime.start_date).toISOString().split('T')[0] : '',
        end_date: overtime.end_date ? new Date(overtime.end_date).toISOString().split('T')[0] : '',
        notes: overtime.notes || '',
        status: overtime.status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.overtimes.update', overtime.id), {
            only: ['errors', 'flash', 'overtimes'],
            onSuccess: () => {
                onSuccess();
            },
            onError: () => {
                // Keep modal open on validation errors
            }
        });
    };

    return (
        <DialogContent className="max-w-md max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Edit Overtime')}</DialogTitle>
            </DialogHeader>
            
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter overtime title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="total_days" required>{t('Total Days')}</Label>
                        <Input
                            id="total_days"
                            type="number"
                            min="1"
                            value={data.total_days}
                            onChange={(e) => setData('total_days', e.target.value)}
                            placeholder={t('Enter total days')}
                            required
                        />
                        <InputError message={errors.total_days} />
                    </div>

                    <div>
                        <Label htmlFor="hours" required>{t('Hours')}</Label>
                        <Input
                            id="hours"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.hours}
                            onChange={(e) => setData('hours', e.target.value)}
                            placeholder={t('Enter hours')}
                            required
                        />
                        <InputError message={errors.hours} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="rate" required>{t('Rate')}</Label>
                    <Input
                        id="rate"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.rate}
                        onChange={(e) => setData('rate', e.target.value)}
                        placeholder={t('Enter rate')}
                        required
                    />
                    <InputError message={errors.rate} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="start_date" required>{t('Start Date')}</Label>
                        <DatePicker
                            id="start_date"
                            value={data.start_date}
                            onChange={(value) => setData('start_date', value)}
                            required
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div>
                        <Label htmlFor="end_date" required>{t('End Date')}</Label>
                        <DatePicker
                            id="end_date"
                            value={data.end_date}
                            onChange={(value) => setData('end_date', value)}
                            required
                        />
                        <InputError message={errors.end_date} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="status" required>{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">{t('Active')}</SelectItem>
                            <SelectItem value="expired">{t('Expired')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div>
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={t('Enter notes for overtime')}
                        rows={3}
                    />
                    <InputError message={errors.notes} />
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