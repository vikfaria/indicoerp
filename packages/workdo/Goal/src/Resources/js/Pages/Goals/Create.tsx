import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage, router } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { CurrencyInput } from '@/components/ui/currency-input';
import { CreateGoalFormData, CreateGoalProps } from './types';

export default function Create({ onSuccess }: CreateGoalProps) {
    const { categories, chartOfAccounts, auth } = usePage<any>().props;
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm<CreateGoalFormData>({
        goal_name: '',
        goal_description: '',
        category_id: 0,
        goal_type: 'savings',
        target_amount: 0,
        current_amount: 0,
        start_date: '',
        target_date: '',
        priority: 'medium',
        status: 'draft',
        account_id: chartOfAccounts?.length > 0 ? chartOfAccounts[0].id : undefined,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('goal.goals.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Create Goal')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="goal_name">{t('Goal Name')}</Label>
                        <Input
                            id="goal_name"
                            type="text"
                            value={data.goal_name}
                            onChange={(e) => setData('goal_name', e.target.value)}
                            placeholder={t('Enter Goal Name')}
                            required
                        />
                        <InputError message={errors.goal_name} />
                    </div>

                    <div>
                        <Label htmlFor="category_id">{t('Category')}</Label>
                        <Select value={data.category_id ? data.category_id.toString() : ''} onValueChange={(value) => setData('category_id', parseInt(value))}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Category')} />
                            </SelectTrigger>
                            <SelectContent>
                                {categories?.map((category: any) => (
                                    <SelectItem key={category.id} value={category.id.toString()}>
                                        {category.category_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {categories?.length === 0 && auth.user?.permissions?.includes('manage-categories') && (
                            <p className="text-xs text-gray-500 mb-1">
                                {t('Create category here.')} <button onClick={() => router.get(route('goal.categories.index'))} className="text-blue-600 hover:underline">{t('Create category')}</button>
                            </p>
                        )}
                        <InputError message={errors.category_id} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="goal_type">{t('Goal Type')}</Label>
                        <Select value={data.goal_type} onValueChange={(value: any) => setData('goal_type', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="savings">{t('Savings')}</SelectItem>
                                <SelectItem value="debt_reduction">{t('Debt Reduction')}</SelectItem>
                                <SelectItem value="expense_reduction">{t('Expense Reduction')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.goal_type} />
                    </div>

                    <div>
                        <Label htmlFor="priority">{t('Priority')}</Label>
                        <Select value={data.priority} onValueChange={(value: any) => setData('priority', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="low">{t('Low')}</SelectItem>
                                <SelectItem value="medium">{t('Medium')}</SelectItem>
                                <SelectItem value="high">{t('High')}</SelectItem>
                                <SelectItem value="critical">{t('Critical')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>
                </div>

                <div>
                    <CurrencyInput
                        label={t('Target Amount')}
                        value={data.target_amount.toString()}
                        onChange={(value) => setData('target_amount', parseFloat(value) || 0)}
                        error={errors.target_amount}
                        required
                    />
                </div>


                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label required>{t('Start Date')}</Label>
                        <DatePicker
                            value={data.start_date}
                            onChange={(value) => setData('start_date', value)}
                            placeholder={t('Select start date')}
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div>
                        <Label required>{t('Target Date')}</Label>
                        <DatePicker
                            value={data.target_date}
                            onChange={(value) => setData('target_date', value)}
                            placeholder={t('Select target date')}
                        />
                        <InputError message={errors.target_date} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="account_id">{t('Chart of Account')}</Label>
                    <Select value={data.account_id ? data.account_id.toString() : ''} onValueChange={(value) => setData('account_id', parseInt(value))}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {chartOfAccounts?.filter((account: any) => {
                                if (data.goal_type === 'savings' || data.goal_type === 'debt_reduction') {
                                    return account.normal_balance === 'credit';
                                } else if (data.goal_type === 'expense_reduction') {
                                    return account.normal_balance === 'debit';
                                }
                                return true;
                            }).map((account: any) => (
                                <SelectItem key={account.id} value={account.id.toString()}>
                                    {account.account_code} - {account.account_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.account_id} />
                </div>

                <div>
                    <Label htmlFor="goal_description">{t('Description')}</Label>
                    <Textarea
                        id="goal_description"
                        value={data.goal_description}
                        onChange={(e) => setData('goal_description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                    />
                    <InputError message={errors.goal_description} />
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
