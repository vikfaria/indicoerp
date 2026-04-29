import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { DateTimeRangePicker } from '@/components/ui/datetime-range-picker';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EditAttendanceFormData, EditAttendanceProps } from './types';

export default function Edit({ attendance, onSuccess }: EditAttendanceProps) {
    const { employees, shifts } = usePage<any>().props;
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<EditAttendanceFormData>({
        employee_id: attendance.employee_id?.toString() || '',
        date: attendance.date || '',
        clock_in: attendance.clock_in ? attendance.clock_in.substring(0, 19) : '',
        clock_out: attendance.clock_out ? attendance.clock_out.substring(0, 19) : '',
        break_hour: attendance.break_hour?.toString() || '',
        notes: attendance.notes || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.attendances.update', attendance.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Edit Attendance')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                        <Label required>{t('Date')}</Label>
                        <DatePicker
                            value={data.date}
                            onChange={(date) => setData('date', date)}
                            placeholder={t('Select Date')}
                            required
                        />
                        <InputError message={errors.date} />
                    </div>

                    <div>
                        <Label htmlFor="clock_in" required>{t('Clock In Time')}</Label>
                        <DateTimeRangePicker
                            value={data.clock_in}
                            onChange={(value) => setData('clock_in', value)}
                            placeholder={t('Select Clock In Time')}
                            mode="single"
                            required
                        />
                        <InputError message={errors.clock_in} />
                    </div>

                    <div>
                        <Label htmlFor="clock_out">{t('Clock Out Time')}</Label>
                        <DateTimeRangePicker
                            value={data.clock_out}
                            onChange={(value) => setData('clock_out', value)}
                            placeholder={t('Select Clock Out Time')}
                            mode="single"
                            required
                        />
                        <InputError message={errors.clock_out} />
                    </div>


                </div>

                <div>
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={t('Enter Notes')}
                        rows={3}
                    />
                    <InputError message={errors.notes} />
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