import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface StatusUpdateProps {
    event: any;
    onSuccess: () => void;
}

export default function StatusUpdate({ event, onSuccess }: StatusUpdateProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        status: event.status || 'pending',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.events.status-update', event.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Update Event Status')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label>{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                            <SelectItem value="approved">{t('Approved')}</SelectItem>
                            <SelectItem value="reject">{t('Reject')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
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