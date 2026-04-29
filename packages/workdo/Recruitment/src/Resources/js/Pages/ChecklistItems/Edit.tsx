import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { EditChecklistItemProps, EditChecklistItemFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function EditChecklistItem({ checklistitem, onSuccess }: EditChecklistItemProps) {
    const { onboardingchecklists } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditChecklistItemFormData>({
        checklist_id: checklistitem.checklist_id?.toString() || '',
        task_name: checklistitem.task_name ?? '',
        description: checklistitem.description ?? '',
        category: checklistitem.category ?? '',
        assigned_to_role: checklistitem.assigned_to_role ?? '',
        due_day: checklistitem.due_day ?? '',
        is_required: checklistitem.is_required ?? false,
        status: checklistitem.status ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('recruitment.checklist-items.update', checklistitem.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Checklist Item')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="checklist_id">{t('Checklist')}</Label>
                    <Select value={data.checklist_id?.toString() || ''} onValueChange={(value) => setData('checklist_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Checklist')} />
                        </SelectTrigger>
                        <SelectContent>
                            {onboardingchecklists.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.checklist_id} />
                    {(!onboardingchecklists || onboardingchecklists.length === 0) && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Create checklist here. ')}
                            <a
                                href={route('recruitment.onboarding-checklists.index')}
                                className="text-blue-600 hover:text-blue-800 cursor-pointer"
                            >
                                {t('checklist')}
                            </a>.
                        </p>
                    )}
                </div>

                <div>
                    <Label htmlFor="task_name">{t('Task Name')}</Label>
                    <Input
                        id="task_name"
                        type="text"
                        value={data.task_name}
                        onChange={(e) => setData('task_name', e.target.value)}
                        placeholder={t('Enter Task Name')}
                        required
                    />
                    <InputError message={errors.task_name} />
                </div>

                <div>
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div>
                    <Label htmlFor="category">{t('Category')}</Label>
                    <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Category')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Documentation">{t('Documentation')}</SelectItem>
                            <SelectItem value="IT Setup">{t('IT Setup')}</SelectItem>
                            <SelectItem value="Training">{t('Training')}</SelectItem>
                            <SelectItem value="HR">{t('HR')}</SelectItem>
                            <SelectItem value="Facilities">{t('Facilities')}</SelectItem>
                            <SelectItem value="Other">{t('Other')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category} />
                </div>

                <div>
                    <Label htmlFor="assigned_to_role">{t('Assigned To Role')}</Label>
                    <Input
                        id="assigned_to_role"
                        type="text"
                        value={data.assigned_to_role}
                        onChange={(e) => setData('assigned_to_role', e.target.value)}
                        placeholder={t('Enter Assigned To Role')}
                    />
                    <InputError message={errors.assigned_to_role} />
                </div>

                <div>
                    <Label htmlFor="due_day">{t('Due Day')}</Label>
                    <Input
                        id="due_day"
                        type="number"
                        step="1"
                        min="1"
                        value={data.due_day}
                        onChange={(e) => setData('due_day', e.target.value)}
                        placeholder={t('Enter Due Day')}
                    />
                    <InputError message={errors.due_day} />
                </div>

                <div className="flex items-center space-x-2">
                    <Switch
                        id="is_required"
                        checked={data.is_required || false}
                        onCheckedChange={(checked) => setData('is_required', !!checked)}
                    />
                    <Label htmlFor="is_required" className="cursor-pointer">{t('Required Task')}</Label>
                    <InputError message={errors.is_required} />
                </div>

                <div>
                    <Label htmlFor="status">{t('Status')}</Label>
                    <Select value={data.status?.toString()} onValueChange={(value) => setData('status', value === 'true')}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="true">{t('Active')}</SelectItem>
                            <SelectItem value="false">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div className="flex justify-end gap-2">
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