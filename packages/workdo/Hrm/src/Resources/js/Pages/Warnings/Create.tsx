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
import { CreateWarningProps, CreateWarningFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Create({ onSuccess }: CreateWarningProps) {
    const { users, allUsers, warningtypes } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateWarningFormData>({
        subject: '',
        severity: 'Minor',
        warning_date: '',
        note_of_culpa_issued_at: '',
        note_of_culpa_delivered_at: '',
        worker_refused_note_of_culpa: false,
        refusal_witness_one_name: '',
        refusal_witness_two_name: '',
        response_deadline_at: '',
        decision_deadline_at: '',
        disciplinary_sanction: '',
        disciplinary_decision_at: '',
        description: '',
        document: '',
        employee_id: '',
        warning_by: '',
        warning_type_id: '',
    });

    // AI hooks for subject and description fields
    const subjectAI = useFormFields('aiField', data, setData, errors, 'create', 'subject', 'Subject', 'hrm', 'warning');
    const descriptionAI = useFormFields('aiField', data, setData, errors, 'create', 'description', 'Description', 'hrm', 'warning');

    const filteredWarningBies = allUsers?.filter((user: any) => user.id.toString() !== data.employee_id) || [];

    useEffect(() => {
        if (data.employee_id && data.warning_by === data.employee_id) {
            setData('warning_by', '');
        }
    }, [data.employee_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.warnings.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Warning')}</DialogTitle>
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

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <Label>{t('Note of Charge Issued At')}</Label>
                        <Input
                            type="date"
                            value={data.note_of_culpa_issued_at}
                            onChange={(e) => setData('note_of_culpa_issued_at', e.target.value)}
                        />
                        <InputError message={errors.note_of_culpa_issued_at} />
                    </div>
                    <div>
                        <Label>{t('Note of Charge Delivered At')}</Label>
                        <Input
                            type="date"
                            value={data.note_of_culpa_delivered_at}
                            onChange={(e) => setData('note_of_culpa_delivered_at', e.target.value)}
                        />
                        <InputError message={errors.note_of_culpa_delivered_at} />
                    </div>
                    <div className="md:col-span-2">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.worker_refused_note_of_culpa}
                                onChange={(e) => setData('worker_refused_note_of_culpa', e.target.checked)}
                            />
                            {t('Employee refused to sign note of charge')}
                        </label>
                        <InputError message={errors.worker_refused_note_of_culpa} />
                    </div>
                    {data.worker_refused_note_of_culpa && (
                        <>
                            <div>
                                <Label>{t('Witness 1 Name')}</Label>
                                <Input
                                    value={data.refusal_witness_one_name}
                                    onChange={(e) => setData('refusal_witness_one_name', e.target.value)}
                                />
                                <InputError message={errors.refusal_witness_one_name} />
                            </div>
                            <div>
                                <Label>{t('Witness 2 Name')}</Label>
                                <Input
                                    value={data.refusal_witness_two_name}
                                    onChange={(e) => setData('refusal_witness_two_name', e.target.value)}
                                />
                                <InputError message={errors.refusal_witness_two_name} />
                            </div>
                        </>
                    )}
                    <div>
                        <Label>{t('Response Deadline')}</Label>
                        <Input
                            type="date"
                            value={data.response_deadline_at}
                            onChange={(e) => setData('response_deadline_at', e.target.value)}
                        />
                        <InputError message={errors.response_deadline_at} />
                    </div>
                    <div>
                        <Label>{t('Decision Deadline')}</Label>
                        <Input
                            type="date"
                            value={data.decision_deadline_at}
                            onChange={(e) => setData('decision_deadline_at', e.target.value)}
                        />
                        <InputError message={errors.decision_deadline_at} />
                    </div>
                    <div>
                        <Label>{t('Disciplinary Sanction')}</Label>
                        <Select value={data.disciplinary_sanction || ''} onValueChange={(value) => setData('disciplinary_sanction', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Sanction')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="warning">{t('Warning')}</SelectItem>
                                <SelectItem value="reprimand">{t('Reprimand')}</SelectItem>
                                <SelectItem value="suspension">{t('Suspension')}</SelectItem>
                                <SelectItem value="demotion">{t('Demotion')}</SelectItem>
                                <SelectItem value="dismissal">{t('Dismissal')}</SelectItem>
                                <SelectItem value="archived">{t('Archived')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.disciplinary_sanction} />
                    </div>
                    <div>
                        <Label>{t('Disciplinary Decision Date')}</Label>
                        <Input
                            type="date"
                            value={data.disciplinary_decision_at}
                            onChange={(e) => setData('disciplinary_decision_at', e.target.value)}
                        />
                        <InputError message={errors.disciplinary_decision_at} />
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
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
