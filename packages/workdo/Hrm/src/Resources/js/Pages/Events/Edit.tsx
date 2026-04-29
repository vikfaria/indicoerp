import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import { useFormFields } from '@/hooks/useFormFields';
import { EditEventProps, EditEventFormData } from './types';
import { usePage } from '@inertiajs/react';



export default function EditEvent({ event, onSuccess }: EditEventProps) {
    const { eventtypes, users, departments } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditEventFormData>({
        title: event.title ?? '',
        event_type_id: event.event_type_id ?? '',
        start_date: event.start_date || '',
        end_date: event.end_date || '',
        start_time: event.start_time ? event.start_time.substring(0, 5) : '',
        end_time: event.end_time ? event.end_time.substring(0, 5) : '',
        location: event.location ?? '',
        description: event.description ?? '',
        departments: event.departments?.map((dept: any) => dept.id.toString()) || [],
        color: event.color ?? '#3b82f6',
    });

    // AI hooks for title and description fields
    const titleAI = useFormFields('aiField', data, setData, errors, 'edit', 'title', 'Title', 'hrm', 'event');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'hrm', 'event');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.events.update', event.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Event')}</DialogTitle>
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
                    <Label htmlFor="event_type_id" required>{t('Event Type')}</Label>
                    <Select value={data.event_type_id?.toString() || ''} onValueChange={(value) => setData('event_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Event Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {eventtypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.event_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.event_type_id} />
                </div>

                <div>
                    <Label required>{t('Departments')}</Label>
                    <MultiSelectEnhanced
                        options={departments?.map((dept: any) => ({
                            value: dept.id.toString(),
                            label: `${dept.department_name} (${dept.branch?.branch_name || 'No Branch'})`
                        })) || []}
                        value={data.departments}
                        onValueChange={(value) => setData('departments', value)}
                        placeholder={t('Select Departments')}
                        searchable={true}
                    />
                    <InputError message={errors.departments} />
                </div>

                <div>
                    <Label required>{t('Start Date')}</Label>
                    <DatePicker
                        value={data.start_date}
                        onChange={(date) => setData('start_date', date)}
                        placeholder={t('Select Start Date')}
                        required
                    />
                    <InputError message={errors.start_date} />
                </div>

                <div>
                    <Label required>{t('End Date')}</Label>
                    <DatePicker
                        value={data.end_date}
                        onChange={(date) => setData('end_date', date)}
                        placeholder={t('Select End Date')}
                        required
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
                    <Label htmlFor="location" required>{t('Location')}</Label>
                    <Input
                        id="location"
                        type="text"
                        value={data.location}
                        onChange={(e) => setData('location', e.target.value)}
                        placeholder={t('Enter Location')}
                        required
                    />
                    <InputError message={errors.location} />
                </div>

                <div>
                    <Label htmlFor="color" required>{t('Color')}</Label>
                    <div className="flex gap-2 mt-1">
                        <Input
                            id="color"
                            type="color"
                            value={data.color}
                            onChange={(e) => setData('color', e.target.value)}
                            className="w-16 h-10 p-1 border rounded"
                        />
                        <Input
                            type="text"
                            value={data.color}
                            onChange={(e) => setData('color', e.target.value)}
                            className="flex-1"
                            placeholder="#3b82f6"
                        />
                    </div>
                    <InputError message={errors.color} />
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