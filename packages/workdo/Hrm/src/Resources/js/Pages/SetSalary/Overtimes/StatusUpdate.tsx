import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/ui/input-error';
import { CheckCircle2 } from 'lucide-react';

interface Overtime {
    id: number;
    status: string;
    approval_status?: string | null;
}

interface OvertimeStatusUpdateProps {
    overtime: Overtime;
    onSuccess: () => void;
}

export default function StatusUpdate({ overtime, onSuccess }: OvertimeStatusUpdateProps) {
    const { t } = useTranslation();
    const currentStatus = overtime.approval_status ?? (overtime.status === 'active' ? 'approved' : 'rejected');
    const [selectedStatus, setSelectedStatus] = useState(currentStatus === 'pending' ? 'approved' : currentStatus);

    const { data, setData, put, processing, errors } = useForm({
        status: selectedStatus,
        rejection_reason: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.overtimes.update-status', overtime.id), {
            only: ['errors', 'flash', 'overtimes'],
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                    <CheckCircle2 className="h-5 w-5 text-indigo-600" />
                    {t('Update Overtime Status')}
                </DialogTitle>
            </DialogHeader>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="status" required>{t('Status')}</Label>
                    <Select
                        value={data.status}
                        onValueChange={(value) => {
                            setSelectedStatus(value);
                            setData('status', value);
                            if (value !== 'rejected') {
                                setData('rejection_reason', '');
                            }
                        }}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="approved">{t('Approved')}</SelectItem>
                            <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                {selectedStatus === 'rejected' && (
                    <div>
                        <Label htmlFor="rejection_reason" required>{t('Rejection Reason')}</Label>
                        <Textarea
                            id="rejection_reason"
                            value={data.rejection_reason}
                            onChange={(e) => setData('rejection_reason', e.target.value)}
                            placeholder={t('Provide the reason for rejection')}
                            rows={4}
                            required
                        />
                        <InputError message={errors.rejection_reason} />
                    </div>
                )}

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
