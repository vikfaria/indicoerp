import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/ui/input-error';
import { X } from 'lucide-react';
import { TrainingType, Branch, Department } from './types';

interface EditProps {
    data: TrainingType;
    trainingType: TrainingType;
    onSuccess: () => void;
    branches: Branch[];
    departments: Department[];
}

export default function EditTrainingType({ data: initialData, trainingType, onSuccess, branches, departments }: EditProps) {
    const { t } = useTranslation();
    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);

    const { data, setData, put, processing, errors } = useForm({
        name: initialData.name,
        description: initialData.description || '',
        branch_id: initialData.branch_id.toString(),
        department_id: initialData.department_id?.toString() || '',
        is_active: initialData.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('training.training-types.update', trainingType.id), {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    useEffect(() => {
        if (data.branch_id) {
            const branchDepartments = departments.filter(dept => dept.branch_id.toString() === data.branch_id);
            setFilteredDepartments(branchDepartments);
            if (data.department_id && !branchDepartments.find(dept => dept.id.toString() === data.department_id)) {
                setData('department_id', '');
            }
        } else {
            setFilteredDepartments([]);
            setData('department_id', '');
        }
    }, [data.branch_id]);


    return (
        <DialogContent className="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{t('Edit Training Type')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="name">{t('Name')}</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter training type name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter training type description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div>
                    <Label htmlFor="branch_id" required>{t('Branch')}</Label>
                    <Select value={data.branch_id} onValueChange={(value) => setData('branch_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select branch')} />
                        </SelectTrigger>
                        <SelectContent>
                            {branches.map((branch) => (
                                <SelectItem key={branch.id} value={branch.id.toString()}>
                                    {branch.branch_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.branch_id} />
                </div>

                <div>
                    <Label htmlFor="department_id" required>{t('Department')}</Label>
                    <Select
                        value={data.department_id}
                        onValueChange={(value) => setData('department_id', value)}
                        disabled={!data.branch_id}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.branch_id ? t('Select Department') : t('Select Branch first')} />
                        </SelectTrigger>
                        <SelectContent>
                            {filteredDepartments.map((department) => (
                                <SelectItem key={department.id} value={department.id.toString()}>
                                    {department.department_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.department_id} />
                </div>
                
                <div className="flex justify-end gap-2 pt-4">
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