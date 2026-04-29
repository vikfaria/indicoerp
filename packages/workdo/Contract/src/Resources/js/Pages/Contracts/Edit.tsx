import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CurrencyInput } from '@/components/ui/currency-input';
import { DatePicker } from '@/components/ui/date-picker';
import { EditContractProps, EditContractFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useFormFields } from '@/hooks/useFormFields';

export default function EditContract({ contract, onSuccess }: EditContractProps) {
    const { users, contracttypes } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditContractFormData>({
        subject: contract.subject ?? '',
        user_id: contract.user_id?.toString() ?? '',
        value: contract.value?.toString() ?? '',
        type_id: contract.type_id?.toString() ?? '',
        start_date: contract.start_date || '',
        end_date: contract.end_date || '',
        description: contract.description ?? '',
        status: contract.status?.toString() ?? 'pending',
    });

    const subjectAI = useFormFields('aiField', data, setData, errors, 'edit', 'subject', 'Subject', 'contract', 'contract');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('contract.update', contract.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Contract')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4 mt-3">
                <div className="flex gap-2 items-end">
                    <div className="flex-1">
                        <Label htmlFor="subject">{t('Subject')}</Label>
                        <Input
                            id="subject"
                            type="text"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            placeholder={t('Enter Subject')}
                            required
                        />
                        <InputError message={errors.subject} />
                    </div>
                    {subjectAI.map(field => <div key={field.id}>{field.component}</div>)}
                </div>

                <div>
                    <CurrencyInput
                        label={t('Value')}
                        value={data.value}
                        onChange={(value) => setData('value', value)}
                        error={errors.value}
                        required
                    />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label required>{t('Start Date')}</Label>
                        <DatePicker
                            value={data.start_date}
                            onChange={(date) => setData('start_date', date)}
                            placeholder={t('Select Start Date')}
                            required
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div>
                        <Label required>{t('End Date')}</Label>
                        <DatePicker
                            value={data.end_date}
                            onChange={(date) => setData('end_date', date)}
                            placeholder={t('Select End Date')}
                            required
                        />
                        <InputError message={errors.end_date} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="status" required>{t('Status')}</Label>
                    <Select value={data.status?.toString() || 'pending'} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                            <SelectItem value="accepted">{t('Accepted')}</SelectItem>
                            <SelectItem value="declined">{t('Declined')}</SelectItem>
                            <SelectItem value="closed">{t('Closed')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div>
                    <Label htmlFor="type_id" required>{t('Contract Type')}</Label>
                    <Select value={data.type_id?.toString() || ''} onValueChange={(value) => setData('type_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Contract Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {contracttypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type_id} />
                </div>

                <div>
                    <Label htmlFor="user_id" required>{t('Users')}</Label>
                    <Select value={data.user_id?.toString() || ''} onValueChange={(value) => setData('user_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Users')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {users?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.user_id} />
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