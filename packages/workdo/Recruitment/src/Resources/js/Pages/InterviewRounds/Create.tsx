import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateInterviewRoundProps, CreateInterviewRoundFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Create({ onSuccess }: CreateInterviewRoundProps) {
    const { jobpostings } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateInterviewRoundFormData>({
        name: '',
        sequence_number: '',
        description: '',
        status: '0',
        job_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('recruitment.interview-rounds.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Interview Round')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="job_id" required>{t('Job')}</Label>
                    <Select value={data.job_id?.toString() || ''} onValueChange={(value) => setData('job_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Job')} />
                        </SelectTrigger>
                        <SelectContent>
                            {jobpostings.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.job_id} />
                    {(!jobpostings || jobpostings.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create job posting here. ')}
                            <a
                                href={route('recruitment.job-postings.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('job posting')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="name">{t('Name')}</Label>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter Name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label htmlFor="sequence_number">{t('Sequence Number')}</Label>
                    <Input
                        id="sequence_number"
                        type="number"
                        step="1"
                        min="0"
                        value={data.sequence_number}
                        onChange={(e) => setData('sequence_number', e.target.value)}
                        placeholder={t('Enter Sequence Number')}
                    />
                    <InputError message={errors.sequence_number} />
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
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status?.toString() || '0'} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">{t('active')}</SelectItem>
                            <SelectItem value="1">{t('inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
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