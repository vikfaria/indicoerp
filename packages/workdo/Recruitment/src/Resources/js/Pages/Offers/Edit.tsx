import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Textarea } from '@/components/ui/textarea';
import { DatePicker } from '@/components/ui/date-picker';
import { EditOfferProps, EditOfferFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function EditOffer({ offer, onSuccess }: EditOfferProps) {
    const { candidates, users, departments } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditOfferFormData>({
        candidate_id: offer.candidate_id?.toString() || '',
        position: offer.position || '',
        department_id: offer.department_id?.toString() || '',
        salary: offer.salary || '',
        bonus: offer.bonus || '',
        equity: offer.equity || '',
        start_date: offer.start_date || '',
        expiration_date: offer.expiration_date || '',
        status: offer.status?.toString() || '0',
        benefits: offer.benefits || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.offers.update', offer.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Offer')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="candidate_id" required>{t('Candidate')} </Label>
                    <Select value={data.candidate_id?.toString() || ''} onValueChange={(value) => setData('candidate_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Candidate')} />
                        </SelectTrigger>
                        <SelectContent>
                            {candidates.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.candidate_id} />
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Only qualified candidates are shown.')}
                    </p>
                    {(!candidates || candidates.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create candidate here. ')}
                            <a
                                href={route('recruitment.candidates.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('candidate')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="position">{t('Position')}</Label>
                    <Input
                        id="position"
                        type="text"
                        value={data.position}
                        onChange={(e) => setData('position', e.target.value)}
                        placeholder={t('Enter Position')}
                        required
                    />
                    <InputError message={errors.position} />
                </div>

                <div>
                    <Label htmlFor="department_id" required>{t('Department')} </Label>
                    <Select value={data.department_id?.toString() || ''} onValueChange={(value) => setData('department_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Department')} />
                        </SelectTrigger>
                        <SelectContent>
                            {departments?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.department_id} />
                    {(!departments || departments.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create department here. ')}
                            <a
                                href={route('hrm.departments.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('department')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <CurrencyInput
                        label={t('Salary')}
                        value={data.salary}
                        onChange={(value) => setData('salary', value)}
                        error={errors.salary}
                        required
                    />
                </div>

                <div>
                    <CurrencyInput
                        label={t('Bonus')}
                        value={data.bonus}
                        onChange={(value) => setData('bonus', value)}
                        error={errors.bonus}
                    />
                </div>

                <div>
                    <Label htmlFor="equity">{t('Equity')}</Label>
                    <Input
                        id="equity"
                        type="text"
                        value={data.equity}
                        onChange={(e) => setData('equity', e.target.value)}
                        placeholder={t('Enter Equity')}
                    />
                    <InputError message={errors.equity} />
                </div>

                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                        minDate={new Date(Date.now() + 24 * 60 * 60 * 1000)}
                        required
                    />
                    <InputError message={errors.start_date} />
                </div>

                <div>
                    <Label required>{t('Expiration Date')}</Label>
                    <DatePicker
                        value={data.expiration_date}
                        onChange={(date) => setData('expiration_date', date)}
                        placeholder={t('Select Expiration Date')}
                        minDate={data.start_date ? new Date(new Date(data.start_date).getTime() + 24 * 60 * 60 * 1000) : new Date(Date.now() + 24 * 60 * 60 * 1000)}
                        required
                    />
                    <InputError message={errors.expiration_date} />
                </div>





                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status?.toString() || '0'} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">{t('Draft')}</SelectItem>
                            <SelectItem value="1">{t('Sent')}</SelectItem>
                            <SelectItem value="2">{t('Accepted')}</SelectItem>
                            <SelectItem value="3">{t('Negotiating')}</SelectItem>
                            <SelectItem value="4">{t('Declined')}</SelectItem>
                            <SelectItem value="5">{t('Expired')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div>
                    <Label htmlFor="benefits">{t('Benefits')}</Label>
                    <Textarea
                        id="benefits"
                        value={data.benefits}
                        onChange={(e) => setData('benefits', e.target.value)}
                        placeholder={t('Enter Benefits')}
                        rows={3}
                    />
                    <InputError message={errors.benefits} />
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