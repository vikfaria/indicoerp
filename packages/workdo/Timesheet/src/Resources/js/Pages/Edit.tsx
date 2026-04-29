import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { DatePicker } from "@/components/ui/date-picker";
import InputError from "@/components/ui/input-error";
import { useState, useEffect } from 'react';
import { useFormFields } from '@/hooks/useFormFields';

interface Timesheet {
    id: number;
    user_id: number;
    project_id?: number;
    task_id?: number;
    date: string;
    hours: number;
    minutes: number;
    notes?: string;
    type: 'clock_in_out' | 'project' | 'manual';
}

interface EditTimesheetFormData {
    user_id: string;
    project_id: string;
    task_id: string;
    date: string;
    hours: number;
    minutes: number;
    notes: string;
    type: 'clock_in_out' | 'project' | 'manual';
}

interface EditTimesheetProps {
    timesheet: Timesheet;
    users: Array<{ id: number; name: string }>;
    projects: Array<{ id: number; name: string }>;
    hasHRM: boolean;
    hasTaskly: boolean;
    onSuccess?: () => void;
}

export default function Edit({ timesheet, users, projects, hasHRM, hasTaskly, onSuccess }: EditTimesheetProps) {
    const { t } = useTranslation();
    const { auth } = usePage<any>().props;
    
    const { data, setData, put, processing, errors } = useForm<EditTimesheetFormData>({
        user_id: timesheet.user_id?.toString() || '',
        project_id: timesheet.project_id?.toString() || '',
        task_id: timesheet.task_id?.toString() || '',
        date: timesheet.date ? new Date(timesheet.date).toISOString().split('T')[0] : '',
        hours: timesheet.hours,
        minutes: timesheet.minutes,
        notes: timesheet.notes || '',
        type: timesheet.type,
    });

    const setDataWrapper = (key: string, value: any) => {
        setData(key as keyof EditTimesheetFormData, value);
    };

    const [attendanceInfo, setAttendanceInfo] = useState<any>(null);
    const [remainingHours, setRemainingHours] = useState({ hours: 0, minutes: 0 });

    const formFields = useFormFields('createTasklyProjectTaskField', data, setDataWrapper, {}, 'edit');

    const fetchAttendanceHours = async (userId: string, date: string) => {
        if (!userId || !date) return;
        
        try {
            const response = await fetch(`${route('timesheet.attendance-hours')}?user_id=${userId}&date=${date}&exclude_id=${timesheet.id}`);
            const attendanceData = await response.json();
            
            if (attendanceData && !attendanceData.error) {
                setAttendanceInfo(attendanceData);
                setRemainingHours({
                    hours: attendanceData.remaining_hours || 0,
                    minutes: attendanceData.remaining_minutes || 0
                });
            }
        } catch (error) {
            console.error('Failed to fetch attendance hours:', error);
        }
    };

    // Fetch attendance hours when user or date changes
    useEffect(() => {
        if (data.user_id && data.date) {
            fetchAttendanceHours(data.user_id, data.date);
        }
    }, [data.user_id, data.date]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('timesheet.update', timesheet.id), {
            onSuccess: () => {
                onSuccess?.();
            }
        });
    };



    return (
        <DialogContent className="max-w-lg">
            <DialogHeader>
                <DialogTitle>{t('Edit Timesheet')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label>{t('Type')}</Label>
                    <RadioGroup value={data.type} onValueChange={(value: any) => setData('type', value)} className="flex gap-6 mt-2">
                        {hasHRM && (
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="clock_in_out" id="clock_in_out" />
                                <Label htmlFor="clock_in_out">{t('Clock In/Out')}</Label>
                            </div>
                        )}
                        {hasTaskly && (
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="project" id="project" />
                                <Label htmlFor="project">{t('Project')}</Label>
                            </div>
                        )}
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="manual" id="manual" />
                            <Label htmlFor="manual">{t('Manual')}</Label>
                        </div>
                    </RadioGroup>
                    <InputError message={errors.type} />
                </div>

                {(auth.user?.permissions?.includes('manage-any-users') || auth.user?.permissions?.includes('manage-own-users')) && users?.length > 0 && (
                    <div>
                        <Label htmlFor="user_id">{t('User')}</Label>
                        <Select value={data.user_id} onValueChange={(value) => setData('user_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select user')} />
                            </SelectTrigger>
                            <SelectContent>
                                {users.map((user) => (
                                    <SelectItem key={user.id} value={user.id.toString()}>
                                        {user.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                )}

                {data.type === 'project' && hasTaskly && (
                    <>
                        {formFields.map((field) => (
                            <div key={field.id}>
                                {field.component}
                            </div>
                        ))}
                    </>
                )}

                <div>
                    <Label>{t('Date')}</Label>
                    <DatePicker
                        value={data.date}
                        onChange={(value) => setData('date', value)}
                        placeholder={t('Select date')}
                    />
                    <InputError message={errors.date} />
                </div>

                {attendanceInfo && data.type === 'clock_in_out' && (
                    <div className="p-3 bg-blue-50 rounded-lg text-sm">
                        <div className="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div className="font-medium text-blue-900">{t('Total Hours')}</div>
                                <div className="text-blue-700">{attendanceInfo.total_hours}h {attendanceInfo.total_minutes}m</div>
                            </div>
                            <div>
                                <div className="font-medium text-orange-900">{t('Used Hours')}</div>
                                <div className="text-orange-700">{attendanceInfo.used_hours}h {attendanceInfo.used_minutes}m</div>
                            </div>
                            <div>
                                <div className="font-medium text-green-900">{t('Remaining Hours')}</div>
                                <div className="text-green-700">{attendanceInfo.remaining_hours}h {attendanceInfo.remaining_minutes}m</div>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="hours">{t('Hours')}</Label>
                        <Input
                            id="hours"
                            type="number"
                            min="0"
                            max="12"
                            value={data.hours}
                            onChange={(e) => setData('hours', parseInt(e.target.value) || 0)}
                            required
                        />
                        <InputError message={errors.hours} />
                    </div>
                    <div>
                        <Label htmlFor="minutes">{t('Minutes')}</Label>
                        <Select value={data.minutes.toString()} onValueChange={(value) => setData('minutes', parseInt(value))}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select minutes')} />
                            </SelectTrigger>
                            <SelectContent>
                                {(() => {
                                    const allMinutes = Array.from({ length: 60 }, (_, i) => i + 1);
                                    const currentMinutes = timesheet.minutes;
                                    
                                    // Edit mode: always show all minutes + ensure current saved value is included
                                    if (data.type === 'clock_in_out' && remainingHours) {
                                        const totalRemainingMinutes = (remainingHours.hours * 60) + remainingHours.minutes;
                                        const selectedHours = data.hours || 0;
                                        const availableMinutesForHour = Math.max(0, totalRemainingMinutes - (selectedHours * 60));
                                        
                                        const filteredMinutes = allMinutes.filter(minute => minute <= availableMinutesForHour);
                                        
                                        // Force include current saved minutes even if exceeds remaining time
                                        if (currentMinutes && !filteredMinutes.includes(currentMinutes)) {
                                            filteredMinutes.push(currentMinutes);
                                            filteredMinutes.sort((a, b) => a - b);
                                        }
                                        
                                        return filteredMinutes.map(minute => (
                                            <SelectItem key={minute} value={minute.toString()}>
                                                {minute}
                                            </SelectItem>
                                        ));
                                    }
                                    
                                    return allMinutes.map(minute => (
                                        <SelectItem key={minute} value={minute.toString()}>
                                            {minute}
                                        </SelectItem>
                                    ));
                                })()}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.minutes} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder={t('Enter notes')}
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