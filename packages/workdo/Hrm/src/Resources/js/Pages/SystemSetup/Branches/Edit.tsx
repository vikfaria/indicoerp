import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';

import { EditBranchProps, BranchFormData } from './types';

export default function Edit({ branch, onSuccess }: EditBranchProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<BranchFormData>({
        branch_name: branch.branch_name ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.branches.update', branch.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Branch')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="branch_name">{t('Branch Name')}</Label>
                    <Input
                        id="branch_name"
                        type="text"
                        value={data.branch_name}
                        onChange={(e) => setData('branch_name', e.target.value)}
                        placeholder={t('Enter Branch Name')}
                        required
                    />
                    <InputError message={errors.branch_name} />
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