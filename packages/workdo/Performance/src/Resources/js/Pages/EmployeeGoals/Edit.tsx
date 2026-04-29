import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DatePicker } from "@/components/ui/date-picker";
import InputError from "@/components/ui/input-error";

interface EmployeeGoal {
    id: number;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    target: string;
    progress: number;
    status: string;
    employee_id: number;
    goal_type_id: number;
}

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface GoalType {
    id: number;
    name: string;
}

interface EditProps {
    goal: EmployeeGoal;
    onSuccess: () => void;
    employees: Employee[];
    goalTypes: GoalType[];
}

interface FormData {
    employee_id: string;
    goal_type_id: string;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    target: string;
    progress: string;
    status: string;
}

export default function Edit({ goal, onSuccess, employees, goalTypes }: EditProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<FormData>({
        employee_id: goal.employee_id?.toString() || '',
        goal_type_id: goal.goal_type_id?.toString() || '',
        title: goal.title || '',
        description: goal.description || '',
        start_date: goal.start_date || '',
        end_date: goal.end_date || '',
        target: goal.target || '',
        progress: goal.progress?.toString() || '0',
        status: goal.status || 'not_started',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('performance.employee-goals.update', goal.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Employee Goal')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="employee_id" required>{t('Employee')}</Label>
                        <Select value={data.employee_id} onValueChange={(value) => setData('employee_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select employee')} />
                            </SelectTrigger>
                            <SelectContent searchable={true}>
                                {employees && employees.length > 0 ? (
                                    employees.map((employee) => (
                                        <SelectItem key={employee.id} value={employee.id?.toString() || ''}>
                                            {employee.name}
                                        </SelectItem>
                                    ))
                                ) : (
                                    <SelectItem value="no-employees" disabled>
                                        {t('No employees available')}
                                    </SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.employee_id} />
                    </div>
                    
                    <div>
                        <Label htmlFor="goal_type_id" required>{t('Goal Type')}</Label>
                        <Select value={data.goal_type_id} onValueChange={(value) => setData('goal_type_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select goal type')} />
                            </SelectTrigger>
                            <SelectContent>
                                {goalTypes && goalTypes.length > 0 ? (
                                    goalTypes.map((type) => (
                                        <SelectItem key={type.id} value={type.id.toString()}>
                                            {type.name}
                                        </SelectItem>
                                    ))
                                ) : (
                                    <SelectItem value="no-goal-types" disabled>
                                        {t('No goal types available')}
                                    </SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.goal_type_id} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter goal title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label htmlFor="description" required>{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter goal description')}
                        rows={3}
                        required
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label required>{t('Start Date')}</Label>
                        <DatePicker
                            value={data.start_date}
                            onChange={(value) => setData('start_date', value)}
                            placeholder={t('Select start date')}
                        />
                        <InputError message={errors.start_date} />
                    </div>
                    
                    <div>
                        <Label required>{t('End Date')}</Label>
                        <DatePicker
                            value={data.end_date}
                            onChange={(value) => setData('end_date', value)}
                            placeholder={t('Select end date')}
                        />
                        <InputError message={errors.end_date} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label htmlFor="target" required>{t('Target')}</Label>
                        <Input
                            id="target"
                            value={data.target}
                            onChange={(e) => setData('target', e.target.value)}
                            placeholder={t('Enter target value')}
                            required
                        />
                        <InputError message={errors.target} />
                    </div>
                    
                    <div>
                        <Label htmlFor="progress">{t('Progress (%)')}</Label>
                        <Input
                            id="progress"
                            type="number"
                            min="0"
                            max="100"
                            value={data.progress}
                            onChange={(e) => setData('progress', e.target.value)}
                            placeholder="0"
                        />
                        <InputError message={errors.progress} />
                    </div>

                    <div>
                        <Label htmlFor="status">{t('Status')}</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="not_started">{t('Not Started')}</SelectItem>
                                <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                <SelectItem value="completed">{t('Completed')}</SelectItem>
                                <SelectItem value="overdue">{t('Overdue')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>
                </div>
                
                <div className="flex justify-end gap-2 pt-4">
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