import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { EditComplaintTypeProps, ComplaintTypeFormData } from './types';

export default function Edit({ complainttype, onSuccess }: EditComplaintTypeProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<ComplaintTypeFormData>({
        complaint_type: complainttype.complaint_type ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.complaint-types.update', complainttype.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Complaint Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="complaint_type">{t('Complaint Type')}</Label>
                    <Input
                        id="complaint_type"
                        type="text"
                        value={data.complaint_type}
                        onChange={(e) => setData('complaint_type', e.target.value)}
                        placeholder={t('Enter Complaint Type')}
                        required
                    />
                    <InputError message={errors.complaint_type} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
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