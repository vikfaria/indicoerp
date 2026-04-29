import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { DateTimeRangePicker } from '@/components/ui/datetime-range-picker';
import { Switch } from '@/components/ui/switch';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateZoomMeetingProps, CreateZoomMeetingFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useFormFields } from '@/hooks/useFormFields';

export default function Create({ onSuccess }: CreateZoomMeetingProps) {
    const { users } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateZoomMeetingFormData>({
        title: '',
        description: '',
        meeting_password: '',
        start_time: '',
        duration: '',
        host_video: false,
        participant_video: false,
        waiting_room: false,
        recording: false,
        status: 'Scheduled',
        participants: [] as string[],
        host_id: '',
        sync_to_google_calendar: false,
    });
    
    const calendarFields = useFormFields('createCalendarSyncField', data, setData, errors, 'create', t, 'ZoomMeeting');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('zoommeeting.zoom-meetings.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="transform-gpu">
            <DialogHeader>
                <DialogTitle>{t('Create Zoom Meeting')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title">{t('Title')}</Label>
                    <Input
                        id="title"
                        type="text"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter Title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>
                
                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>
                

                <div>
                    <Label htmlFor="meeting_password">{t('Meeting Password')}</Label>
                    <Input
                        id="meeting_password"
                        type="text"
                        value={data.meeting_password}
                        onChange={(e) => setData('meeting_password', e.target.value)}
                        placeholder={t('Enter Meeting Password')}
                        
                    />
                    <InputError message={errors.meeting_password} />
                </div>
                
                <div>
                    <Label required>{t('Start Time')}</Label>
                    <DateTimeRangePicker
                        value={data.start_time}
                        onChange={(value) => setData('start_time', value)}
                        placeholder={t('Select Start Time')}
                        mode="single"
                    />
                    <InputError message={errors.start_time} />
                </div>
                
                <div>
                    <Label htmlFor="duration">{t('Duration')}</Label>
                    <Input
                        id="duration"
                        type="number"
                        step="1"
                        min="0"
                        value={data.duration}
                        onChange={(e) => setData('duration', e.target.value)}
                        placeholder="0"
                        required
                    />
                    <InputError message={errors.duration} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="host_video"
                        checked={data.host_video || false}
                        onCheckedChange={(checked) => setData('host_video', !!checked)}
                    />
                    <Label htmlFor="host_video" className="cursor-pointer">{t('Host Video')}</Label>
                    <InputError message={errors.host_video} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="participant_video"
                        checked={data.participant_video || false}
                        onCheckedChange={(checked) => setData('participant_video', !!checked)}
                    />
                    <Label htmlFor="participant_video" className="cursor-pointer">{t('Participant Video')}</Label>
                    <InputError message={errors.participant_video} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="waiting_room"
                        checked={data.waiting_room || false}
                        onCheckedChange={(checked) => setData('waiting_room', !!checked)}
                    />
                    <Label htmlFor="waiting_room" className="cursor-pointer">{t('Waiting Room')}</Label>
                    <InputError message={errors.waiting_room} />
                </div>
                
                <div className="flex items-center space-x-2">
                    <Switch
                        id="recording"
                        checked={data.recording || false}
                        onCheckedChange={(checked) => setData('recording', !!checked)}
                    />
                    <Label htmlFor="recording" className="cursor-pointer">{t('Recording')}</Label>
                    <InputError message={errors.recording} />
                </div>
                
                <div>
                    <Label required>{t('Status')}</Label>
                    <RadioGroup value={data.status || 'Scheduled'} onValueChange={(value) => setData('status', value)} className="flex gap-6 mt-2">
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="Scheduled" id="status_scheduled" />
                            <Label htmlFor="status_scheduled" className="cursor-pointer">{t('Scheduled')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="Started" id="status_started" />
                            <Label htmlFor="status_started" className="cursor-pointer">{t('Started')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="Ended" id="status_ended" />
                            <Label htmlFor="status_ended" className="cursor-pointer">{t('Ended')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="Cancelled" id="status_cancelled" />
                            <Label htmlFor="status_cancelled" className="cursor-pointer">{t('Cancelled')}</Label>
                        </div>
                    </RadioGroup>
                    <InputError message={errors.status} />
                </div>
                
                <div>
                    <Label>{t('Participants')}</Label>
                    <MultiSelectEnhanced
                        options={users?.map((item: any) => ({ value: item.id.toString(), label: item.name })) || []}
                        value={data.participants}
                        onValueChange={(value) => setData('participants', value)}
                        placeholder={t('Select Participants...')}
                        searchable={true}
                    />
                    <InputError message={errors.participants} />
                </div>
                
                <div>
                    <Label htmlFor="host_id">{t('Host')}</Label>
                    <Select value={data.host_id?.toString() || ''} onValueChange={(value) => setData('host_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Host')} />
                        </SelectTrigger>
                        <SelectContent>
                            {users.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.host_id} />
                </div>
                
                {/* Calendar Sync Field */}
                {calendarFields.map((field) => (
                    <div key={field.id}>
                        {field.component}
                    </div>
                ))}
                
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}