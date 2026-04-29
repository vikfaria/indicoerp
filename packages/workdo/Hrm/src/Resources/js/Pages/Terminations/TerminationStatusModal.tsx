import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Play } from 'lucide-react';
import { Termination } from './types';

interface TerminationStatusModalProps {
    termination: Termination;
    onSuccess: () => void;
}

export default function TerminationStatusModal({ termination, onSuccess }: TerminationStatusModalProps) {
    const { t } = useTranslation();
    const [selectedStatus, setSelectedStatus] = useState(termination.status || 'pending');

    const { put, processing, setData } = useForm({
        status: selectedStatus
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.terminations.update-status', termination.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    const statusOptions = [
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'rejected', label: t('Rejected') }
    ];

    return (
        <DialogContent className="max-w-md">
            <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                    <Play className="h-5 w-5 text-purple-600" />
                    {t('Update Termination Status')}
                </DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        {t('Status')}
                    </label>
                    <Select value={selectedStatus} onValueChange={(value) => {
                        setSelectedStatus(value);
                        setData('status', value);
                    }}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex justify-end gap-3">
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