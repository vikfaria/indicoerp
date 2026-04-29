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

interface EmployeeReview {
    id: number;
    user_id: number;
    reviewer_id: number;
    review_cycle_id: number;
    review_date: string;

    comments: string;
    status: string;
}

interface User {
    id: number;
    name: string;
}

interface ReviewCycle {
    id: number;
    name: string;
}

interface EditProps {
    employeeReview: EmployeeReview;
    onSuccess: () => void;
    employees: User[];
    reviewers: User[];
    reviewCycles: ReviewCycle[];
}

interface FormData {
    user_id: string;
    reviewer_id: string;
    review_cycle_id: string;
    review_date: string;

    comments: string;
    status: string;
}

export default function Edit({ employeeReview, onSuccess, employees, reviewers, reviewCycles }: EditProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<FormData>({
        user_id: employeeReview.user_id.toString(),
        reviewer_id: employeeReview.reviewer_id.toString(),
        review_cycle_id: employeeReview.review_cycle_id.toString(),
        review_date: employeeReview.review_date || '',


        status: employeeReview.status,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('performance.employee-reviews.update', employeeReview.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{t('Edit Employee Review')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="user_id" required>{t('Employee')}</Label>
                        <Select value={data.user_id} onValueChange={(value) => setData('user_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select employee')} />
                            </SelectTrigger>
                            <SelectContent searchable={true}>
                                {employees && employees.map((employee) => (
                                    <SelectItem key={employee.id} value={employee.id.toString()}>
                                        {employee.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>
                    
                    <div>
                        <Label htmlFor="reviewer_id" required>{t('Reviewer')}</Label>
                        <Select value={data.reviewer_id} onValueChange={(value) => setData('reviewer_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select reviewer')} />
                            </SelectTrigger>
                            <SelectContent searchable={true}>
                                {reviewers.map((reviewer) => (
                                    <SelectItem key={reviewer.id} value={reviewer.id.toString()}>
                                        {reviewer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.reviewer_id} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="review_cycle_id" required>{t('Review Cycle')}</Label>
                        <Select value={data.review_cycle_id} onValueChange={(value) => setData('review_cycle_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select review cycle')} />
                            </SelectTrigger>
                            <SelectContent>
                                {reviewCycles && reviewCycles.length > 0 ? (
                                    reviewCycles.map((cycle) => (
                                        <SelectItem key={cycle.id} value={cycle.id.toString()}>
                                            {cycle.name}
                                        </SelectItem>
                                    ))
                                ) : (
                                    <SelectItem value="no-review-cycles" disabled>
                                        {t('No review cycles available')}
                                    </SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.review_cycle_id} />
                    </div>

                    <div>
                        <Label required>{t('Review Date')}</Label>
                        <DatePicker
                            value={data.review_date}
                            onChange={(value) => setData('review_date', value)}
                            placeholder={t('Select review date')}
                        />
                        <InputError message={errors.review_date} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">


                    <div>
                        <Label htmlFor="status" required>{t('Status')}</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">{t('Pending')}</SelectItem>
                                <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                <SelectItem value="completed">{t('Completed')}</SelectItem>
                                <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
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