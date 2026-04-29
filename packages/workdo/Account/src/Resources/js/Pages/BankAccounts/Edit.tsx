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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EditBankAccountProps, EditBankAccountFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useFormFields } from '@/hooks/useFormFields';

export default function EditBankAccount({ bankaccount, onSuccess }: EditBankAccountProps) {
    const { chartofaccounts } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditBankAccountFormData>({
        account_number: bankaccount.account_number ?? '',
        account_name: bankaccount.account_name ?? '',
        bank_name: bankaccount.bank_name ?? '',
        branch_name: bankaccount.branch_name ?? '',
        account_type: bankaccount.account_type?.toString() || '0',
        //        payment_gateway: bankaccount.payment_gateway ?? '',
        opening_balance: bankaccount.opening_balance ?? '',
        current_balance: bankaccount.current_balance ?? '',
        iban: bankaccount.iban ?? '',
        swift_code: bankaccount.swift_code ?? '',
        routing_number: bankaccount.routing_number ?? '',
        is_active: bankaccount.is_active ?? false,
        gl_account_id: bankaccount.gl_account_id?.toString() || '',
    });

    // const paymentGatewayFields = useFormFields('paymentGateway');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('account.bank-accounts.update', bankaccount.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Bank Account')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="account_number">{t('Account Number')}</Label>
                    <Input
                        id="account_number"
                        type="text"
                        value={data.account_number}
                        onChange={(e) => setData('account_number', e.target.value)}
                        placeholder={t('Enter Account Number')}
                        required
                    />
                    <InputError message={errors.account_number} />
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
                    <Label htmlFor="bank_name">{t('Bank Name')}</Label>
                    <Input
                        id="bank_name"
                        type="text"
                        value={data.bank_name}
                        onChange={(e) => setData('bank_name', e.target.value)}
                        placeholder={t('Enter Bank Name')}
                        required
                    />
                    <InputError message={errors.bank_name} />
                </div>

                <div>
                    <Label htmlFor="branch_name">{t('Branch Name')}</Label>
                    <Input
                        id="branch_name"
                        type="text"
                        value={data.branch_name}
                        onChange={(e) => setData('branch_name', e.target.value)}
                        placeholder={t('Enter Branch Name')}

                    />
                    <InputError message={errors.branch_name} />
                </div>

                <div>
                    <Label>{t('Account Type')}</Label>
                    <RadioGroup value={data.account_type?.toString() || '0'} onValueChange={(value) => setData('account_type', value)} className="flex gap-6 mt-2">
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="0" id="account_type_0" />
                            <Label htmlFor="account_type_0" className="cursor-pointer">{t('checking')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="1" id="account_type_1" />
                            <Label htmlFor="account_type_1" className="cursor-pointer">{t('savings')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="2" id="account_type_2" />
                            <Label htmlFor="account_type_2" className="cursor-pointer">{t('credit')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="3" id="account_type_3" />
                            <Label htmlFor="account_type_3" className="cursor-pointer">{t('loan')}</Label>
                        </div>
                    </RadioGroup>
                    <InputError message={errors.account_type} />
                </div>

                <div>
                    <Label htmlFor="gl_account_id" required>{t('Gl Account')}</Label>
                    <Select value={data.gl_account_id?.toString() || ''} onValueChange={(value) => setData('gl_account_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Gl Account')} />
                        </SelectTrigger>
                        <SelectContent>
                            {chartofaccounts.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.account_code} - {item.account_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.gl_account_id} />
                </div>

                {/* <div>
                    <Label htmlFor="payment_gateway">{t('Payment Gateway')}</Label>
                    <Select value={data.payment_gateway} onValueChange={(value) => setData('payment_gateway', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Payment Gateway')} />
                        </SelectTrigger>
                        <SelectContent>
                            {paymentGatewayFields.map((field) => (
                                <div key={field.id}>{field.component}</div>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.payment_gateway} />
                </div> */}

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

                <div>
                    <Label htmlFor="iban">{t('Iban')}</Label>
                    <Input
                        id="iban"
                        type="text"
                        value={data.iban}
                        onChange={(e) => setData('iban', e.target.value)}
                        placeholder={t('Enter Iban')}

                    />
                    <InputError message={errors.iban} />
                </div>

                <div>
                    <Label htmlFor="swift_code">{t('Swift Code')}</Label>
                    <Input
                        id="swift_code"
                        type="text"
                        value={data.swift_code}
                        onChange={(e) => setData('swift_code', e.target.value)}
                        placeholder={t('Enter Swift Code')}

                    />
                    <InputError message={errors.swift_code} />
                </div>

                <div>
                    <Label htmlFor="routing_number">{t('Routing Number')}</Label>
                    <Input
                        id="routing_number"
                        type="text"
                        value={data.routing_number}
                        onChange={(e) => setData('routing_number', e.target.value)}
                        placeholder={t('Enter Routing Number')}

                    />
                    <InputError message={errors.routing_number} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_active"
                        checked={data.is_active || false}
                        onCheckedChange={(checked) => setData('is_active', !!checked)}
                    />
                    <Label htmlFor="is_active" className="cursor-pointer">{t('Is Active')}</Label>
                    <InputError message={errors.is_active} />
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
