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
import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';
import MediaPicker from '@/components/MediaPicker';
import { useFormFields } from '@/hooks/useFormFields';
import { CreateComplaintProps, CreateComplaintFormData } from './types';
import { usePage } from '@inertiajs/react';

export default function Create({ onSuccess }: CreateComplaintProps) {
    const { employees, allEmployees, complaintTypes } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateComplaintFormData>({
        employee_id: '',
        against_employee_id: '',
        complaint_type_id: '',
        subject: '',
        description: '',
        complaint_date: '',
        is_confidential: false,
        is_harassment_report: false,
        confidential_channel: '',
        confidentiality_level: 'internal',
        confidential_access_user_ids: [],
        handling_owner_id: '',
        investigation_started_at: '',
        investigation_closed_at: '',
        document: '',
    });

    // AI hooks for subject and description fields
    const subjectAI = useFormFields('aiField', data, setData, errors, 'create', 'subject', 'Subject', 'hrm', 'complaint');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'complaint');

    const filteredAgainstEmployees = allEmployees?.filter((emp: any) => 
        emp.id.toString() !== data.employee_id
    ) || [];
    const confidentialAccessOptions = allEmployees?.map((employee: any) => ({
        value: employee.id.toString(),
        label: employee.name,
    })) || [];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.complaints.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Complaint')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                    <Select value={data.employee_id} onValueChange={(value) => setData('employee_id', value)}>
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

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div className="md:col-span-2 flex flex-wrap items-center gap-4">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.is_confidential}
                                onChange={(e) => setData('is_confidential', e.target.checked)}
                            />
                            {t('Confidential Case')}
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.is_harassment_report}
                                onChange={(e) => setData('is_harassment_report', e.target.checked)}
                            />
                            {t('Harassment Report')}
                        </label>
                    </div>
                    <div>
                        <Label>{t('Confidentiality Level')}</Label>
                        <Select value={data.confidentiality_level} onValueChange={(value) => setData('confidentiality_level', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="internal">{t('Internal')}</SelectItem>
                                <SelectItem value="restricted">{t('Restricted')}</SelectItem>
                                <SelectItem value="anonymous">{t('Anonymous')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.confidentiality_level} />
                    </div>
                    <div>
                        <Label>{t('Confidential Channel')}</Label>
                        <Input
                            value={data.confidential_channel}
                            onChange={(e) => setData('confidential_channel', e.target.value)}
                            placeholder={t('e.g. hotline, email, manager')}
                        />
                        <InputError message={errors.confidential_channel} />
                    </div>
                    <div className="md:col-span-2">
                        <Label>{t('Additional Case Access')}</Label>
                        <MultiSelectEnhanced
                            options={confidentialAccessOptions}
                            value={data.confidential_access_user_ids}
                            onValueChange={(value) => setData('confidential_access_user_ids', value)}
                            placeholder={t('Select additional reviewers')}
                            searchable={true}
                        />
                        <InputError message={errors.confidential_access_user_ids} />
                    </div>
                    <div>
                        <Label>{t('Handling Owner')}</Label>
                        <Select value={data.handling_owner_id} onValueChange={(value) => setData('handling_owner_id', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Handling Owner')} />
                            </SelectTrigger>
                            <SelectContent searchable={true}>
                                {allEmployees?.map((employee: any) => (
                                    <SelectItem key={employee.id} value={employee.id.toString()}>
                                        {employee.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.handling_owner_id} />
                    </div>
                    <div>
                        <Label>{t('Investigation Started At')}</Label>
                        <Input
                            type="date"
                            value={data.investigation_started_at}
                            onChange={(e) => setData('investigation_started_at', e.target.value)}
                        />
                        <InputError message={errors.investigation_started_at} />
                    </div>
                    <div>
                        <Label>{t('Investigation Closed At')}</Label>
                        <Input
                            type="date"
                            value={data.investigation_closed_at}
                            onChange={(e) => setData('investigation_closed_at', e.target.value)}
                        />
                        <InputError message={errors.investigation_closed_at} />
                    </div>
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
