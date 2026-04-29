import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { DatePicker } from '@/components/ui/date-picker';

export default function EditTask({ onSuccess, training, users, task }) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm({
        title: task.title || '',
        description: task.description || '',
        due_date: task.due_date || '',
        assigned_to: task.assigned_to?.toString() || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('training.trainings.tasks.update', [training.id, task.id]), {
            onSuccess: () => {
                onSuccess();
            },
        });
    };

    return (
        <DialogContent className="sm:max-w-1xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Task')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter task title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>                
                <div>
                    <Label required>{t('Due Date')}</Label>
                    <DatePicker
                        value={data.due_date}
                        onChange={(value) => setData('due_date', value)}
                        placeholder={t('Select due date')}
                        required
                    />
                    <InputError message={errors.due_date} />
                </div>

                <div>
                    <Label htmlFor="assigned_to" required>{t('Assign To')}</Label>
                    <Select value={data.assigned_to} onValueChange={(value) => setData('assigned_to', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select user')} />
                        </SelectTrigger>
                        <SelectContent>
                            {users?.map((user) => (
                                <SelectItem key={user.id} value={user.id.toString()}>
                                    {user.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.assigned_to} />
                </div>
                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter task description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
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