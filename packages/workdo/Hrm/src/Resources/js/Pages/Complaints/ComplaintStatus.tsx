import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Complaint } from './types';

interface ComplaintStatusProps {
    complaint: Complaint;
    onSuccess: () => void;
}

export default function ComplaintStatus({ complaint, onSuccess }: ComplaintStatusProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, put, errors, processing } = useForm({
        status: complaint.status || 'pending'
    });

    const statusOptions = [
        { value: 'pending', label: t('Pending') },
        { value: 'in review', label: t('In Review') },
        { value: 'assigned', label: t('Assigned') },
        { value: 'in progress', label: t('In Progress') },
        { value: 'resolved', label: t('Resolved') }
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        put(route('hrm.complaints.update-status', complaint.id), {
            onSuccess: () => {
                onSuccess();
                setIsSubmitting(false);
            },
            onError: () => {
                setIsSubmitting(false);
            }
        });
    };

    return (
        <DialogContent className="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Update Complaint Status')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.status && <p className="text-sm text-red-600">{errors.status}</p>}
                </div>

                <div className="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing || isSubmitting}>
                        {processing || isSubmitting ? t('Updating...') : t('Update Status')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}