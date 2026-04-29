import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateEventTypeProps, EventTypeFormData } from './types';

export default function Create({ onSuccess }: CreateEventTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<EventTypeFormData>({
        event_type: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.event-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Event Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="event_type">{t('Event Type')}</Label>
                    <Input
                        id="event_type"
                        type="text"
                        value={data.event_type}
                        onChange={(e) => setData('event_type', e.target.value)}
                        placeholder={t('Enter Event Type')}
                        required
                    />
                    <InputError message={errors.event_type} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => onSuccess()}>
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