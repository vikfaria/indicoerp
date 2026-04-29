import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { InputError } from '@/components/ui/input-error';
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { useFormFields } from '@/hooks/useFormFields';
import { BankAccount } from './types';
import { formatCurrency } from '@/utils/helpers';

interface CreateProps {
    onSuccess: () => void;
}

export default function Create({ onSuccess }: CreateProps) {
    const { t } = useTranslation();
    const { bankaccounts } = usePage().props as { bankaccounts: BankAccount[] };

    const { data, setData, post, processing, errors, reset } = useForm({
        transfer_date: new Date().toISOString().split('T')[0],
        from_account_id: '',
        to_account_id: '',
        transfer_amount: '',
        transfer_charges: '0',
        reference_number: '',
        description: ''
    });

    // AI hooks for description field
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'account', 'bank_transfer');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('account.bank-transfers.store'), {
            onSuccess: () => {
                reset();
                onSuccess();
            }
        });
    };

    const availableToAccounts = bankaccounts.filter(account =>
        account.id.toString() !== data.from_account_id
    );

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Create Bank Transfer')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="transfer_date">{t('Transfer Date')}</Label>
                    <DatePicker
                        value={data.transfer_date}
                        onChange={(value) => setData('transfer_date', value)}
                        required
                    />
                    <InputError message={errors.transfer_date} />
                </div>

                <div>
                    <Label htmlFor="from_account_id" required>{t('From Account')}</Label>
                    <Select
                        value={data.from_account_id}
                        onValueChange={(value) => {
                            setData('from_account_id', value);
                            if (value === data.to_account_id) {
                                setData('to_account_id', '');
                            }
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select source account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {bankaccounts.map(account => (
                                <SelectItem key={account.id} value={account.id.toString()}>
                                    {account.account_name} ({formatCurrency(account.current_balance)})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.from_account_id} />
                </div>

                <div>
                    <Label htmlFor="to_account_id" required>{t('To Account')}</Label>
                    <Select
                        value={data.to_account_id}
                        onValueChange={(value) => setData('to_account_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select destination account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableToAccounts.map(account => (
                                <SelectItem key={account.id} value={account.id.toString()}>
                                    {account.account_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_account_id} />
                </div>

                <div>
                    <Label htmlFor="transfer_amount" required>{t('Transfer Amount')}</Label>
                    <CurrencyInput
                        value={data.transfer_amount}
                        onChange={(value) => setData('transfer_amount', value)}
                        required
                    />
                    <InputError message={errors.transfer_amount} />
                </div>

                <div>
                    <Label htmlFor="transfer_charges">{t('Transfer Charges')}</Label>
                    <CurrencyInput
                        value={data.transfer_charges}
                        onChange={(value) => setData('transfer_charges', value)}
                    />
                    <InputError message={errors.transfer_charges} />
                </div>

                <div>
                    <Label htmlFor="reference_number">{t('Reference Number')}</Label>
                    <Input
                        id="reference_number"
                        value={data.reference_number}
                        onChange={(e) => setData('reference_number', e.target.value)}
                        placeholder={t('Optional reference number')}
                    />
                    <InputError message={errors.reference_number} />
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
                        placeholder={t('Enter transfer description')}
                        required
                    />
                    <InputError message={errors.description} />
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
