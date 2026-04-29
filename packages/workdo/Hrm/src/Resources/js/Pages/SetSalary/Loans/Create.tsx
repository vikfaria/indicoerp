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

interface CreateLoanProps {
    employeeId: number;
    loanTypes: Array<{ id: number; name: string }>;
    onSuccess: () => void;
}

export default function Create({ employeeId, loanTypes, onSuccess }: CreateLoanProps) {
    const { t } = useTranslation();
    
    const { data, setData, post, processing, errors } = useForm({
        employee_id: employeeId,
        title: '',
        loan_type_id: '',
        type: '',
        amount: '',
        start_date: '',
        end_date: '',
        reason: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.loans.store'), {
            only: ['errors', 'flash', 'loans'],
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
                <DialogTitle>{t('Add Loan')}</DialogTitle>
            </DialogHeader>
            
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter loan title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label htmlFor="loan_type_id" required>{t('Loan Type')}</Label>
                    <Select value={data.loan_type_id} onValueChange={(value) => setData('loan_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select loan type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {loanTypes.map((type) => (
                                <SelectItem key={type.id} value={type.id.toString()}>
                                    {type.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.loan_type_id} />
                </div>

                <div>
                    <Label htmlFor="type" required>{t('Type')}</Label>
                    <Select value={data.type} onValueChange={(value) => setData('type', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select type')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="fixed">{t('Fixed')}</SelectItem>
                            <SelectItem value="percentage">{t('Percentage')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                <div>
                    <Label htmlFor="amount" required>{t('Amount')}</Label>
                    <Input
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.amount}
                        onChange={(e) => setData('amount', e.target.value)}
                        placeholder={data.type === 'percentage' ? t('Enter percentage') : t('Enter amount')}
                        required
                    />
                    <InputError message={errors.amount} />
                </div>

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

                <div>
                    <Label htmlFor="reason">{t('Reason')}</Label>
                    <Textarea
                        id="reason"
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        placeholder={t('Enter reason for loan')}
                        rows={3}
                    />
                    <InputError message={errors.reason} />
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