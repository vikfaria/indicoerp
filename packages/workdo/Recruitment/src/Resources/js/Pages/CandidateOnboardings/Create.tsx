import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { CreateCandidateOnboardingProps, CreateCandidateOnboardingFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function Create({ onSuccess }: CreateCandidateOnboardingProps) {
    const { candidates, onboardingchecklists, users } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateCandidateOnboardingFormData>({
        candidate_id: '',
        checklist_id: '',
        start_date: '',
        buddy_employee_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.candidate-onboardings.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Candidate Onboarding')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="candidate_id" required>{t('Candidate')} </Label>
                    <Select value={data.candidate_id?.toString() || ''} onValueChange={(value) => setData('candidate_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Candidate')} />
                        </SelectTrigger>
                        <SelectContent>
                            {candidates?.map((item: any) => (
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
                    <Label htmlFor="checklist_id" required>{t('Onboarding Checklist')} </Label>
                    <Select value={data.checklist_id?.toString() || ''} onValueChange={(value) => setData('checklist_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Checklist')} />
                        </SelectTrigger>
                        <SelectContent>
                            {onboardingchecklists?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.checklist_id} />
                    {(!onboardingchecklists || onboardingchecklists.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create onboarding checklist here. ')}
                            <a
                                href={route('recruitment.onboarding-checklists.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('onboarding checklist')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                        minDate={new Date(Date.now() + 24 * 60 * 60 * 1000)}
                        required
                    />
                    <InputError message={errors.start_date} />
                </div>

                <div>
                    <Label htmlFor="buddy_employee_id">{t('Buddy Employee')}</Label>
                    <Select value={data.buddy_employee_id?.toString() || ''} onValueChange={(value) => setData('buddy_employee_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Buddy Employee')} />
                        </SelectTrigger>
                        <SelectContent>
                            {users?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.buddy_employee_id} />
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Buddy employees are users with staff role.')}
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