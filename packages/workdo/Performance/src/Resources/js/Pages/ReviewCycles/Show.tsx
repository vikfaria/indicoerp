import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { RotateCcw } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { formatDate } from '@/utils/helpers';

interface EmployeeReview {
    id: number;
    user: { name: string };
    reviewer: { name: string };
    status: string;
    review_date: string;
    completion_date?: string;
}

interface ReviewCycle {
    id: number;
    name: string;
    frequency: string;
    description?: string;
    status: string;
    created_at: string;
    creator: { name: string };
    created_by: { name: string };
    employee_reviews: EmployeeReview[];
}

interface ShowProps {
    reviewCycle: ReviewCycle;
}

export default function Show({ reviewCycle }: ShowProps) {
    const { t } = useTranslation();
    const { users, frequency_options } = usePage<any>().props;

    const getStatusColor = (status: string) => {
        const colors: { [key: string]: string } = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-red-100 text-red-800',
            pending: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-blue-100 text-blue-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusLabel = (status: string) => {
        const labels: { [key: string]: string } = {
            active: t('Active'),
            inactive: t('Inactive'),
            pending: t('Pending'),
            completed: t('Completed')
        };
        return labels[status] || status;
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <RotateCcw className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Review Cycle Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{reviewCycle.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Name')}</label>
                            <p className="mt-1 text-sm text-gray-900">{reviewCycle.name}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Frequency')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {frequency_options?.[reviewCycle.frequency] || reviewCycle.frequency || '-'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Created By')}</label>
                            <p className="mt-1 text-sm text-gray-900">{reviewCycle.created_by?.name || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(reviewCycle.status)}`}>
                                    {getStatusLabel(reviewCycle.status)}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                {reviewCycle.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Description')}</label>
                        <p className="mt-1 text-sm text-gray-900">{reviewCycle.description}</p>
                    </div>
                )}

                {reviewCycle.employee_reviews && reviewCycle.employee_reviews.length > 0 && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">
                            {t('Employee Reviews')} ({reviewCycle.employee_reviews.length})
                        </label>
                        <div className="mt-2 space-y-2">
                            {reviewCycle.employee_reviews.map((review) => (
                                <div key={review.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-4">
                                            <div>
                                                <span className="text-sm font-medium text-gray-900">{review.user?.name}</span>
                                                <p className="text-xs text-gray-500">
                                                    {t('Reviewer')}: {review.reviewer?.name}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500">
                                                    {formatDate(review.review_date)}
                                                </p>
                                                {review.completion_date && (
                                                    <p className="text-xs text-gray-500">
                                                        {t('Completed')}: {formatDate(review.completion_date)}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(review.status)}`}>
                                        {getStatusLabel(review.status)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}