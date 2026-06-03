import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateBankAccountProps, CreateBankAccountFormData } from './types';
import { usePage } from '@inertiajs/react';
import { ACCOUNT_TYPE_OPTIONS } from './account-type';

export default function Create({ onSuccess }: CreateBankAccountProps) {
    const { chartofaccounts } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateBankAccountFormData>({
        account_number: '',
        account_name: '',
        bank_name: '',
        branch_name: '',
        account_type: 'current',
        //        payment_gateway: '',
        opening_balance: '',
        current_balance: '',
        iban: '',
        swift_code: '',
        routing_number: '',
        is_active: false,
        is_electronic_money_account: false,
        electronic_money_entity: '',
        electronic_money_level: '',
        electronic_money_daily_limit_mzn: '',
        electronic_money_monthly_limit_mzn: '',
        electronic_money_limit_exempt_for_enterprise: false,
        electronic_money_account_purpose: '',
        gl_account_id: '',
    });

    // const paymentGatewayFields = useFormFields('paymentGateway');

    const isElectronicMoney = !!data.is_electronic_money_account;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('account.bank-accounts.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Bank Account')}</DialogTitle>
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
                    <Label required>{t('Account Type')}</Label>
                    <Select value={data.account_type || 'current'} onValueChange={(value) => setData('account_type', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Account Type')} />
                        </SelectTrigger>
                        <SelectContent>
                            {ACCOUNT_TYPE_OPTIONS.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.account_type} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_electronic_money_account"
                        checked={isElectronicMoney}
                        onCheckedChange={(checked) => {
                            const enabled = !!checked;
                            setData('is_electronic_money_account', enabled);

                            if (!enabled) {
                                setData('electronic_money_entity', '');
                                setData('electronic_money_level', '');
                                setData('electronic_money_daily_limit_mzn', '');
                                setData('electronic_money_monthly_limit_mzn', '');
                                setData('electronic_money_limit_exempt_for_enterprise', false);
                                setData('electronic_money_account_purpose', '');
                            }
                        }}
                    />
                    <Label htmlFor="is_electronic_money_account" className="cursor-pointer">{t('Electronic Money Account')}</Label>
                    <InputError message={errors.is_electronic_money_account} />
                </div>

                {isElectronicMoney && (
                    <>
                        <div>
                            <Label htmlFor="electronic_money_entity" required>{t('Electronic Money Entity')}</Label>
                            <Input
                                id="electronic_money_entity"
                                type="text"
                                value={data.electronic_money_entity}
                                onChange={(e) => setData('electronic_money_entity', e.target.value)}
                                placeholder={t('Enter provider or institution name')}
                            />
                            <InputError message={errors.electronic_money_entity} />
                        </div>

                        <div>
                            <Label htmlFor="electronic_money_level" required>{t('Electronic Money Account Level')}</Label>
                            <Select value={data.electronic_money_level || ''} onValueChange={(value) => setData('electronic_money_level', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select account level')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="I">{t('Level I')}</SelectItem>
                                    <SelectItem value="II">{t('Level II')}</SelectItem>
                                    <SelectItem value="III">{t('Level III')}</SelectItem>
                                    <SelectItem value="IV">{t('Level IV')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.electronic_money_level} />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="electronic_money_limit_exempt_for_enterprise"
                                checked={!!data.electronic_money_limit_exempt_for_enterprise}
                                onCheckedChange={(checked) => {
                                    const exempt = !!checked;
                                    setData('electronic_money_limit_exempt_for_enterprise', exempt);

                                    if (exempt) {
                                        setData('electronic_money_daily_limit_mzn', '');
                                        setData('electronic_money_monthly_limit_mzn', '');
                                    }
                                }}
                            />
                            <Label htmlFor="electronic_money_limit_exempt_for_enterprise" className="cursor-pointer">{t('Limit Exempt for Medium/Large Enterprise')}</Label>
                            <InputError message={errors.electronic_money_limit_exempt_for_enterprise} />
                        </div>

                        {!data.electronic_money_limit_exempt_for_enterprise && (
                            <>
                                <div>
                                    <CurrencyInput
                                        label={t('Electronic Money Daily Limit (MZN)')}
                                        value={data.electronic_money_daily_limit_mzn}
                                        onChange={(value) => setData('electronic_money_daily_limit_mzn', value)}
                                        error={errors.electronic_money_daily_limit_mzn}
                                        required
                                    />
                                </div>

                                <div>
                                    <CurrencyInput
                                        label={t('Electronic Money Monthly Limit (MZN)')}
                                        value={data.electronic_money_monthly_limit_mzn}
                                        onChange={(value) => setData('electronic_money_monthly_limit_mzn', value)}
                                        error={errors.electronic_money_monthly_limit_mzn}
                                        required
                                    />
                                </div>
                            </>
                        )}

                        <div>
                            <Label htmlFor="electronic_money_account_purpose">{t('Electronic Money Account Purpose')}</Label>
                            <Input
                                id="electronic_money_account_purpose"
                                type="text"
                                value={data.electronic_money_account_purpose}
                                onChange={(e) => setData('electronic_money_account_purpose', e.target.value)}
                                placeholder={t('Describe the account purpose')}
                            />
                            <InputError message={errors.electronic_money_account_purpose} />
                        </div>
                    </>
                )}

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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
