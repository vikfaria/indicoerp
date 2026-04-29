import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CurrencyInput } from '@/components/ui/currency-input';

interface CreateProps {
    onSuccess: () => void;
}

export default function Create({ onSuccess }: CreateProps) {
    const { budgets, accounts } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        budget_id: '',
        account_id: '',
        allocated_amount: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('budget-planner.budget-allocations.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Budget Allocation')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label required>{t('Budget')}</Label>
                    <Select value={data.budget_id} onValueChange={(value) => setData('budget_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Budget')} />
                        </SelectTrigger>
                        <SelectContent>
                            {budgets?.map((budget: any) => (
                                <SelectItem key={budget.id} value={budget.id.toString()}>
                                    {budget.budget_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.budget_id} />
                </div>

                <div>
                    <Label required>{t('Account')}</Label>
                    <Select value={data.account_id} onValueChange={(value) => setData('account_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {accounts?.map((account: any) => (
                                <SelectItem key={account.id} value={account.id.toString()}>
                                    {account.account_code} - {account.account_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.account_id} />
                </div>

                <div>
                    <CurrencyInput
                        label={t('Allocated Amount')}
                        value={data.allocated_amount}
                        onChange={(value) => setData('allocated_amount', value)}
                        error={errors.allocated_amount}
                        required
                    />
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
