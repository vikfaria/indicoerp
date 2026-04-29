import { useState } from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Label } from "@/components/ui/label";
import { Star } from "lucide-react";
import InputError from "@/components/ui/input-error";

interface PerformanceIndicator {
    id: number;
    name: string;
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
}

interface ConductProps {
    employeeReview: EmployeeReview;
    performanceIndicators: { [categoryName: string]: PerformanceIndicator[] };
    existingRatings: { [key: string]: number };
}

export default function Conduct() {
    const { t } = useTranslation();
    const { employeeReview, performanceIndicators, existingRatings } = usePage<ConductProps>().props;
    
    const { data, setData, post, processing, errors } = useForm({
        ratings: existingRatings || {},
        pros: employeeReview.pros || '',
        cons: employeeReview.cons || ''
    });

    const setRating = (indicatorId: number, rating: number) => {
        setData('ratings', {
            ...data.ratings,
            [indicatorId]: rating
        });
    };

    const renderStars = (indicatorId: number) => {
        const currentRating = data.ratings[indicatorId] || 0;
        
        return (
            <div className="flex gap-1">
                {[1, 2, 3, 4, 5].map((star) => (
                    <button
                        key={star}
                        type="button"
                        onClick={() => setRating(indicatorId, star)}
                        className="focus:outline-none"
                    >
                        <Star
                            className={`h-6 w-6 ${
                                star <= currentRating
                                    ? 'fill-yellow-400 text-yellow-400'
                                    : 'text-gray-300'
                            }`}
                        />
                    </button>
                ))}
            </div>
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('performance.employee-reviews.conduct.store', employeeReview.id));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Performance') },
                { label: t('Employee Reviews'), url: route('performance.employee-reviews.index') },
                { label: t('Conduct Review') }
            ]}
            pageTitle={t('Conduct Performance Review')}
            backUrl={route('performance.employee-reviews.index')}
        >
            <Head title={t('Conduct Review')} />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Review Information')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span className="font-medium text-gray-700">{t('Employee')}:</span>
                                <p>{employeeReview.user?.name}</p>
                            </div>
                            <div>
                                <span className="font-medium text-gray-700">{t('Reviewer')}:</span>
                                <p>{employeeReview.reviewer?.name}</p>
                            </div>
                            <div>
                                <span className="font-medium text-gray-700">{t('Review Cycle')}:</span>
                                <p>{employeeReview.review_cycle?.name}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Performance Ratings')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {Object.entries(performanceIndicators).map(([categoryName, indicators]) => (
                                <div key={categoryName} className="space-y-4">
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        {categoryName || t('Uncategorized')}
                                    </h3>
                                    
                                    {indicators.map((indicator) => (
                                        <div key={indicator.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                            <div className="flex-1">
                                                <span className="text-sm font-medium text-gray-700">
                                                    {indicator.name}
                                                </span>
                                            </div>
                                            <div className="ml-4">
                                                {renderStars(indicator.id)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ))}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                                <div>
                                    <Label htmlFor="pros">{t('Pros')}</Label>
                                    <RichTextEditor
                                        content={data.pros}
                                        onChange={(content) => setData('pros', content)}
                                        placeholder={t('Enter positive feedback')}
                                    />
                                    <InputError message={errors.pros} />
                                </div>

                                <div>
                                    <Label htmlFor="cons">{t('Cons')}</Label>
                                    <RichTextEditor
                                        content={data.cons}
                                        onChange={(content) => setData('cons', content)}
                                        placeholder={t('Enter areas for improvement')}
                                    />
                                    <InputError message={errors.cons} />
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-4">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    onClick={() => window.history.back()}
                                >
                                    {t('Cancel')}
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? t('Submitting...') : t('Submit Review')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}