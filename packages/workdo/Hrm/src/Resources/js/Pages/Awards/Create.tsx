import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { useFormFields } from '@/hooks/useFormFields';
import { CreateAwardProps, CreateAwardFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function Create({ onSuccess }: CreateAwardProps) {
    const { employees, awardTypes } = usePage<any>().props;

    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateAwardFormData>({
        employee_id: '',
        award_type_id: '',
        award_date: '',
        description: '',
        certificate: '',
    });

    // AI hooks for description field
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'award');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.awards.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Award')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id} onValueChange={(value) => setData('employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {employees?.map((employee: any) => (
                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                    {employee.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="award_type_id" required>{t('Award Type')}</Label>
                    <Select value={data.award_type_id} onValueChange={(value) => setData('award_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Award Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {awardTypes?.map((type: any) => (
                                <SelectItem key={type.id} value={type.id.toString()}>
                                    {type.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.award_type_id} />
                </div>
                
                <div>
                    <Label htmlFor="award_date" required>{t('Award Date')}</Label>
                    <DatePicker
                        value={data.award_date}
                        onChange={(date) => setData('award_date', date)}
                        placeholder={t('Select Award Date')}
                        required
                    />
                    <InputError message={errors.award_date} />
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
                
                <div>
                    <MediaPicker
                        id="certificate"
                        label={t('Certificate')}
                        value={data.certificate}
                        onChange={(value) => setData('certificate', Array.isArray(value) ? value[0] || '' : value)}
                        placeholder={t('Select Certificate')}
                        showPreview={true}
                    />
                    <InputError message={errors.certificate} />
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