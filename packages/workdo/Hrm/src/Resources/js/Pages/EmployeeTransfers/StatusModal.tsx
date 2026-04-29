import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { EmployeeTransfer } from './types';

interface StatusModalProps {
    employeeTransfer: EmployeeTransfer | null;
    onClose: () => void;
}

export default function StatusModal({ employeeTransfer, onClose }: StatusModalProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, put, processing, errors, reset } = useForm({
        status: employeeTransfer?.status || 'pending'
    });

    const statusOptions = [
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'in progress', label: t('In Progress') },

        { value: 'rejected', label: t('Rejected') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        put(route('hrm.employee-transfers.update-status', employeeTransfer?.id), {
            onSuccess: () => {
                onClose();
                reset();
            },
            onError: () => {
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    if (!employeeTransfer) return null;

    return (
        <Dialog open={!!employeeTransfer} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('Update Transfer Status')}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="employee">{t('Employee')}</Label>
                        <div className="p-2 bg-gray-50 rounded border">
                            {employeeTransfer.employee?.name || '-'}
                        </div>
                    </div>



                    <div className="space-y-2">
                        <Label htmlFor="status">{t('Status')} *</Label>
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
                        <InputError message={errors.status} />
                    </div>

                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing || isSubmitting}>
                            {processing || isSubmitting ? t('Updating...') : t('Update Status')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}