import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { useFormFields } from '@/hooks/useFormFields';
import { EditPromotionProps, EditPromotionFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function EditPromotion({ promotion, onSuccess }: EditPromotionProps) {
    const { employees, branches, departments, designations } = usePage<any>().props;
    const [filteredDepartments, setFilteredDepartments] = useState([]);
    const [filteredDesignations, setFilteredDesignations] = useState([]);

    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditPromotionFormData>({
        employee_id: promotion.employee_id?.toString() ?? '',
        current_branch_id: promotion.current_branch_id?.toString() ?? '',
        current_department_id: promotion.current_department_id?.toString() ?? '',
        current_designation_id: promotion.current_designation_id?.toString() ?? '',
        effective_date: promotion.effective_date ?? '',
        reason: promotion.reason ?? '',
        document: promotion.document ?? '',

    });

    // AI hooks for reason field
    const reasonAI = useFormFields('aiField', data, setData, errors, 'edit', 'reason', 'Reason', 'hrm', 'promotion');

    useEffect(() => {
        if (data.current_branch_id) {
            const branchDepartments = departments.filter(dept => dept.branch_id.toString() === data.current_branch_id);
            setFilteredDepartments(branchDepartments);
            if (data.current_department_id && !branchDepartments.find(dept => dept.id.toString() === data.current_department_id)) {
                setData('current_department_id', '');
                setData('current_designation_id', '');
            }
        } else {
            setFilteredDepartments([]);
            setData('current_department_id', '');
            setData('current_designation_id', '');
        }
    }, [data.current_branch_id]);

    useEffect(() => {
        if (data.current_department_id) {
            const departmentDesignations = designations.filter(desig => desig.department_id.toString() === data.current_department_id);
            setFilteredDesignations(departmentDesignations);
            if (data.current_designation_id && !departmentDesignations.find(desig => desig.id.toString() === data.current_designation_id)) {
                setData('current_designation_id', '');
            }
        } else {
            setFilteredDesignations([]);
            setData('current_designation_id', '');
        }
    }, [data.current_department_id]);



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('hrm.promotions.update', promotion.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Promotion')}</DialogTitle>
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
                    <Label htmlFor="current_branch_id" required>{t('Current Branch')}</Label>
                    <Select value={data.current_branch_id} onValueChange={(value) => setData('current_branch_id', value)} required>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Current Branch')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {branches?.map((branch: any) => (
                                <SelectItem key={branch.id} value={branch.id.toString()}>
                                    {branch.branch_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.current_branch_id} />
                </div>
                
                <div>
                    <Label htmlFor="current_department_id" required>{t('Current Department')}</Label>
                    <Select value={data.current_department_id} onValueChange={(value) => setData('current_department_id', value)} disabled={!data.current_branch_id} required>
                        <SelectTrigger>
                            <SelectValue placeholder={data.current_branch_id ? t('Select Current Department') : t('Select Branch first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredDepartments?.map((department: any) => (
                                <SelectItem key={department.id} value={department.id.toString()}>
                                    {department.department_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.current_department_id} />
                </div>
                
                <div>
                    <Label htmlFor="current_designation_id" required>{t('Current Designation')}</Label>
                    <Select value={data.current_designation_id} onValueChange={(value) => setData('current_designation_id', value)} disabled={!data.current_department_id} required>
                        <SelectTrigger>
                            <SelectValue placeholder={data.current_department_id ? t('Select Current Designation') : t('Select Department first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredDesignations?.map((designation: any) => (
                                <SelectItem key={designation.id} value={designation.id.toString()}>
                                    {designation.designation_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.current_designation_id} />
                </div>
                
                <div>
                    <Label htmlFor="effective_date" required>{t('Effective Date')}</Label>
                    <DatePicker
                        value={data.effective_date}
                        onChange={(date) => setData('effective_date', date)}
                        placeholder={t('Select Effective Date')}
                        required
                    />
                    <InputError message={errors.effective_date} />
                </div>
                
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="reason">{t('Reason')}</Label>
                        <div className="flex gap-2">
                            {reasonAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <Textarea
                        id="reason"
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        placeholder={t('Enter Reason')}
                        rows={3}
                    />
                    <InputError message={errors.reason} />
                </div>
                
                <div>
                    <MediaPicker
                        id="document"
                        label={t('Document')}
                        value={data.document}
                        onChange={(value) => setData('document', Array.isArray(value) ? value[0] || '' : value)}
                        placeholder={t('Select Document')}
                        showPreview={true}
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