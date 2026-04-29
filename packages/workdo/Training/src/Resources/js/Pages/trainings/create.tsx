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
import { DatePicker } from '@/components/ui/date-picker';
import { useFormFields } from '@/hooks/useFormFields';
import { TrainingType, Trainer, Branch, Department, User } from './types';

interface CreateProps {
    onSuccess: () => void;
    trainingTypes: TrainingType[];
    trainers: Trainer[];
    branches: Branch[];
    departments: Department[];
    users: User[];
}

export default function Create({ onSuccess, trainingTypes, trainers, branches, departments, users }: CreateProps) {
    const { t } = useTranslation();
    const [filteredDepartments, setFilteredDepartments] = useState(departments || []);


    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        training_type_id: '',
        trainer_id: '',
        branch_id: '',
        department_id: '',
        start_date: '',
        end_date: '',
        start_time: '',
        end_time: '',
        location: '',
        max_participants: '',
        cost: '',
        status: 'scheduled',
    });

    // AI hooks for description field
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'training', 'training');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('training.trainings.store'), {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    useEffect(() => {
        if (data.branch_id && departments) {
            const branchDepartments = departments.filter(dept => dept.branch_id.toString() === data.branch_id);
            setFilteredDepartments(branchDepartments);
            if (data.department_id && !branchDepartments.find(dept => dept.id.toString() === data.department_id)) {
                setData('department_id', '');
            }
        } else {
            setFilteredDepartments([]);
            setData('department_id', '');
        }


    }, [data.branch_id, departments, users]);



    return (
        <DialogContent className="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>{t('Create Training List')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="title" required>{t('Title')}</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder={t('Enter training title')}
                            required
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div>
                        <Label htmlFor="training_type_id" required>{t('Training Type')}</Label>
                        <Select value={data.training_type_id} onValueChange={(value) => setData('training_type_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select training type')} />
                            </SelectTrigger>
                            <SelectContent>
                                {trainingTypes.map((type) => (
                                    <SelectItem key={type.id} value={type.id.toString()}>
                                        {type.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.training_type_id} />
                    </div>

                    <div>
                        <Label htmlFor="trainer_id" required>{t('Trainer')}</Label>
                        <Select value={data.trainer_id} onValueChange={(value) => setData('trainer_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select trainer')} />
                            </SelectTrigger>
                            <SelectContent>
                                {trainers.map((trainer) => (
                                    <SelectItem key={trainer.id} value={trainer.id.toString()}>
                                        {trainer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.trainer_id} />
                    </div>

                    <div>
                        <Label htmlFor="status" required>{t('Status')}</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="scheduled">{t('Scheduled')}</SelectItem>
                                <SelectItem value="ongoing">{t('Ongoing')}</SelectItem>
                                <SelectItem value="completed">{t('Completed')}</SelectItem>
                                <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
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

                    

                    <div>
                        <Label required>{t('Start Date')}</Label>
                        <DatePicker
                            value={data.start_date}
                            onChange={(value) => setData('start_date', value)}
                            placeholder={t('Select start date')}
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div>
                        <Label required>{t('End Date')}</Label>
                        <DatePicker
                            value={data.end_date}
                            onChange={(value) => setData('end_date', value)}
                            placeholder={t('Select end date')}
                        />
                        <InputError message={errors.end_date} />
                    </div>

                    <div>
                        <Label htmlFor="start_time" required>{t('Start Time')}</Label>
                        <Input
                            id="start_time"
                            type="time"
                            value={data.start_time}
                            onChange={(e) => setData('start_time', e.target.value)}
                            required
                        />
                        <InputError message={errors.start_time} />
                    </div>

                    <div>
                        <Label htmlFor="end_time" required>{t('End Time')}</Label>
                        <Input
                            id="end_time"
                            type="time"
                            value={data.end_time}
                            onChange={(e) => setData('end_time', e.target.value)}
                            required
                        />
                        <InputError message={errors.end_time} />
                    </div>

                    <div>
                        <Label htmlFor="max_participants">{t('Max Participants')}</Label>
                        <Input
                            id="max_participants"
                            type="number"
                            value={data.max_participants}
                            onChange={(e) => setData('max_participants', e.target.value)}
                            placeholder={t('Enter maximum participants')}
                            min="1"
                        />
                        <InputError message={errors.max_participants} />
                    </div>

                    <div>
                        <Label htmlFor="location">{t('Location')}</Label>
                        <Input
                            id="location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            placeholder={t('Enter training location')}
                        />
                        <InputError message={errors.location} />
                    </div>

                </div>
                    <div>
                        <Label htmlFor="cost">{t('Cost')}</Label>
                        <Input
                            id="cost"
                            type="number"
                            step="0.01"
                            value={data.cost}
                            onChange={(e) => setData('cost', e.target.value)}
                            placeholder={t('Enter training cost')}
                            min="0"
                        />
                        <InputError message={errors.cost} />
                    </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description">{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter training description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
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