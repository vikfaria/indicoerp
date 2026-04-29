import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { EditTerminationTypeProps, TerminationTypeFormData } from './types';

export default function Edit({ terminationtype, onSuccess }: EditTerminationTypeProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<TerminationTypeFormData>({
        termination_type: terminationtype.termination_type ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.termination-types.update', terminationtype.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Termination Type')}</DialogTitle>
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
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}