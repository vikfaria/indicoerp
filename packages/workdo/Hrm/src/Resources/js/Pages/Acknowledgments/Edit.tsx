import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { DateTimeRangePicker } from '@/components/ui/datetime-range-picker';
import { EditAcknowledgmentProps, EditAcknowledgmentFormData } from './types';
import { usePage } from '@inertiajs/react';


export default function EditAcknowledgment({ acknowledgment, onSuccess }: EditAcknowledgmentProps) {
    const { users, hrmdocuments } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditAcknowledgmentFormData>({
        employee_id: acknowledgment.employee_id?.toString() || '',
        document_id: acknowledgment.document_id?.toString() || '',
        status: acknowledgment.status?.toString() || '0',
        acknowledgment_note: acknowledgment.acknowledgment_note ?? '',
        acknowledged_at: acknowledgment.acknowledged_at ?? '',
        assigned_by: acknowledgment.assigned_by?.toString() || '',
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.acknowledgments.update', acknowledgment.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Acknowledgment')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id?.toString() || ''} onValueChange={(value) => setData('employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {users?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="document_id" required>{t('Document')}</Label>
                    <Select value={data.document_id?.toString() || ''} onValueChange={(value) => setData('document_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Document')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {hrmdocuments?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.document_id} />
                </div>
                
                <div>
                    <Label htmlFor="acknowledgment_note" required>{t('Acknowledgment Note')}</Label>
                    <Textarea
                        id="acknowledgment_note"
                        value={data.acknowledgment_note}
                        onChange={(e) => setData('acknowledgment_note', e.target.value)}
                        placeholder={t('Enter Acknowledgment Note')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.acknowledgment_note} />
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