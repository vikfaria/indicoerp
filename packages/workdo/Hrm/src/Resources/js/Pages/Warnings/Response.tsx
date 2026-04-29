import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface WarningResponseProps {
    warning: any;
    onSuccess: () => void;
}

interface WarningResponseFormData {
    warning_status: string;
    employee_response: string;
}

export default function WarningResponse({ warning, onSuccess }: WarningResponseProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<WarningResponseFormData>({
        warning_status: warning.status || 'pending',
        employee_response: warning.employee_response || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.warnings.response', warning.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Response')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="warning_status">{t('Warning Status')}</Label>
                    <Select value={data.warning_status} onValueChange={(value) => setData('warning_status', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                            <SelectItem value="approved">{t('Approved')}</SelectItem>
                            <SelectItem value="rejected">{t('Rejected')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.warning_status} />
                </div>
                
                <div>
                    <Label htmlFor="employee_response">{t('Response')}</Label>
                    <Textarea
                        id="employee_response"
                        value={data.employee_response}
                        onChange={(e) => setData('employee_response', e.target.value)}
                        placeholder={t('Enter Response')}
                        rows={4}
                    />
                    <InputError message={errors.employee_response} />
                </div>
                
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Saving...') : t('Save')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}