import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { DatePicker } from '@/components/ui/date-picker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { useFormFields } from '@/hooks/useFormFields';
import { EditAnnouncementProps, EditAnnouncementFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function EditAnnouncement({ announcement, onSuccess }: EditAnnouncementProps) {
    const { announcementcategories, departments } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditAnnouncementFormData>({
        title: announcement.title ?? '',
        announcement_category_id: announcement.announcement_category_id?.toString() || '',
        departments: announcement.departments?.map((dept: any) => dept.id.toString()) || [],
        priority: announcement.priority || 'low',
        status: announcement.status || 'draft',
        start_date: announcement.start_date || '',
        end_date: announcement.end_date || '',
        description: announcement.description ?? '',
    });

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, setData, errors, 'edit', 'title', 'Title', 'hrm', 'announcement');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'hrm', 'announcement');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.announcements.update', announcement.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Announcement')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="title">{t('Title')}</Label>
                            <Input
                                id="title"
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder={t('Enter Title')}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>
                        {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>
                
                <div>
                    <Label htmlFor="announcement_category_id" required>{t('Announcement Category')}</Label>
                    <Select value={data.announcement_category_id?.toString() || ''} onValueChange={(value) => setData('announcement_category_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Announcement Category')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {announcementcategories?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.announcement_category_id} />
                </div>
                
                <div>
                    <Label required>{t('Department')}</Label>
                    <MultiSelectEnhanced
                        options={departments?.map((dept: any) => ({ value: dept.id.toString(), label: dept.name })) || []}
                        value={data.departments}
                        onValueChange={(value) => setData('departments', value)}
                        placeholder={t('Select Departments')}
                        searchable={true}
                    />
                    <InputError message={errors.departments} />
                </div>
                
                <div>
                    <Label required>{t('Priority')}</Label>
                    <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Priority')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="low">{t('Low')}</SelectItem>
                            <SelectItem value="medium">{t('Medium')}</SelectItem>
                            <SelectItem value="high">{t('High')}</SelectItem>
                            <SelectItem value="urgent">{t('Urgent')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.priority} />
                </div>
                

                
                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                    />
                    <InputError message={errors.start_date} />
                </div>
                
                <div>
                    <Label required>{t('End Date')}</Label>
                    <DatePicker
                        value={data.end_date}
                        onChange={(date) => setData('end_date', date)}
                        placeholder={t('Select End Date')}
                    />
                    <InputError message={errors.end_date} />
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
                        placeholder={t('Enter Description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
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