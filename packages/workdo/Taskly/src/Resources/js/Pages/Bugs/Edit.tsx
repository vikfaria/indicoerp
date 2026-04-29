import { useState, useEffect } from 'react';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
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

interface EditBugProps {
    onSuccess: () => void;
    bug: { id: number };
    teamMembers: Array<{ id: number; name: string; }>;
    bugStages: Array<{ id: number; name: string; }>;
}

export default function Edit({ onSuccess, bug, teamMembers, bugStages }: EditBugProps) {
    const { t } = useTranslation();
    const [data, setData] = useState({
        title: '',
        priority: 'Medium',
        assigned_to: [],
        stage_id: '',
        description: '',
    });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<any>({});
    const [loading, setLoading] = useState(true);

    const updateData = (key: string, value: any) => {
        setData(prev => ({ ...prev, [key]: value }));
    };

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, updateData, errors, 'edit', 'title', 'Title', 'taskly', 'bug');
    const descriptionAI = useFormFields('aiField', data, updateData, errors, 'edit', 'description', 'Description', 'taskly', 'bug');

    useEffect(() => {
        const fetchBug = async () => {
            try {
                const response = await axios.get(route('project.bugs.show', bug.id));
                const bugData = response.data.bug;
                // Get assigned user IDs
                const assignedIds = bugData.assignedUsers?.map((user: any) => user.id.toString()) || [];
                
                setData({
                    title: bugData.title,
                    priority: bugData.priority,
                    assigned_to: assignedIds.map(id => parseInt(id)),
                    stage_id: bugData.stage_id,
                    description: bugData.description || '',
                });
            } catch (error) {
                toast.error(t('Failed to load bug data'));
            } finally {
                setLoading(false);
            }
        };

        fetchBug();
    }, [bug.id]);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const response = await axios.put(route('project.bugs.update', bug.id), data);
            toast.success(t(response.data.message));
            onSuccess();
        } catch (error: any) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                toast.error(t('Failed to update bug.'));
            }
        } finally {
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Edit Bug')}</DialogTitle>
                </DialogHeader>
                <div className="flex items-center justify-center py-8">
                    <p className="text-sm text-gray-500">{t('Loading bug data...')}</p>
                </div>
            </DialogContent>
        );
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Bug')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="edit_title">{t('Title')}</Label>
                            <Input
                                id="edit_title"
                                value={data.title}
                                onChange={(e) => updateData('title', e.target.value)}
                                placeholder={t('Enter bug title')}
                                required
                            />
                            <InputError message={errors.title?.[0]} />
                        </div>
                        {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>

                <div>
                    <Label required>{t('Priority')}</Label>
                    <Select value={data.priority} onValueChange={(value) => setData({...data, priority: value})}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Low">{t('Low')}</SelectItem>
                            <SelectItem value="Medium">{t('Medium')}</SelectItem>
                            <SelectItem value="High">{t('High')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.priority?.[0]} />
                </div>

                <div>
                    <Label required>{t('Assign To')}</Label>
                    <MultiSelectEnhanced
                        options={teamMembers.map(member => ({ value: member.id.toString(), label: member.name }))}
                        value={data.assigned_to.map(id => id.toString())}
                        onValueChange={(value) => setData({...data, assigned_to: value.map(v => parseInt(v))})}
                        placeholder={t('Select team members')}
                        searchable={true}
                    />
                    <InputError message={errors.assigned_to?.[0]} />
                </div>

                <div>
                    <Label>{t('Status')}</Label>
                    <Select value={data.stage_id.toString()} onValueChange={(value) => setData({...data, stage_id: parseInt(value)})}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {bugStages.map((stage) => (
                                <SelectItem key={stage.id} value={stage.id.toString()}>{stage.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.stage_id?.[0]} />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="edit_description">{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="edit_description"
                        value={data.description}
                        onChange={(e) => updateData('description', e.target.value)}
                        placeholder={t('Enter bug description')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description?.[0]} />
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
