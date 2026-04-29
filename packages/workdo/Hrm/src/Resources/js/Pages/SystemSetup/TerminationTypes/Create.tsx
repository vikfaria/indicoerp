import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { CreateTerminationTypeProps, TerminationTypeFormData } from './types';

export default function Create({ onSuccess }: CreateTerminationTypeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<TerminationTypeFormData>({
        termination_type: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.termination-types.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Termination Type')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="termination_type">{t('Termination Type')}</Label>
                    <Input
                        id="termination_type"
                        type="text"
                        value={data.termination_type}
                        onChange={(e) => setData('termination_type', e.target.value)}
                        placeholder={t('Enter Termination Type')}
                        required
                    />
                    <InputError message={errors.termination_type} />
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