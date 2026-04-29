import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateComplaintTypeProps, ComplaintTypeFormData } from './types';

export default function Create({ onSuccess }: CreateComplaintTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<ComplaintTypeFormData>({
        complaint_type: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.complaint-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Complaint Type')}</DialogTitle>
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}