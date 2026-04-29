import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { Rating } from '@/components/ui/rating';
import { EditInterviewFeedbackProps, EditInterviewFeedbackFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function EditInterviewFeedback({ interviewfeedback, onSuccess }: EditInterviewFeedbackProps) {
    const { interviews, users } = usePage<any>().props;
    const [filteredInterviewers, setFilteredInterviewers] = useState(users || []);
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditInterviewFeedbackFormData>({
        technical_rating: interviewfeedback.technical_rating ?? '',
        communication_rating: interviewfeedback.communication_rating ?? '',
        cultural_fit_rating: interviewfeedback.cultural_fit_rating ?? '',
        overall_rating: interviewfeedback.overall_rating ?? '',
        strengths: interviewfeedback.strengths ?? '',
        weaknesses: interviewfeedback.weaknesses ?? '',
        comments: interviewfeedback.comments ?? '',
        recommendation: interviewfeedback.recommendation?.toString() ?? '0',
        interview_id: interviewfeedback.interview_id?.toString() || '',
        interviewer_ids: interviewfeedback.interviewer_ids || [],
    });

    // Filter interviewers based on selected interview
    useEffect(() => {
        if (data.interview_id) {
            const selectedInterview = interviews.find((interview: any) => interview.id.toString() === data.interview_id);
            if (selectedInterview && selectedInterview.interviewers && selectedInterview.interviewers.length > 0) {
                // Filter users to only show those who were interviewers for this interview
                const interviewerUsers = users.filter((user: any) =>
                    selectedInterview.interviewers.includes(user.name) ||
                    selectedInterview.interviewers.includes(user.id.toString())
                );
                setFilteredInterviewers(interviewerUsers);
            } else {
                setFilteredInterviewers([]);
            }
        } else {
            setFilteredInterviewers([]);
        }
    }, [data.interview_id, interviews, users]);

    // Initialize filtered interviewers on component mount
    useEffect(() => {
        if (interviewfeedback.interview_id) {
            const selectedInterview = interviews.find((interview: any) => interview.id.toString() === interviewfeedback.interview_id.toString());
            if (selectedInterview && selectedInterview.interviewers && selectedInterview.interviewers.length > 0) {
                const interviewerUsers = users.filter((user: any) =>
                    selectedInterview.interviewers.includes(user.name) ||
                    selectedInterview.interviewers.includes(user.id.toString())
                );
                setFilteredInterviewers(interviewerUsers);
            }
        }
    }, []);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.interview-feedbacks.update', interviewfeedback.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Interview Feedback')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="interview_id" required>{t('Interview')} </Label>
                    <Select value={data.interview_id?.toString() || ''} onValueChange={(value) => setData('interview_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Interview')} />
                        </SelectTrigger>
                        <SelectContent>
                            {interviews.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.interview_id} />
                    {(!interviews || interviews.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create interview here. ')}
                            <a
                                href={route('recruitment.interviews.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('interview')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label required>{t('Interviewers')} </Label>
                    <MultiSelectEnhanced
                        options={filteredInterviewers.map((user: any) => ({
                            value: user.id.toString(),
                            label: user.name
                        }))}
                        value={data.interviewer_ids}
                        onValueChange={(value) => setData('interviewer_ids', value)}
                        placeholder={!data.interview_id ? t('Select Interview first') : (filteredInterviewers.length === 0 ? t('No interviewers available') : t('Select Interviewers...'))}
                        searchable={true}
                        disabled={!data.interview_id || filteredInterviewers.length === 0}
                    />
                    <InputError message={errors.interviewer_ids} />
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Interviewers are filtered based on selected interview.')}
                    </p>
                    {(!filteredInterviewers || filteredInterviewers.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create user here. ')}
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
                    <Label>{t('Technical Rating')}</Label>
                    <Rating
                        value={Number(data.technical_rating) || 0}
                        onChange={(value) => setData('technical_rating', value.toString())}
                    />
                    <InputError message={errors.technical_rating} />
                </div>

                <div>
                    <Label>{t('Communication Rating')}</Label>
                    <Rating
                        value={Number(data.communication_rating) || 0}
                        onChange={(value) => setData('communication_rating', value.toString())}
                    />
                    <InputError message={errors.communication_rating} />
                </div>

                <div>
                    <Label>{t('Cultural Fit Rating')}</Label>
                    <Rating
                        value={Number(data.cultural_fit_rating) || 0}
                        onChange={(value) => setData('cultural_fit_rating', value.toString())}
                    />
                    <InputError message={errors.cultural_fit_rating} />
                </div>



                <div>
                    <Label htmlFor="recommendation">{t('Recommendation')}</Label>
                    <Select value={data.recommendation?.toString() || '0'} onValueChange={(value) => setData('recommendation', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">{t('Strong Hire')}</SelectItem>
                            <SelectItem value="1">{t('Hire')}</SelectItem>
                            <SelectItem value="2">{t('Maybe')}</SelectItem>
                            <SelectItem value="3">{t('Reject')}</SelectItem>
                            <SelectItem value="4">{t('Strong Reject')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.recommendation} />
                </div>

                <div>
                    <Label htmlFor="strengths">{t('Strengths')}</Label>
                    <Textarea
                        id="strengths"
                        value={data.strengths}
                        onChange={(e) => setData('strengths', e.target.value)}
                        placeholder={t('Enter Strengths')}
                        rows={3}
                    />
                    <InputError message={errors.strengths} />
                </div>

                <div>
                    <Label htmlFor="weaknesses">{t('Weaknesses')}</Label>
                    <Textarea
                        id="weaknesses"
                        value={data.weaknesses}
                        onChange={(e) => setData('weaknesses', e.target.value)}
                        placeholder={t('Enter Weaknesses')}
                        rows={3}
                    />
                    <InputError message={errors.weaknesses} />
                </div>

                <div>
                    <Label htmlFor="comments">{t('Comments')}</Label>
                    <Textarea
                        id="comments"
                        value={data.comments}
                        onChange={(e) => setData('comments', e.target.value)}
                        placeholder={t('Enter Comments')}
                        rows={3}
                    />
                    <InputError message={errors.comments} />
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