import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CurrencyInput } from '@/components/ui/currency-input';

interface EditProps {
    budget: any;
    onSuccess: () => void;
}

export default function Edit({ budget, onSuccess }: EditProps) {
    const { budgetPeriods } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        budget_name: budget.budget_name ?? '',
        period_id: budget.period_id?.toString() ?? '',
        budget_type: budget.budget_type ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('budget-planner.budgets.update', budget.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Budget')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="budget_name">{t('Budget Name')}</Label>
                    <Input
                        id="budget_name"
                        type="text"
                        value={data.budget_name}
                        onChange={(e) => setData('budget_name', e.target.value)}
                        placeholder={t('Enter Budget Name')}
                        required
                    />
                    <InputError message={errors.budget_name} />
                </div>

                <div>
                    <Label required>{t('Budget Period')}</Label>
                    <Select value={data.period_id} onValueChange={(value) => setData('period_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Budget Period')} />
                        </SelectTrigger>
                        <SelectContent>
                            {budgetPeriods?.map((period: any) => (
                                <SelectItem key={period.id} value={period.id.toString()}>
                                    {period.period_name} ({period.financial_year})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.period_id} />
                </div>

                <div>
                    <Label required>{t('Budget Type')}</Label>
                    <Select value={data.budget_type} onValueChange={(value) => setData('budget_type', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Budget Type')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="operational">{t('Operational')}</SelectItem>
                            <SelectItem value="capital">{t('Capital')}</SelectItem>
                            <SelectItem value="cash_flow">{t('Cash Flow')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.budget_type} />
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
