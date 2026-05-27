import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Contract } from './types';

interface DuplicateProps {
    contract: Contract;
    onSuccess: () => void;
}

export default function Duplicate({ contract, onSuccess }: DuplicateProps) {
    const { t } = useTranslation();
    const { users, contracttypes } = usePage<any>().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        subject: contract.subject + ' (Copy)',
        user_id: contract.user_id?.toString() || '',
        value: contract.value || '',
        type_id: contract.type_id?.toString() || '',
        start_date: '',
        end_date: '',
        description: contract.description || '',
        status: 'pending',
        is_labour_contract: !!contract.is_labour_contract,
        legal_contract_type: contract.legal_contract_type || '',
        fixed_term_justification: contract.fixed_term_justification || '',
        probation_category: contract.probation_category || '',
        legal_notes: contract.legal_notes || ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('contract.duplicate', contract.id), {
            onSuccess: () => {
                reset();
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Duplicate Contract')}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6 my-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="subject">{t('Subject')}</Label>
                        <Input
                            id="subject"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            placeholder={t('Enter subject')}
                            className={errors.subject ? 'border-red-500' : ''}
                            required
                        />
                        {errors.subject && <p className="text-sm text-red-500">{errors.subject}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="value">{t('Contract Value')}</Label>
                            <Input
                                id="value"
                                type="number"
                                step="0.01"
                                value={data.value}
                                onChange={(e) => setData('value', e.target.value)}
                                placeholder={t('Enter contract value')}
                                className={errors.value ? 'border-red-500' : ''}
                                required
                            />
                            {errors.value && <p className="text-sm text-red-500">{errors.value}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="type_id" required>{t('Contract Type')}</Label>
                            <Select value={data.type_id} onValueChange={(value) => setData('type_id', value)}>
                                <SelectTrigger className={errors.type_id ? 'border-red-500' : ''}>
                                    <SelectValue placeholder={t('Select Contract Type')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {contracttypes?.map((type: any) => (
                                        <SelectItem key={type.id} value={type.id.toString()}>
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.type_id && <p className="text-sm text-red-500">{errors.type_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label required>{t('Start Date')}</Label>
                            <DatePicker
                                value={data.start_date}
                                onChange={(date) => setData('start_date', date)}
                                placeholder={t('Select Start Date')}
                                required
                            />
                            {errors.start_date && <p className="text-sm text-red-500">{errors.start_date}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label required>{t('End Date')}</Label>
                            <DatePicker
                                value={data.end_date}
                                onChange={(date) => setData('end_date', date)}
                                placeholder={t('Select End Date')}
                                required
                            />
                            {errors.end_date && <p className="text-sm text-red-500">{errors.end_date}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="status" required>{t('Status')}</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                                    <SelectValue placeholder={t('Select Status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">{t('Pending')}</SelectItem>
                                    <SelectItem value="accepted">{t('Accepted')}</SelectItem>
                                    <SelectItem value="declined">{t('Declined')}</SelectItem>
                                    <SelectItem value="closed">{t('Closed')}</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.status && <p className="text-sm text-red-500">{errors.status}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="user_id" required>{t('Assign User')}</Label>
                            <Select value={data.user_id} onValueChange={(value) => setData('user_id', value)}>
                                <SelectTrigger className={errors.user_id ? 'border-red-500' : ''}>
                                    <SelectValue placeholder={t('Select User')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {users?.map((user: any) => (
                                        <SelectItem key={user.id} value={user.id.toString()}>
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.user_id && <p className="text-sm text-red-500">{errors.user_id}</p>}
                        </div>
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
                        {errors.is_labour_contract && <p className="text-sm text-red-500">{errors.is_labour_contract}</p>}

                        {data.is_labour_contract && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label required>{t('Legal Contract Type')}</Label>
                                    <Select value={data.legal_contract_type || ''} onValueChange={(value) => setData('legal_contract_type', value)}>
                                        <SelectTrigger className={errors.legal_contract_type ? 'border-red-500' : ''}>
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
                                    {errors.legal_contract_type && <p className="text-sm text-red-500">{errors.legal_contract_type}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label>{t('Probation Category')}</Label>
                                    <Select value={data.probation_category || ''} onValueChange={(value) => setData('probation_category', value)}>
                                        <SelectTrigger className={errors.probation_category ? 'border-red-500' : ''}>
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
                                    {errors.probation_category && <p className="text-sm text-red-500">{errors.probation_category}</p>}
                                </div>

                                <div className="md:col-span-2 space-y-2">
                                    <Label>{t('Fixed-Term Justification')}</Label>
                                    <Textarea
                                        value={data.fixed_term_justification}
                                        onChange={(e) => setData('fixed_term_justification', e.target.value)}
                                        placeholder={t('Provide legal/economic/technical reason for fixed-term contract')}
                                        className={errors.fixed_term_justification ? 'border-red-500' : ''}
                                    />
                                    {errors.fixed_term_justification && <p className="text-sm text-red-500">{errors.fixed_term_justification}</p>}
                                </div>

                                <div className="md:col-span-2 space-y-2">
                                    <Label>{t('Legal Notes')}</Label>
                                    <Textarea
                                        value={data.legal_notes}
                                        onChange={(e) => setData('legal_notes', e.target.value)}
                                        placeholder={t('Additional legal notes')}
                                        className={errors.legal_notes ? 'border-red-500' : ''}
                                    />
                                    {errors.legal_notes && <p className="text-sm text-red-500">{errors.legal_notes}</p>}
                                </div>
                            </div>
                        )}
                    </div>



                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={onSuccess}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? t('Duplicating...') : t('Duplicate')}
                        </Button>
                    </div>
                </form>
            </div>
        </DialogContent>
    );
}
