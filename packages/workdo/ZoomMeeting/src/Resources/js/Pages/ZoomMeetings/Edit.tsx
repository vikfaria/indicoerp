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
import { EditZoomMeetingProps, EditZoomMeetingFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditZoomMeeting({ zoommeeting, onSuccess }: EditZoomMeetingProps) {
    const { users } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditZoomMeetingFormData>({
        title: zoommeeting.title ?? '',
        description: zoommeeting.description ?? '',
        meeting_password: zoommeeting.meeting_password ?? '',
        start_time: zoommeeting.start_time ?? '',
        duration: zoommeeting.duration ?? '',
        host_video: zoommeeting.host_video ?? false,
        participant_video: zoommeeting.participant_video ?? false,
        waiting_room: zoommeeting.waiting_room ?? false,
        recording: zoommeeting.recording ?? false,

        participants: (zoommeeting.participants as string[]) || [],
        host_id: zoommeeting.host_id?.toString() || '',
    });



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('zoommeeting.zoom-meetings.update', zoommeeting.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="transform-gpu">
            <DialogHeader>
                <DialogTitle>{t('Edit Zoom Meeting')}</DialogTitle>
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