import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
        is_labour_contract: !!contract.is_labour_contract,
        legal_contract_type: contract.legal_contract_type ?? '',
        fixed_term_justification: contract.fixed_term_justification ?? '',
        probation_category: contract.probation_category ?? '',
        legal_notes: contract.legal_notes ?? '',
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

                <div className="space-y-3 rounded-lg border p-3">
                    <label className="flex items-center gap-2 text-sm font-medium">
                        <input
                            type="checkbox"
                            checked={data.is_labour_contract}
                            onChange={(e) => setData('is_labour_contract', e.target.checked)}
                        />
                        {t('Labour Contract (Mozambique)')}
                    </label>
                    <InputError message={errors.is_labour_contract} />

                    {data.is_labour_contract && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <Label required>{t('Legal Contract Type')}</Label>
                                <Select value={data.legal_contract_type || ''} onValueChange={(value) => setData('legal_contract_type', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select legal type')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="indefinite">{t('Indefinite')}</SelectItem>
                                        <SelectItem value="fixed_term">{t('Fixed Term')}</SelectItem>
                                        <SelectItem value="uncertain_term">{t('Uncertain Term')}</SelectItem>
                                        <SelectItem value="foreign">{t('Foreign Worker')}</SelectItem>
                                        <SelectItem value="short_term">{t('Short Term')}</SelectItem>
                                        <SelectItem value="project">{t('Project')}</SelectItem>
                                        <SelectItem value="special_regime">{t('Special Regime')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.legal_contract_type} />
                            </div>

                            <div>
                                <Label>{t('Probation Category')}</Label>
                                <Select value={data.probation_category || ''} onValueChange={(value) => setData('probation_category', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select probation category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="base_indefinite">{t('Base (Indefinite)')}</SelectItem>
                                        <SelectItem value="general">{t('General')}</SelectItem>
                                        <SelectItem value="technician_mid">{t('Mid Technician')}</SelectItem>
                                        <SelectItem value="technician_high">{t('High Technician')}</SelectItem>
                                        <SelectItem value="leadership">{t('Leadership')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.probation_category} />
                            </div>

                            <div className="md:col-span-2">
                                <Label>{t('Fixed-Term Justification')}</Label>
                                <Textarea
                                    value={data.fixed_term_justification}
                                    onChange={(e) => setData('fixed_term_justification', e.target.value)}
                                    placeholder={t('Provide legal/economic/technical reason for fixed-term contract')}
                                />
                                <InputError message={errors.fixed_term_justification} />
                            </div>

                            <div className="md:col-span-2">
                                <Label>{t('Legal Notes')}</Label>
                                <Textarea
                                    value={data.legal_notes}
                                    onChange={(e) => setData('legal_notes', e.target.value)}
                                    placeholder={t('Additional legal notes')}
                                />
                                <InputError message={errors.legal_notes} />
                            </div>
                        </div>
                    )}
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
