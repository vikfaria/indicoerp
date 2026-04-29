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
import InputError from '@/components/ui/input-error';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { Branch, Department } from './types';

interface CreateProps {
    onSuccess: () => void;
    branches: Branch[];
    departments: Department[];
}

export default function Create({ onSuccess, branches, departments }: CreateProps) {
    const { t } = useTranslation();
    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        contact: '',
        email: '',
        experience: '',
        branch_id: '',
        department_id: '',
        expertise: '',
        qualification: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('training.trainers.store'), {
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
        <DialogContent className="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Create Trainer')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="name" required>{t('Name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('Enter trainer name')}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <PhoneInputComponent
                            label={t('Contact')}
                            value={data.contact}
                            onChange={(value) => setData('contact', value)}
                            placeholder="+1234567890"
                            required
                            error={errors.contact}
                        />
                    </div>

                    <div>
                        <Label htmlFor="email" required>{t('Email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={t('Enter email address')}
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <Label htmlFor="experience" required>{t('Experience')}</Label>
                        <Input
                            id="experience"
                            value={data.experience}
                            onChange={(e) => setData('experience', e.target.value)}
                            placeholder={t('Enter experience (e.g., 5 years)')}
                            required
                        />
                        <InputError message={errors.experience} />
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
                </div>

                <div>
                    <Label htmlFor="expertise">{t('Expertise')}</Label>
                    <Textarea
                        id="expertise"
                        value={data.expertise}
                        onChange={(e) => setData('expertise', e.target.value)}
                        placeholder={t('Enter areas of expertise')}
                        rows={2}
                    />
                    <InputError message={errors.expertise} />
                </div>

                <div>
                    <Label htmlFor="qualification">{t('Qualification')}</Label>
                    <Textarea
                        id="qualification"
                        value={data.qualification}
                        onChange={(e) => setData('qualification', e.target.value)}
                        placeholder={t('Enter educational qualifications')}
                        rows={2}
                    />
                    <InputError message={errors.qualification} />
                </div>                

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