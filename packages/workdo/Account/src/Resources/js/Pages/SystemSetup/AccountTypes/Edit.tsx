import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { usePage } from '@inertiajs/react';

interface AccountType {
    id: number;
    category_id: number;
    name: string;
    code: string;
    normal_balance: string;
    description?: string;
    is_active: boolean;
    category?: { name: string };
}

interface EditAccountTypeFormData {
    category_id: string;
    name: string;
    code: string;
    normal_balance: string;
    description: string;
    is_active: boolean;
}

interface EditAccountTypeProps {
    accounttype: AccountType;
    onSuccess: () => void;
}

export default function EditAccountType({ accounttype, onSuccess }: EditAccountTypeProps) {
    const { accountcategories } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditAccountTypeFormData>({
        category_id: accounttype.category_id?.toString() || '',
        name: accounttype.name ?? '',
        code: accounttype.code ?? '',
        normal_balance: accounttype.normal_balance === 'credit' ? '1' : '0',
        description: accounttype.description ?? '',
        is_active: accounttype.is_active ?? false,
    });

    const generateCode = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .join('');
    };

    const handleNameChange = (value: string) => {
        const capitalizedName = value.charAt(0).toUpperCase() + value.slice(1);
        setData('name', capitalizedName);
        setData('code', generateCode(capitalizedName));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('account.account-types.update', accounttype.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Account Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="category_id" required>{t('Category')}</Label>
                    <Select value={data.category_id?.toString() || ''} onValueChange={(value) => setData('category_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Category')} />
                        </SelectTrigger>
                        <SelectContent>
                            {accountcategories?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category_id} />
                </div>
                <div>
                    <Label htmlFor="name">{t('Name')}</Label>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => handleNameChange(e.target.value)}
                        placeholder={t('Enter Name')}
                        disabled={accounttype.is_system_type == 1}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label htmlFor="code">{t('Code')}</Label>
                    <Input
                        id="code"
                        type="text"
                        value={data.code}
                        placeholder={t('Auto-generated from name')}
                        disabled
                        required
                    />
                    <InputError message={errors.code} />
                </div>

                <div>
                    <Label>{t('Normal Balance')}</Label>
                    <RadioGroup value={data.normal_balance?.toString() || '0'} onValueChange={(value) => setData('normal_balance', value)} className="flex gap-6 mt-2">
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="0" id="normal_balance_0" />
                            <Label htmlFor="normal_balance_0" className="cursor-pointer">{t('debit')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="1" id="normal_balance_1" />
                            <Label htmlFor="normal_balance_1" className="cursor-pointer">{t('credit')}</Label>
                        </div>
                    </RadioGroup>
                    <InputError message={errors.normal_balance} />
                </div>

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
