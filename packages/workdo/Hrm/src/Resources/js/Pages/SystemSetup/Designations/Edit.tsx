import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { EditDesignationProps, DesignationFormData } from './types';

export default function Edit({ designation, onSuccess, branches, departments }: EditDesignationProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<DesignationFormData>({
        designation_name: designation.designation_name ?? '',
        branch_id: designation.branch_id?.toString() || '',
        department_id: designation.department_id?.toString() || '',
    });

    // Filter departments based on selected branch
    const filteredDepartments = departments.filter(dept => dept.branch_id?.toString() === data.branch_id);

    const handleBranchChange = (value: string) => {
        setData({
            ...data,
            branch_id: value,
            department_id: '' // Reset department when branch changes
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.designations.update', designation.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Designation')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="designation_name">{t('Designation Name')}</Label>
                    <Input
                        id="designation_name"
                        type="text"
                        value={data.designation_name}
                        onChange={(e) => setData('designation_name', e.target.value)}
                        placeholder={t('Enter Designation Name')}
                        required
                    />
                    <InputError message={errors.designation_name} />
                </div>

                <div>
                    <Label htmlFor="branch_id" required>{t('Branch')}</Label>
                    <Select value={data.branch_id?.toString() || ''} onValueChange={handleBranchChange} required> 
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

                <div>
                    <Label htmlFor="department_id" required>{t('Department')}</Label>
                    <Select
                        value={data.department_id?.toString() || ''}
                        onValueChange={(value) => setData('department_id', value)}
                        disabled={!data.branch_id}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.branch_id ? t('Select Department') : t('Select Branch first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredDepartments?.length > 0 ? (
                                filteredDepartments.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.department_name}
                                    </SelectItem>
                                ))
                            ) : (
                                <SelectItem value="no-department" disabled>
                                    {t('No Department found')}
                                </SelectItem>
                            )}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.department_id} />
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