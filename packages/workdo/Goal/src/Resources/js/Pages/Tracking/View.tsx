import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

import { CalendarDays, Target, TrendingUp, Clock, DollarSign } from "lucide-react";
import { GoalTracking } from './types';
import { formatDate, formatCurrency } from '@/utils/helpers';

interface ViewProps {
    tracking: GoalTracking;
}

export default function View() {
    const { t } = useTranslation();
    const { tracking } = usePage<ViewProps>().props;

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'ahead': return 'bg-green-100 text-green-800';
            case 'on_track': return 'bg-blue-100 text-blue-800';
            case 'behind': return 'bg-yellow-100 text-yellow-800';
            case 'critical': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Goal'), url: route('goal.goals.index')},
                {label: t('Tracking'), url: route('goal.tracking.index')},
                {label: t('View')}
            ]}
            pageTitle={t('Tracking Details')}
            backUrl={route('goal.tracking.index')}
        >
            <Head title={t('Tracking Details')} />

            <div className="space-y-6">
                {/* Header Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-xl">{tracking.goal?.goal_name}</CardTitle>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {t('Tracking Date')}: {formatDate(tracking.tracking_date)}
                                </p>
                            </div>
                            <Badge className={getStatusColor(tracking.on_track_status)}>
                                {t(tracking.on_track_status.replace('_', ' ').charAt(0).toUpperCase() + tracking.on_track_status.replace('_', ' ').slice(1))}
                            </Badge>
                        </div>
                    </CardHeader>
                </Card>

                {/* Progress Overview */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5" />
                            {t('Progress Overview')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="text-center p-4 bg-blue-50 rounded-lg">
                                <DollarSign className="h-8 w-8 text-blue-600 mx-auto mb-2" />
                                <p className="text-sm text-gray-600">{t('Target Amount')}</p>
                                <p className="text-2xl font-bold text-blue-600">{formatCurrency(tracking.goal?.target_amount || 0)}</p>
                            </div>
                            <div className="text-center p-4 bg-green-50 rounded-lg">
                                <Target className="h-8 w-8 text-green-600 mx-auto mb-2" />
                                <p className="text-sm text-gray-600">{t('Current Amount')}</p>
                                <p className="text-2xl font-bold text-green-600">{formatCurrency(tracking.current_amount)}</p>
                            </div>
                            <div className="text-center p-4 bg-purple-50 rounded-lg">
                                <TrendingUp className="h-8 w-8 text-purple-600 mx-auto mb-2" />
                                <p className="text-sm text-gray-600">{t('Progress')}</p>
                                <p className="text-2xl font-bold text-purple-600">{tracking.progress_percentage}%</p>
                            </div>
                        </div>
                        
                        <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span>{t('Progress')}</span>
                                <span>{tracking.progress_percentage}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-3">
                                <div 
                                    className="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                                    style={{ width: `${Math.min(tracking.progress_percentage, 100)}%` }}
                                ></div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tracking Details */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Financial Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <DollarSign className="h-5 w-5" />
                                {t('Financial Details')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between items-center py-2 border-b">
                                <span className="text-sm text-gray-600">{t('Previous Amount')}</span>
                                <span className="font-medium">{formatCurrency(tracking.previous_amount)}</span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b">
                                <span className="text-sm text-gray-600">{t('Contribution Amount')}</span>
                                <span className="font-medium text-green-600">{formatCurrency(tracking.contribution_amount)}</span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b">
                                <span className="text-sm text-gray-600">{t('Current Amount')}</span>
                                <span className="font-semibold text-lg">{formatCurrency(tracking.current_amount)}</span>
                            </div>
                            <div className="flex justify-between items-center py-2">
                                <span className="text-sm text-gray-600">{t('Remaining Amount')}</span>
                                <span className="font-medium text-blue-600">
                                    {formatCurrency(Math.max(0, (tracking.goal?.target_amount || 0) - tracking.current_amount))}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Timeline Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                {t('Timeline Details')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between items-center py-2 border-b">
                                <span className="text-sm text-gray-600">{t('Tracking Date')}</span>
                                <span className="font-medium">{formatDate(tracking.tracking_date)}</span>
                            </div>
                            <div className="flex justify-between items-center py-2 border-b">
                                <span className="text-sm text-gray-600">{t('Days Remaining')}</span>
                                <span className={`font-medium ${tracking.days_remaining < 0 ? 'text-red-600' : tracking.days_remaining < 30 ? 'text-yellow-600' : 'text-green-600'}`}>
                                    {tracking.days_remaining < 0 ? t('Overdue by {{days}} days', { days: Math.abs(tracking.days_remaining) }) : t('{{days}} days', { days: tracking.days_remaining })}
                                </span>
                            </div>
                            {tracking.projected_completion_date && (
                                <div className="flex justify-between items-center py-2 border-b">
                                    <span className="text-sm text-gray-600">{t('Projected Completion')}</span>
                                    <span className="font-medium">{formatDate(tracking.projected_completion_date)}</span>
                                </div>
                            )}
                            <div className="flex justify-between items-center py-2">
                                <span className="text-sm text-gray-600">{t('Status')}</span>
                                <Badge className={getStatusColor(tracking.on_track_status)}>
                                    {t(tracking.on_track_status.replace('_', ' ').charAt(0).toUpperCase() + tracking.on_track_status.replace('_', ' ').slice(1))}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Goal Information */}
                {tracking.goal && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarDays className="h-5 w-5" />
                                {t('Goal Information')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">{t('Goal Name')}</p>
                                    <p className="font-medium">{tracking.goal.goal_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">{t('Goal Type')}</p>
                                    <p className="font-medium capitalize">{tracking.goal.goal_type?.replace('_', ' ')}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">{t('Start Date')}</p>
                                    <p className="font-medium">{formatDate(tracking.goal.start_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">{t('Target Date')}</p>
                                    <p className="font-medium">{formatDate(tracking.goal.target_date)}</p>
                                </div>
                            </div>
                            {tracking.goal.goal_description && (
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">{t('Description')}</p>
                                    <p className="text-sm">{tracking.goal.goal_description}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}