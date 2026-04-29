import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { useFormFields } from '@/hooks/useFormFields';

interface Category {
    id: number;
    category_name: string;
}

interface BankAccount {
    id: number;
    account_name: string;
}

interface ChartOfAccount {
    id: number;
    account_code: string;
    account_name: string;
}

interface Revenue {
    id: number;
    revenue_date: string;
    category_id: number;
    bank_account_id: number;
    chart_of_account_id?: number;
    amount: string;
    description: string;
    reference_number: string;
    status: 'draft' | 'approved' | 'posted';
}

interface EditRevenueProps {
    revenue: Revenue;
    categories: Category[];
    bankAccounts: BankAccount[];
    chartOfAccounts: ChartOfAccount[];
    onSuccess: () => void;
}

interface EditRevenueFormData {
    revenue_date: string;
    category_id: string;
    bank_account_id: string;
    chart_of_account_id: string;
    amount: string;
    description: string;
    reference_number: string;
}

export default function Edit({ revenue, categories, bankAccounts, chartOfAccounts, onSuccess }: EditRevenueProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<EditRevenueFormData>({
        revenue_date: revenue.revenue_date,
        category_id: revenue.category_id.toString(),
        bank_account_id: revenue.bank_account_id.toString(),
        chart_of_account_id: revenue.chart_of_account_id?.toString() || '',
        amount: revenue.amount,
        description: revenue.description || '',
        reference_number: revenue.reference_number || '',
    });

    // AI hooks for description field
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'account', 'revenue');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('account.revenues.update', revenue.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Revenue')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <Label htmlFor="revenue_date" required>{t('Revenue Date')}</Label>
                                <DatePicker
                                    id="revenue_date"
                                    value={data.revenue_date}
                                    onChange={(value) => {
                                        const formattedDate = value instanceof Date ? value.toISOString().split('T')[0] : value;
                                        setData('revenue_date', formattedDate);
                                    }}
                                    placeholder={t('Select revenue date')}
                                    required
                                />
                                <InputError message={errors.revenue_date} />
                            </div>

                            <div>
                                <Label htmlFor="category_id" required>{t('Category')}</Label>
                                <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select Category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.category_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div>
                                <Label htmlFor="bank_account_id" required>{t('Bank Account')}</Label>
                                <Select value={data.bank_account_id} onValueChange={(value) => setData('bank_account_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select Bank Account')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankAccounts.map((account) => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.bank_account_id} />
                            </div>

                            <div>
                                <Label htmlFor="chart_of_account_id" required>{t('Chart of Account')}</Label>
                                <Select value={data.chart_of_account_id} onValueChange={(value) => setData('chart_of_account_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select Chart of Account')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {chartOfAccounts?.map((account) => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.account_code} - {account.account_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.chart_of_account_id} />
                            </div>

                            <div>
                                <CurrencyInput
                                    label={t('Amount')}
                                    value={data.amount}
                                    onChange={(value) => setData('amount', value)}
                                    error={errors.amount}
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="reference_number">{t('Reference Number')}</Label>
                                <Input
                                    id="reference_number"
                                    type="text"
                                    value={data.reference_number}
                                    onChange={(e) => setData('reference_number', e.target.value)}
                                    placeholder={t('Enter Reference Number')}
                                />
                                <InputError message={errors.reference_number} />
                            </div>


                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="description">{t('Description')}</Label>
                                <div className="flex gap-2">
                                    {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                                </div>
                            </div>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder={t('Enter Description')}
                                rows={3}
                            />
                            <InputError message={errors.description} />
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
