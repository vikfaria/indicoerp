import { useState, useEffect } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { useTranslation } from 'react-i18next';
import axios from 'axios';

export const bankAccountField = (data: any, setData: any, errors: any, mode: string = 'create') => {
    const { t } = useTranslation();
    const [bankAccounts, setBankAccounts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const fieldId = mode === 'edit' ? 'edit_bank_account_id' : 'bank_account_id';

    useEffect(() => {
        const fetchBankAccounts = async () => {
            try {
                const response = await axios.get(route('account.bank-accounts.api.list'));
                setBankAccounts(response.data);
            } catch (error) {
                console.error('Error fetching bank accounts:', error);
            } finally {
                setLoading(false);
            }
        };
        fetchBankAccounts();
    }, []);

    return [{
        id: 'bank-account-field',
        order: 10,
        component: (
            <div>
                <Label htmlFor={fieldId} required>{t('Bank Account')}</Label>
                <Select
                    value={data.bank_account_id?.toString() || ''}
                    onValueChange={(value) => setData('bank_account_id', value)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder={loading ? t('Loading...') : t('Select Bank Account')} />
                    </SelectTrigger>
                    <SelectContent>
                        {bankAccounts?.map((account) => (
                            <SelectItem key={account.id} value={account.id.toString()}>
                                {account.account_name} ({account.account_number})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.bank_account_id} />
            </div>
        )
    }];
};
