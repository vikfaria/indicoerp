import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LeaveApplication } from './types';

interface StatusUpdateProps {
    leaveapplication: LeaveApplication;
    onSuccess: () => void;
}

interface StatusUpdateFormData {
    status: string;
    approver_comment: string;
}

export default function StatusUpdate({ leaveapplication, onSuccess }: StatusUpdateProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<StatusUpdateFormData>({
        status: leaveapplication.status || 'pending',
        approver_comment: leaveapplication.approver_comment || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.leave-applications.updateStatus', leaveapplication.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Update Leave Status')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                            <SelectItem value="approved">{t('Approved')}</SelectItem>
                            <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                
                <div>
                    <Label htmlFor="approver_comment">{t('Comment')}</Label>
                    <Textarea
                        id="approver_comment"
                        value={data.approver_comment}
                        onChange={(e) => setData('approver_comment', e.target.value)}
                        placeholder={t('Enter your comment...')}
                        rows={4}
                    />
                    <InputError message={errors.approver_comment} />
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