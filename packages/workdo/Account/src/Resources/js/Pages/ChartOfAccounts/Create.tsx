import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateChartOfAccountProps, CreateChartOfAccountFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateChartOfAccountProps) {
    const { accounttypes, parentaccounts } = usePage<any>().props;
    const [filteredParentAccounts, setFilteredParentAccounts] = useState(parentaccounts || []);
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm<CreateChartOfAccountFormData>({
        account_code: '',
        account_name: '',
        level: '1',
        normal_balance: 'debit',
        opening_balance: '',
        current_balance: '',
        is_active: false,
        description: '',
        account_type_id: '',
        parent_account_id: '',
    });

    const [isSubAccount, setIsSubAccount] = useState(false);

    const handleParentAccountChange = (value: string) => {
        setData('parent_account_id', value);
        // Set level to 2 if parent account is selected, otherwise 1
        if (value && value !== '0') {
            setData('level', '2');
        } else {
            setData('level', '1');
        }
    };

    const handleSubAccountChange = (checked: boolean) => {
        setIsSubAccount(checked);
        if (!checked) {
            setData('parent_account_id', '');
            setData('level', '1');
        } else {
            setData('level', '2');
        }
    };



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('account.chart-of-accounts.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Chart Of Account')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="account_type_id" required>{t('Account Type')}</Label>
                    <Select value={data.account_type_id?.toString() || ''} onValueChange={(value) => setData('account_type_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Account Type')} />
                        </SelectTrigger>
                        <SelectContent>
                            {accounttypes.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.account_type_id} />
                </div>

                <div>
                    <Label htmlFor="account_name">{t('Account Name')}</Label>
                    <Input
                        id="account_name"
                        type="text"
                        value={data.account_name}
                        onChange={(e) => setData('account_name', e.target.value)}
                        placeholder={t('Enter Account Name')}
                        required
                    />
                    <InputError message={errors.account_name} />
                </div>


                <div>
                    <Label htmlFor="account_code">{t('Account Code')}</Label>
                    <Input
                        id="account_code"
                        type="text"
                        value={data.account_code}
                        onChange={(e) => setData('account_code', e.target.value)}
                        placeholder={t('Enter Account Code')}
                        required
                    />
                    <InputError message={errors.account_code} />
                </div>

                <div>
                    <Label>{t('Normal Balance')}</Label>
                    <RadioGroup value={data.normal_balance || 'debit'} onValueChange={(value) => setData('normal_balance', value)} className="flex gap-6 mt-2">
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="debit" id="normal_balance_debit" />
                            <Label htmlFor="normal_balance_debit" className="cursor-pointer">{t('Debit')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="credit" id="normal_balance_credit" />
                            <Label htmlFor="normal_balance_credit" className="cursor-pointer">{t('Credit')}</Label>
                        </div>
                    </RadioGroup>
                    <InputError message={errors.normal_balance} />
                </div>

                <div>
                    <CurrencyInput
                        label={t('Opening Balance')}
                        value={data.opening_balance}
                        onChange={(value) => setData('opening_balance', value)}
                        error={errors.opening_balance}
                        required
                    />
                </div>

                <div>
                    <CurrencyInput
                        label={t('Current Balance')}
                        value={data.current_balance}
                        onChange={(value) => setData('current_balance', value)}
                        error={errors.current_balance}
                        required
                    />
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <Switch
                            id="is_active"
                            checked={data.is_active || false}
                            onCheckedChange={(checked) => setData('is_active', !!checked)}
                        />
                        <Label htmlFor="is_active" className="cursor-pointer">{t('Is Active')}</Label>
                        <InputError message={errors.is_active} />
                    </div>
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_sub_account"
                            checked={isSubAccount}
                            onCheckedChange={handleSubAccountChange}
                        />
                        <Label htmlFor="is_sub_account" className="cursor-pointer">{t('Create as sub account')}</Label>
                    </div>
                </div>

                {isSubAccount && (
                    <div>
                        <Label htmlFor="parent_account_id">{t('Parent Account')}</Label>
                        <Select value={data.parent_account_id?.toString() || ''} onValueChange={handleParentAccountChange}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Parent Account')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">{t('None')}</SelectItem>
                                {filteredParentAccounts.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.account_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.parent_account_id} />
                    </div>
                )}

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
