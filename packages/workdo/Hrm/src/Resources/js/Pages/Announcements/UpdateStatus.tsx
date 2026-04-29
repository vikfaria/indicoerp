import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Announcement } from './types';

interface UpdateStatusProps {
    announcement: Announcement;
    onSuccess: () => void;
}

export default function UpdateStatus({ announcement, onSuccess }: UpdateStatusProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        status: announcement.status || 'draft',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.announcements.update-status', announcement.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Update Announcement Status')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label required>{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">{t('Draft')}</SelectItem>
                            <SelectItem value="active">{t('Active')}</SelectItem>
                            <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update Status')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}