import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFormFields } from '@/hooks/useFormFields';
import { EditLeaveApplicationProps, EditLeaveApplicationFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';


export default function EditLeaveApplication({ leaveapplication, onSuccess }: EditLeaveApplicationProps) {
    const { employees, leavetypes } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditLeaveApplicationFormData>({
        employee_id: leaveapplication.employee_id?.toString() || '',
        leave_type_id: leaveapplication.leave_type_id?.toString() || '',
        start_date: leaveapplication.start_date || '',
        end_date: leaveapplication.end_date || '',
        reason: leaveapplication.reason ?? '',
        attachment: leaveapplication.attachment || '',
    });

    const [leaveBalance, setLeaveBalance] = useState<any>(null);
    const [totalDays, setTotalDays] = useState(0);

    // AI hooks for reason field
    const reasonAI = useFormFields('aiField', data, setData, errors, 'edit', 'reason', 'Reason', 'hrm', 'leave_application');

    // Calculate total days when dates change
    useEffect(() => {
        if (data.start_date && data.end_date) {
            const startDate = new Date(data.start_date);
            const endDate = new Date(data.end_date);
            const diffTime = Math.abs(endDate.getTime() - startDate.getTime());
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            setTotalDays(diffDays);
        } else {
            setTotalDays(0);
        }
    }, [data.start_date, data.end_date]);

    // Get leave balance when employee and leave type are selected
    useEffect(() => {
        if (data.employee_id && data.leave_type_id) {
            axios.get(route('hrm.leave-balance', [data.employee_id, data.leave_type_id]), {
                params: { exclude_id: leaveapplication.id }
            })
                .then(response => {
                    setLeaveBalance(response.data);
                })
                .catch(() => {
                    setLeaveBalance(null);
                });
        } else {
            setLeaveBalance(null);
        }
    }, [data.employee_id, data.leave_type_id]);



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.leave-applications.update', leaveapplication.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Leave Application')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id?.toString() || ''} onValueChange={(value) => setData('employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {employees?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="leave_type_id" required>{t('Leave Type')}</Label>
                    <Select value={data.leave_type_id?.toString() || ''} onValueChange={(value) => setData('leave_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Leave Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {leavetypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.leave_type_id} />
                </div>
                
                <div>
                    <Label htmlFor="start_date" required>{t('Start Date')}</Label>
                    <DatePicker
                        id="start_date"
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                        required
                    />
                    <InputError message={errors.start_date} />
                </div>
                
                <div>
                    <Label htmlFor="end_date" required>{t('End Date')}</Label>
                    <DatePicker
                        id="end_date"
                        value={data.end_date}
                        onChange={(date) => setData('end_date', date)}
                        placeholder={t('Select End Date')}
                        required
                    />
                    <InputError message={errors.end_date} />
                </div>
                
                {leaveBalance && (
                    <div className="p-3 bg-blue-50 rounded-lg">
                        <div className="text-sm space-y-1">
                            <p><strong>{t('Leave Balance')}:</strong></p>
                            <p>{t('Total')}: {leaveBalance.total_leaves} {t('days')}</p>
                            <p>{t('Used')}: {leaveBalance.used_leaves} {t('days')} </p>
                            <p>{t('Available')}: {leaveBalance.available_leaves} {t('days')}</p>
                            {totalDays > 0 && (
                                <p className={totalDays > leaveBalance.available_leaves ? 'text-red-600 font-semibold' : 'text-green-600'}>
                                    {t('Requesting')}: {totalDays} {t('days')}
                                </p>
                            )}
                        </div>
                    </div>
                )}
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="reason" required>{t('Reason')}</Label>
                        <div className="flex gap-2">
                            {reasonAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="reason"
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        placeholder={t('Enter Reason')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.reason} />
                </div>
                
                <div>
                    <MediaPicker
                        label={t('Attachment')}
                        value={data.attachment}
                        onChange={(value) => setData('attachment', Array.isArray(value) ? value[0] || '' : value)}
                        placeholder={t('Select Attachment...')}
                        showPreview={true}
                        multiple={false}
                    />
                    <InputError message={errors.attachment} />
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