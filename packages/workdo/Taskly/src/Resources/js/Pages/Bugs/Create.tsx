import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { MultiSelectEnhanced } from "@/components/ui/multi-select-enhanced";
import InputError from "@/components/ui/input-error";
import { useFormFields } from '@/hooks/useFormFields';
import axios from 'axios';
import { toast } from 'sonner';

interface CreateBugProps {
    onSuccess: () => void;
    project: { id: number; name: string; };
    teamMembers: Array<{ id: number; name: string; }>;
    bugStages: Array<{ id: number; name: string; }>;
    preSelectedStageId?: number;
}

export default function Create({ onSuccess, project, teamMembers, bugStages, preSelectedStageId }: CreateBugProps) {
    const { t } = useTranslation();
    const { data, setData, processing, errors, setError, clearErrors } = useForm({
        project_id: project.id,
        title: '',
        priority: 'Medium',
        assigned_to: [],
        stage_id: preSelectedStageId || (bugStages && bugStages.length > 0 ? bugStages[0].id : ''),
        description: '',
    });

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, setData, errors, 'create', 'title', 'Title', 'taskly', 'bug');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'taskly', 'bug');

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        clearErrors();

        try {
            const response = await axios.post(route('project.bugs.store'), data);
            toast.success(t(response.data.message));
            onSuccess();
        } catch (error: any) {
            if (error.response?.data?.errors) {
                Object.keys(error.response.data.errors).forEach(key => {
                    setError(key as any, error.response.data.errors[key][0]);
                });
            } else {
                toast.error(t('Failed to create bug.'));
            }
        }
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Bug')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="title">{t('Title')}</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder={t('Enter bug title')}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>
                        {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>

                <div>
                    <Label required>{t('Priority')}</Label>
                    <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Low">{t('Low')}</SelectItem>
                            <SelectItem value="Medium">{t('Medium')}</SelectItem>
                            <SelectItem value="High">{t('High')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.priority} />
                </div>

                <div>
                    <Label required>{t('Assign To')}</Label>
                    <MultiSelectEnhanced
                        options={teamMembers.map(member => ({ value: member.id.toString(), label: member.name }))}
                        value={data.assigned_to.map(id => id.toString())}
                        onValueChange={(value) => setData('assigned_to', value.map(v => parseInt(v)))}
                        placeholder={t('Select team members')}
                        searchable={true}
                    />
                    <InputError message={errors.assigned_to} />
                </div>

                <div>
                    <Label>{t('Status')}</Label>
                    <Select value={data.stage_id.toString()} onValueChange={(value) => setData('stage_id', parseInt(value))}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {bugStages && bugStages.map((stage) => (
                                <SelectItem key={stage.id} value={stage.id.toString()}>{stage.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.stage_id} />
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
                        placeholder={t('Enter bug description')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="flex justify-end gap-2">
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
