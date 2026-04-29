import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import MediaPicker from '@/components/MediaPicker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CreateEmployeeTransferProps, CreateEmployeeTransferFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Create({ onSuccess }: CreateEmployeeTransferProps) {
    const { employees, branches, departments, designations } = usePage<any>().props;
    const [filteredToDepartments, setFilteredToDepartments] = useState<any[]>([]);
    const [filteredToDesignations, setFilteredToDesignations] = useState<any[]>([]);
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateEmployeeTransferFormData>({
        employee_id: '',
        to_branch_id: '',
        to_department_id: '',
        to_designation_id: '',
        effective_date: '',
        reason: '',
        document: '',
    });

    // Handle dependent dropdowns
    useEffect(() => {
        if (data.to_branch_id) {
            const branchDepartments = departments.filter((dept: any) => dept.branch_id.toString() === data.to_branch_id);
            setFilteredToDepartments(branchDepartments);
            // Clear department if it doesn't belong to selected branch
            if (data.to_department_id) {
                const departmentExists = branchDepartments.find((dept: any) => dept.id.toString() === data.to_department_id);
                if (!departmentExists) {
                    setData('to_department_id', '');
                    setData('to_designation_id', '');
                }
            }
        } else {
            setFilteredToDepartments([]);
            setData('to_department_id', '');
            setData('to_designation_id', '');
        }
    }, [data.to_branch_id]);

    useEffect(() => {
        if (data.to_department_id) {
            const departmentDesignations = designations.filter((desig: any) => desig.department_id.toString() === data.to_department_id);
            setFilteredToDesignations(departmentDesignations);
            // Clear designation if it doesn't belong to selected department
            if (data.to_designation_id) {
                const designationExists = departmentDesignations.find((desig: any) => desig.id.toString() === data.to_designation_id);
                if (!designationExists) {
                    setData('to_designation_id', '');
                }
            }
        } else {
            setFilteredToDesignations([]);
            setData('to_designation_id', '');
        }
    }, [data.to_department_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hrm.employee-transfers.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Employee Transfer')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="employee_id">{t('Employee')} <span className="text-red-500">*</span></Label>
                    <Select value={data.employee_id?.toString() || ''} onValueChange={(value) => setData('employee_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Employee')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {employees?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.employee_id} />
                </div>
                
                <div>
                    <Label htmlFor="to_branch_id">{t('To Branch')} <span className="text-red-500">*</span></Label>
                    <Select value={data.to_branch_id?.toString() || ''} onValueChange={(value) => setData('to_branch_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select To Branch')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {branches?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.branch_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_branch_id} />
                </div>
                
                <div>
                    <Label htmlFor="to_department_id">{t('To Department')} <span className="text-red-500">*</span></Label>
                    <Select 
                        value={data.to_department_id?.toString() || ''} 
                        onValueChange={(value) => setData('to_department_id', value)}
                        disabled={!data.to_branch_id}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.to_branch_id ? t('Select To Department') : t('Select To Branch first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredToDepartments?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.department_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_department_id} />
                </div>
                
                <div>
                    <Label htmlFor="to_designation_id">{t('To Designation')} <span className="text-red-500">*</span></Label>
                    <Select 
                        value={data.to_designation_id?.toString() || ''} 
                        onValueChange={(value) => setData('to_designation_id', value)}
                        disabled={!data.to_department_id}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.to_department_id ? t('Select To Designation') : t('Select To Department first')} />
                        </SelectTrigger>
                        <SelectContent searchable={true}>
                            {filteredToDesignations?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.designation_name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_designation_id} />
                </div>
                
                <div>
                    <Label>{t('Effective Date')} <span className="text-red-500">*</span></Label>
                    <DatePicker
                        value={data.effective_date}
                        onChange={(date) => setData('effective_date', date)}
                        placeholder={t('Select Effective Date')}
                    />
                    <InputError message={errors.effective_date} />
                </div>
                
                <div>
                    <Label htmlFor="reason" required>{t('Reason')}</Label>
                    <Textarea
                        id="reason"
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        placeholder={t('Enter Reason')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.reason} />
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