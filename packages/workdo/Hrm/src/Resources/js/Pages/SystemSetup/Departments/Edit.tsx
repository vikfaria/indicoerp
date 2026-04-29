import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { EditDepartmentProps, DepartmentFormData } from './types';

export default function Edit({ department, onSuccess, branches }: EditDepartmentProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<DepartmentFormData>({
        department_name: department.department_name ?? '',
        branch_id: department.branch_id?.toString() || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.departments.update', department.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Department')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="department_name">{t('Department Name')}</Label>
                    <Input
                        id="department_name"
                        type="text"
                        value={data.department_name}
                        onChange={(e) => setData('department_name', e.target.value)}
                        placeholder={t('Enter Department Name')}
                        required
                    />
                    <InputError message={errors.department_name} />
                </div>
                
                <div>
                    <Label htmlFor="branch_id" required>{t('Branch')}</Label>
                    <Select value={data.branch_id?.toString() || ''} onValueChange={(value) => setData('branch_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Branch')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {branches.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.branch_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.branch_id} />
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