import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { DatePicker } from '@/components/ui/date-picker';
import { TimePicker } from '@/components/ui/time-picker';
import { EditInterviewProps, EditInterviewFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditInterview({ interview, onSuccess }: EditInterviewProps) {
    const { candidates, jobpostings, interviewrounds, interviewtypes, employees } = usePage<any>().props;
    const [filteredJobs, setFilteredJobs] = useState(jobpostings || []);
    const [filteredRounds, setFilteredRounds] = useState(interviewrounds || []);
    const [filteredInterviewTypes, setFilteredInterviewTypes] = useState(interviewtypes || []);
    const [isRemoteWork, setIsRemoteWork] = useState(false);
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditInterviewFormData>({
        scheduled_date: interview.scheduled_date ?? '',
        scheduled_time: interview.scheduled_time ?? '',
        duration: interview.duration ?? '',
        location: interview.location ?? '',
        meeting_link: interview.meeting_link ?? '',
        interviewer_ids: interview.interviewer_ids || [],
        status: interview.status?.toString() ?? '0',

        candidate_id: interview.candidate_id?.toString() || '',
        round_id: interview.round_id?.toString() || '',
        interview_type_id: interview.interview_type_id?.toString() || '',
    });

    useEffect(() => {
        if (data.candidate_id) {
            axios.get(route('recruitment.candidates.interview-rounds', data.candidate_id))
                .then(response => {
                    setFilteredRounds(response.data);
                })
                .catch(() => {
                    setFilteredRounds([]);
                });

            // Check if candidate's job is remote work
            axios.get(route('recruitment.candidates.job-location', data.candidate_id))
                .then(response => {
                    const isRemote = response.data.remote_work;
                    setIsRemoteWork(isRemote);
                    if (isRemote) {
                        setData('location', 'Online');
                    }
                })
                .catch(() => {
                    setIsRemoteWork(false);
                });
        } else {
            setFilteredRounds([]);
            setData('round_id', '');
            setIsRemoteWork(false);
        }
    }, [data.candidate_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.interviews.update', interview.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Interview')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="candidate_id" required>{t('Candidate')} </Label>
                    <Select value={data.candidate_id?.toString() || ''} onValueChange={(value) => setData('candidate_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Candidate')} />
                        </SelectTrigger>
                        <SelectContent>
                            {candidates.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.candidate_id} />
                    {(!candidates || candidates.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create candidate here. ')}
                            <a
                                href={route('recruitment.candidates.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('candidate')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="round_id" required>{t('Interview Round')} </Label>
                    <Select
                        value={data.round_id?.toString() || ''}
                        onValueChange={(value) => setData('round_id', value)}
                        disabled={!data.candidate_id}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.candidate_id ? t('Select Interview Round') : t('Select Candidate first')} />
                        </SelectTrigger>
                        <SelectContent>
                            {filteredRounds?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.round_id} />
                    {(!filteredRounds || filteredRounds.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create interview round here. ')}
                            <a
                                href={route('recruitment.interview-rounds.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('interview round')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="interview_type_id" required>{t('Interview Type')} </Label>
                    <Select value={data.interview_type_id?.toString() || ''} onValueChange={(value) => setData('interview_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Interview Type')} />
                        </SelectTrigger>
                        <SelectContent>
                            {interviewtypes.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.interview_type_id} />
                    {(!interviewtypes || interviewtypes.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create interview type here. ')}
                            <a
                                href={route('recruitment.interview-types.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('interview type')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label required>{t('Scheduled Date')}</Label>
                    <DatePicker
                        value={data.scheduled_date}
                        onChange={(date) => setData('scheduled_date', date)}
                        placeholder={t('Select Scheduled Date')}
                        minDate={new Date(Date.now() + 24 * 60 * 60 * 1000)}
                        required
                    />
                    <InputError message={errors.scheduled_date} />
                </div>

                <div>
                    <Label htmlFor="scheduled_time" required>{t('Scheduled Time')}</Label>
                    <TimePicker
                        id="scheduled_time"
                        value={data.scheduled_time}
                        onChange={(time) => setData('scheduled_time', time)}
                        placeholder={t('Select Scheduled Time')}
                        required
                    />
                    <InputError message={errors.scheduled_time} />
                </div>

                <div>
                    <Label htmlFor="duration">{t('Duration (Minutes)')}</Label>
                    <Input
                        id="duration"
                        type="number"
                        step="1"
                        min="1"
                        value={data.duration}
                        onChange={(e) => setData('duration', e.target.value)}
                        placeholder={t('Enter Duration in Minutes')}
                        required
                    />
                    <InputError message={errors.duration} />
                </div>

                {!isRemoteWork && (
                    <div>
                        <Label htmlFor="location">{t('Location')}</Label>
                        <Input
                            id="location"
                            type="text"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            placeholder={t('Enter Location')}
                        />
                        <InputError message={errors.location} />
                    </div>
                )}

                <div>
                    <Label htmlFor="meeting_link">{t('Meeting Link')}</Label>
                    <Input
                        id="meeting_link"
                        type="text"
                        value={data.meeting_link}
                        onChange={(e) => setData('meeting_link', e.target.value)}
                        placeholder={t('Enter Meeting Link')}

                    />
                    <InputError message={errors.meeting_link} />
                </div>

                <div>
                    <Label>{t('Interviewers')}</Label>
                    <MultiSelectEnhanced
                        options={employees?.map((emp: any) => ({
                            value: emp.id.toString(),
                            label: emp.name
                        })) || []}
                        value={data.interviewer_ids}
                        onValueChange={(value) => setData('interviewer_ids', value)}
                        placeholder={t('Select Interviewers...')}
                        searchable={true}
                    />
                    <InputError message={errors.interviewer_ids} />
                    {(!employees || employees.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Interviewers are users with staff role. Create user here. ')}
                            <a
                                href={route('users.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('Create User')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status?.toString() || '0'} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">{t('Scheduled')}</SelectItem>
                            <SelectItem value="1">{t('Completed')}</SelectItem>
                            <SelectItem value="2">{t('Cancelled')}</SelectItem>
                            <SelectItem value="3">{t('No-show')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
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