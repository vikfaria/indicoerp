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
import { EditComplaintProps, EditComplaintFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function EditComplaint({ complaint, onSuccess }: EditComplaintProps) {
    const { employees, allEmployees, complaintTypes } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditComplaintFormData>({
        employee_id: complaint.employee_id ? complaint.employee_id.toString() : '',
        against_employee_id: complaint.against_employee_id ? complaint.against_employee_id.toString() : '',
        complaint_type_id: complaint.complaint_type_id?.toString() || '',
        subject: complaint.subject || '',
        description: complaint.description || '',
        complaint_date: complaint.complaint_date || '',
        document: complaint.document || '',
    });

    // AI hooks for subject and description fields
    const subjectAI = useFormFields('aiField', data, setData, errors, 'edit', 'subject', 'Subject', 'hrm', 'complaint');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'edit', 'description', 'Description', 'hrm', 'complaint');

    const filteredAgainstEmployees = allEmployees?.filter((emp: any) => 
        emp.id.toString() !== data.employee_id
    ) || [];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.complaints.update', complaint.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Complaint')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id} onValueChange={(value) => setData('employee_id', value) } required>
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
                    <Label htmlFor="against_employee_id" required>{t('Against Employee')}</Label>
                    <Select value={data.against_employee_id} onValueChange={(value) => setData('against_employee_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Against Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredAgainstEmployees.map((employee: any) => (
                                <SelectItem key={employee.id} value={employee.id.toString()}>
                                    {employee.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.against_employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="complaint_type_id" required>{t('Complaint Type')}</Label>
                    <Select value={data.complaint_type_id} onValueChange={(value) => setData('complaint_type_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Complaint Type')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {complaintTypes?.map((type: any) => (
                                <SelectItem key={type.id} value={type.id.toString()}>
                                    {type.complaint_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.complaint_type_id} />
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
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description" required>{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter Description')}
                        rows={4}
                        required
                    />
                    <InputError message={errors.description} />
                </div>
                
                <div>
                    <Label required>{t('Complaint Date')}</Label>
                    <DatePicker
                        value={data.complaint_date}
                        onChange={(date) => setData('complaint_date', date)}
                        placeholder={t('Select Complaint Date')}
                        required
                    />
                    <InputError message={errors.complaint_date} />
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