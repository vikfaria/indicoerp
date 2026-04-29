import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Star, FileText } from "lucide-react";
import { usePage } from '@inertiajs/react';
import { formatDate } from '@/utils/helpers';

interface PerformanceIndicator {
    id: number;
    name: string;
    user_rating: number;
    category: {
        name: string;
    };
}

interface EmployeeReview {
    id: number;
    user: { name: string };
    reviewer: { name: string };
    review_cycle: { name: string };
    review_date: string;
    status: string;
    pros?: string;
    cons?: string;
    completion_date?: string;
}

interface ShowProps {
    employeeReview: EmployeeReview;
    performanceIndicators: { [categoryName: string]: PerformanceIndicator[] };
    averageRating: number | null;
}

export default function Show({ employeeReview, performanceIndicators, averageRating }: ShowProps) {
    const { t } = useTranslation();
    const { users, review_cycles } = usePage<any>().props;

    const getStatusColor = (status: string) => {
        const colors: { [key: string]: string } = {
            pending: 'bg-yellow-100 text-yellow-800',
            in_progress: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusLabel = (status: string) => {
        const labels: { [key: string]: string } = {
            pending: t('Pending'),
            in_progress: t('In Progress'),
            completed: t('Completed'),
            cancelled: t('Cancelled')
        };
        return labels[status] || status;
    };

    const renderStars = (rating: number) => {
        return (
            <div className="flex gap-1">
                {[1, 2, 3, 4, 5].map((star) => (
                    <Star
                        key={star}
                        className={`h-4 w-4 ${
                            star <= rating
                                ? 'fill-yellow-400 text-yellow-400'
                                : 'text-gray-300'
                        }`}
                    />
                ))}
            </div>
        );
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <FileText className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Employee Review Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{employeeReview.user?.name}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Employee')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {users?.find((user: any) => user.id.toString() === employeeReview.user?.id?.toString())?.name || employeeReview.user?.name || '-'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Reviewer')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {users?.find((user: any) => user.id.toString() === employeeReview.reviewer?.id?.toString())?.name || employeeReview.reviewer?.name || '-'}
                            </p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Review Cycle')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {review_cycles?.find((cycle: any) => cycle.id.toString() === employeeReview.review_cycle?.id?.toString())?.name || employeeReview.review_cycle?.name || '-'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(employeeReview.status)}`}>
                                    {getStatusLabel(employeeReview.status)}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Review Date')}</label>
                            <p className="mt-1 text-sm text-gray-900">{formatDate(employeeReview.review_date)}</p>
                        </div>
                        {employeeReview.completion_date && (
                            <div>
                                <label className="text-sm font-medium text-gray-500">{t('Completion Date')}</label>
                                <p className="mt-1 text-sm text-gray-900">{formatDate(employeeReview.completion_date)}</p>
                            </div>
                        )}
                    </div>
                    
                    {averageRating && (
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Average Rating')}</label>
                            <div className="mt-1 flex items-center gap-2">
                                {renderStars(Math.round(averageRating))}
                                <span className="text-sm text-gray-900">{averageRating.toFixed(1)}/5</span>
                            </div>
                        </div>
                    )}
                </div>

                {Object.keys(performanceIndicators).length > 0 && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Performance Ratings')}</label>
                        <div className="mt-2 space-y-3">
                            {Object.entries(performanceIndicators).map(([categoryName, indicators]) => (
                                <div key={categoryName}>
                                    <h3 className="text-sm font-medium text-gray-700 mb-2">
                                        {categoryName || t('Uncategorized')}
                                    </h3>
                                    <div className="space-y-2">
                                        {indicators.map((indicator) => (
                                            <div key={indicator.id} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                                <span className="text-sm text-gray-900">{indicator.name}</span>
                                                <div className="flex items-center gap-1">
                                                    {renderStars(indicator.user_rating)}
                                                    <span className="text-xs text-gray-600 ml-1">{indicator.user_rating}/5</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {employeeReview.pros && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Pros')}</label>
                        <div className="mt-1 p-3 bg-green-50 rounded border-l-2 border-green-400">
                            <div className="text-sm text-gray-900" dangerouslySetInnerHTML={{ __html: employeeReview.pros }} />
                        </div>
                    </div>
                )}

                {employeeReview.cons && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Cons')}</label>
                        <div className="mt-1 p-3 bg-red-50 rounded border-l-2 border-red-400">
                            <div className="text-sm text-gray-900" dangerouslySetInnerHTML={{ __html: employeeReview.cons }} />
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}