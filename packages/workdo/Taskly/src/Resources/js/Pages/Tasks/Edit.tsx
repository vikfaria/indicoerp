import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import InputError from '@/components/ui/input-error';
import { KanbanTask } from '@/components/kanban-board';
import { Project, Milestone, TaskStage } from './types';
import { useFormFields } from '@/hooks/useFormFields';
import axios from 'axios';
import { toast } from 'sonner';

interface EditProps {
    onSuccess: () => void;
    task: { id: number } | KanbanTask;
    project?: Project;
    milestones: Milestone[];
    teamMembers: Array<{
        id: number;
        name: string;
    }>;
    taskStages?: TaskStage[];
}

export default function Edit({ onSuccess, task, project, milestones, teamMembers, taskStages = [] }: EditProps) {
    const { t } = useTranslation();
    const [taskData, setTaskData] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);
    const [data, setData] = useState({
        title: '',
        priority: 'Medium',
        assigned_to: [] as string[],
        duration: '',
        description: '',
        stage_id: undefined as number | undefined,
    });

    const updateData = (key: string, value: any) => {
        setData(prev => ({ ...prev, [key]: value }));
    };

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, updateData, {}, 'edit', 'title', 'Title', 'taskly', 'task');
    const descriptionAI = useFormFields('aiField', data, updateData, {}, 'edit', 'description', 'Description', 'taskly', 'task');

    useEffect(() => {
        const fetchTaskData = async () => {
            try {
                const response = await axios.get(route('project.tasks.show', task.id));
                const fetchedTask = response.data.task;
                setTaskData(fetchedTask);

                // Get assigned user IDs
                const assignedIds = fetchedTask.assignedUsers?.map((user: any) => user.id.toString()) || [];

                setData({
                    title: fetchedTask.title || '',
                    priority: fetchedTask.priority || 'Medium',
                    assigned_to: assignedIds,
                    duration: fetchedTask.duration || '',
                    description: fetchedTask.description || '',
                    stage_id: fetchedTask.stage_id || (taskStages.length > 0 ? taskStages[0].id : undefined),
                });
            } catch (error) {
                toast.error(t('Failed to load task data'));
            } finally {
                setLoading(false);
            }
        };

        fetchTaskData();
    }, [task.id, taskStages]);

    if (loading) {
        return (
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Edit Task')}</DialogTitle>
                </DialogHeader>
                <div className="flex items-center justify-center py-8">
                    <p className="text-sm text-gray-500">{t('Loading task data...')}</p>
                </div>
            </DialogContent>
        );
    }

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        try {
            const response = await axios.put(route('project.tasks.update', task.id), data);
            if (response.data.message) {
                toast.success(t(response.data.message));
            }
            onSuccess();
        } catch (error: any) {
            if (error.response?.data?.errors) {
                Object.keys(error.response.data.errors).forEach(key => {
                    toast.error(error.response.data.errors[key][0]);
                });
            } else {
                toast.error(t('Failed to update task'));
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Task')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="project">{t('Project')}</Label>
                    <Input
                        id="project"
                        value={taskData?.project?.name || project?.name || ''}
                        disabled
                        className="bg-gray-50"
                        required
                    />
                </div>

                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="title">{t('Title')}</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => updateData('title', e.target.value)}
                                placeholder={t('Enter task title')}
                                required
                            />
                        </div>
                        {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>

                <div>
                    <Label htmlFor="priority">{t('Priority')}</Label>
                    <Select value={data.priority} onValueChange={(value) => updateData('priority', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="High">{t('High')}</SelectItem>
                            <SelectItem value="Medium">{t('Medium')}</SelectItem>
                            <SelectItem value="Low">{t('Low')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label htmlFor="stage_id">{t('Stage')}</Label>
                    <Select value={data.stage_id?.toString() || ''} onValueChange={(value) => updateData('stage_id', value ? parseInt(value) : undefined)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select stage')} />
                        </SelectTrigger>
                        <SelectContent>
                            {taskStages?.map((stage) => (
                                <SelectItem key={stage.id} value={stage.id.toString()}>
                                    {stage.name}
                                </SelectItem>
                            )) || []}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label required>{t('Assign To')}</Label>
                    <MultiSelectEnhanced
                        options={teamMembers.map(member => ({
                            value: member.id.toString(),
                            label: member.name
                        }))}
                        value={data.assigned_to || []}
                        onValueChange={(values) => updateData('assigned_to', values)}
                        placeholder={t('Select team members')}
                        searchable={true}
                    />
                </div>

                <div>
                    <Label required>{t('Duration')}</Label>
                    <DateRangePicker
                        value={data.duration || ''}
                        onChange={(value) => updateData('duration', value)}
                        placeholder={t('Select duration dates')}
                    />
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
                        rows={3}
                        value={data.description}
                        onChange={(e) => updateData('description', e.target.value)}
                        placeholder={t('Enter task description')}
                        required
                    />
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
