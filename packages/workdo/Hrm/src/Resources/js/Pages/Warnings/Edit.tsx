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
import { EditWarningProps, EditWarningFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function EditWarning({ warning, onSuccess }: EditWarningProps) {
    const { users, allUsers, warningtypes } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditWarningFormData>({
        subject: warning.subject ?? '',
        severity: warning.severity ?? 'Minor',
        warning_date: warning.warning_date || '',
        description: warning.description ?? '',
        document: warning.document || '',
        employee_id: warning.employee_id?.toString() || '',
        warning_by: (warning.warning_by?.id || warning.warning_by)?.toString() || '',
        warning_type_id: warning.warning_type_id?.toString() || '',
    });

    // AI hooks for subject and description fields
    const subjectAI = useFormFields('aiField', data, setData, errors, 'edit', 'subject', 'Subject', 'hrm', 'warning');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'hrm', 'warning');

    const filteredWarningBies = allUsers?.filter((user: any) => 
        user.id.toString() !== data.employee_id || user.id.toString() === data.warning_by
    ) || [];

    useEffect(() => {
        if (data.employee_id && data.warning_by === data.employee_id && data.warning_by !== (warning.warning_by?.id || warning.warning_by)?.toString()) {
            setData('warning_by', '');
        }
    }, [data.employee_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.warnings.update', warning.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Warning')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id?.toString() || ''} onValueChange={(value) => setData('employee_id', value)} required> 
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {users.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="warning_by" required>{t('Warning By')}</Label>
                    <Select 
                        value={data.warning_by?.toString() || ''} 
                        onValueChange={(value) => setData('warning_by', value)}
                        disabled={!data.employee_id}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.employee_id ? t('Select Warningby') : t('Select Employee first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredWarningBies.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.warning_by} />
                </div>
                
                <div>
                    <Label htmlFor="warning_type_id" required>{t('Warning Type')}</Label>
                    <Select 
                        value={data.warning_type_id?.toString() || ''} 
                        onValueChange={(value) => setData('warning_type_id', value)}
                        disabled={!data.warning_by}
                        required
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.warning_by ? t('Select Warningtype') : t('Select Warningby first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {warningtypes?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.warning_type_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.warning_type_id} />
                </div>
                
                <div>
                    <div className="flex gap-2 items-end">
                        <div className="flex-1">
                            <Label htmlFor="subject" required>{t('Subject')}</Label>
                            <Input
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                placeholder={t('Enter Subject')}
                                required
                            />
                            <InputError message={errors.subject} />
                        </div>
                        {subjectAI.map(field => <div key={field.id}>{field.component}</div>)}
                    </div>
                </div>
                
                <div>
                    <Label htmlFor="severity" required>{t('Severity')}</Label>
                    <Select value={data.severity || 'Minor'} onValueChange={(value) => setData('severity', value)} required>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            <SelectItem value="Minor">{t('Minor')}</SelectItem>
                            <SelectItem value="Moderate">{t('Moderate')}</SelectItem>
                            <SelectItem value="Major">{t('Major')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.severity} />
                </div>
                
                <div>
                    <Label required>{t('Warning Date')}</Label>
                    <DatePicker
                        value={data.warning_date}
                        onChange={(date) => setData('warning_date', date)}
                        placeholder={t('Select Warning Date')}
                        required
                    />
                    <InputError message={errors.warning_date} />
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
                        label={t('Document')}
                        value={data.document}
                        onChange={(value) => setData('document', Array.isArray(value) ? value[0] || '' : value)}
                        placeholder={t('Select Document...')}
                        showPreview={true}
                        multiple={false}
                    />
                    <InputError message={errors.document} />
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