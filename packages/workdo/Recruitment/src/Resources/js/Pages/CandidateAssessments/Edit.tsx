import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DatePicker } from '@/components/ui/date-picker';
import { EditCandidateAssessmentProps, EditCandidateAssessmentFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function EditCandidateAssessment({ candidateassessment, onSuccess }: EditCandidateAssessmentProps) {
    const { candidates, users } = usePage<any>().props;
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm<EditCandidateAssessmentFormData>({
        assessment_name: candidateassessment.assessment_name ?? '',
        score: candidateassessment.score ?? '',
        max_score: candidateassessment.max_score ?? '',
        pass_fail_status: candidateassessment.pass_fail_status?.toString() ?? '0',
        comments: candidateassessment.comments ?? '',
        assessment_date: candidateassessment.assessment_date ?? '',
        candidate_id: candidateassessment.candidate_id?.toString() || '',
        conducted_by: candidateassessment.conducted_by?.id?.toString() || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.candidate-assessments.update', candidateassessment.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Candidate Assessment')}</DialogTitle>
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
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Only candidates with Strong Hire/Hire recommendations are shown.')}
                    </p>
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
                    <Label htmlFor="assessment_name">{t('Assessment Name')}</Label>
                    <Input
                        id="assessment_name"
                        type="text"
                        value={data.assessment_name}
                        onChange={(e) => setData('assessment_name', e.target.value)}
                        placeholder={t('Enter Assessment Name')}
                        required
                    />
                    <InputError message={errors.assessment_name} />
                </div>

                <div>
                    <Label htmlFor="score">{t('Score')}</Label>
                    <Input
                        id="score"
                        type="number"
                        step="1"
                        min="0"
                        value={data.score}
                        onChange={(e) => setData('score', e.target.value)}
                        placeholder={t('Enter Score')}
                    />
                    <InputError message={errors.score} />
                </div>

                <div>
                    <Label htmlFor="max_score">{t('Max Score')}</Label>
                    <Input
                        id="max_score"
                        type="number"
                        step="1"
                        min="0"
                        value={data.max_score}
                        onChange={(e) => setData('max_score', e.target.value)}
                        placeholder={t('Enter Max Score')}
                    />
                    <InputError message={errors.max_score} />
                </div>

                <div>
                    <Label htmlFor="pass_fail_status" required>{t('Status')} </Label>
                    <Select value={data.pass_fail_status?.toString() || '2'} onValueChange={(value) => setData('pass_fail_status', value)} required>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="2">{t('Pending')}</SelectItem>
                            <SelectItem value="0">{t('Pass')}</SelectItem>
                            <SelectItem value="1">{t('Fail')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.pass_fail_status} />
                </div>

                <div>
                    <Label htmlFor="conducted_by" required>{t('Conducted by')} </Label>
                    <Select
                        value={data.conducted_by?.toString() || ''}
                        onValueChange={(value) => setData('conducted_by', value)}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Conducted by')} />
                        </SelectTrigger>
                        <SelectContent>
                            {users?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.conducted_by} />
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Conducted by are users with staff role.')}
                    </p>
                    {(!users || users.length === 0) && (
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
                    <Label required>{t('Assessment Date')}</Label>
                    <DatePicker
                        value={data.assessment_date}
                        onChange={(date) => setData('assessment_date', date)}
                        placeholder={t('Select Assessment Date')}
                        required
                    />
                    <InputError message={errors.assessment_date} />
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
