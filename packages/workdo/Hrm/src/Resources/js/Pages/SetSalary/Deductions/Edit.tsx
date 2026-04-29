import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';

interface Deduction {
    id: number;
    deduction_type_id: number;
    type: string;
    amount: number;
}

interface EditDeductionProps {
    deduction: Deduction;
    deductionTypes: Array<{ id: number; name: string }>;
    onSuccess: () => void;
}

export default function Edit({ deduction, deductionTypes, onSuccess }: EditDeductionProps) {
    const { t } = useTranslation();
    
    const { data, setData, put, processing, errors } = useForm({
        deduction_type_id: deduction.deduction_type_id.toString(),
        type: deduction.type,
        amount: deduction.amount.toString(),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.deductions.update', deduction.id), {
            only: ['errors', 'flash', 'deductions'],
            onSuccess: () => {
                onSuccess();
            },
            onError: () => {
                // Keep modal open on validation errors
            }
        });
    };

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Edit Deduction')}</DialogTitle>
            </DialogHeader>
            
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="deduction_type_id" required>{t('Deduction Type')}</Label>
                    <Select value={data.deduction_type_id} onValueChange={(value) => setData('deduction_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select deduction type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {deductionTypes.map((type) => (
                                <SelectItem key={type.id} value={type.id.toString()}>
                                    {type.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.deduction_type_id} />
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