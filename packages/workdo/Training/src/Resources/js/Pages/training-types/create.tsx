import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/ui/input-error';
import { Branch, Department } from './types';

interface CreateProps {
    onSuccess: () => void;
    branches: Branch[];
    departments: Department[];
}

const complianceCodeOptions = [
    { value: 'safety_health', label: 'Safety and Health' },
    { value: 'equipment_usage', label: 'Equipment Usage' },
    { value: 'conduct_harassment', label: 'Conduct and Harassment' },
    { value: 'compliance', label: 'Compliance' },
    { value: 'data_protection', label: 'Data Protection' },
    { value: 'onboarding', label: 'Onboarding' },
];

export default function Create({ onSuccess, branches, departments }: CreateProps) {
    const { t } = useTranslation();
    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        branch_id: '',
        department_id: '',
        is_mandatory: false,
        compliance_code: '',
        certificate_validity_days: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('training.training-types.store'), {
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
                <DialogTitle>{t('Create Training Type')}</DialogTitle>
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

                <div className="space-y-2">
                    <div className="flex items-center justify-between rounded-md border p-3">
                        <div>
                            <Label htmlFor="is_mandatory">{t('Mandatory compliance training')}</Label>
                            <p className="text-xs text-muted-foreground">
                                {t('Enable this when training is legally required and monitored in compliance alerts.')}
                            </p>
                        </div>
                        <Switch
                            id="is_mandatory"
                            checked={Boolean(data.is_mandatory)}
                            onCheckedChange={(checked) => {
                                setData('is_mandatory', checked);
                                if (!checked) {
                                    setData('compliance_code', '');
                                    setData('certificate_validity_days', '');
                                }
                            }}
                        />
                    </div>
                    <InputError message={errors.is_mandatory} />
                </div>

                {data.is_mandatory && (
                    <>
                        <div>
                            <Label htmlFor="compliance_code" required>{t('Compliance category')}</Label>
                            <Select value={data.compliance_code} onValueChange={(value) => setData('compliance_code', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select compliance category')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {complianceCodeOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {t(option.label)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.compliance_code} />
                        </div>

                        <div>
                            <Label htmlFor="certificate_validity_days">{t('Certificate validity (days)')}</Label>
                            <Input
                                id="certificate_validity_days"
                                type="number"
                                min={1}
                                max={3650}
                                value={data.certificate_validity_days}
                                onChange={(e) => setData('certificate_validity_days', e.target.value)}
                                placeholder={t('Leave empty for non-expiring mandatory training')}
                            />
                            <InputError message={errors.certificate_validity_days} />
                        </div>
                    </>
                )}

                <div className="flex justify-end gap-2 pt-4">
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
