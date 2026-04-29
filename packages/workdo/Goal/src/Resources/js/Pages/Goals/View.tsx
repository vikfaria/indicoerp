import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { Goal } from './types';

interface ViewGoalProps {
    goal: Goal;
}

export default function View({ goal }: ViewGoalProps) {
    const { t } = useTranslation();

    const getStatusBadge = (status: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                status === 'completed' ? 'bg-green-100 text-green-800' :
                status === 'active' ? 'bg-blue-100 text-blue-800' :
                status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                status === 'cancelled' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
            }`}>
                {t(status.charAt(0).toUpperCase() + status.slice(1))}
            </span>
        );
    };

    const getPriorityBadge = (priority: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                priority === 'critical' ? 'bg-red-100 text-red-800' :
                priority === 'high' ? 'bg-orange-100 text-orange-800' :
                priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                'bg-green-100 text-green-800'
            }`}>
                {t(priority.charAt(0).toUpperCase() + priority.slice(1))}
            </span>
        );
    };

    const progressPercentage = goal.target_amount > 0 ? (goal.current_amount / goal.target_amount) * 100 : 0;

    return (
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Goal Details')} - {goal.goal_name}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6 mt-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex justify-between items-center">
                            {t('Goal Information')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="font-semibold">{t('Goal Name')}</span>
                                <p className="mt-1 text-gray-500">{goal.goal_name}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Category')}</span>
                                <p className="mt-1 text-gray-500">{goal.category?.category_name || '-'}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Goal Type')}</span>
                                <p className="mt-1 text-gray-500">{t(goal.goal_type.replace('_', ' '))}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Priority')}</span>
                                <div className="mt-1">{getPriorityBadge(goal.priority)}</div>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Status')}</span>
                                <div className="mt-1">{getStatusBadge(goal.status)}</div>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Chart of Account')}</span>
                                <p className="mt-1 text-gray-500">{goal.account?.account_name || '-'}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Start Date')}</span>
                                <p className="mt-1 text-gray-500">{formatDate(goal.start_date)}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Target Date')}</span>
                                <p className="mt-1 text-gray-500">{formatDate(goal.target_date)}</p>
                            </div>
                        </div>
                        {goal.goal_description && (
                            <div className="text-sm mt-4">
                                <span className="font-semibold">{t('Description')}</span>
                                <p className="mt-1 p-3 bg-gray-50 rounded text-sm">{goal.goal_description}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t('Financial Progress')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span className="font-semibold text-sm">{t('Target Amount')}</span>
                                    <p className="mt-1 text-2xl font-bold text-blue-600">{formatCurrency(goal.target_amount)}</p>
                                </div>
                                <div>
                                    <span className="font-semibold text-sm">{t('Current Amount')}</span>
                                    <p className="mt-1 text-2xl font-bold text-green-600">{formatCurrency(goal.current_amount)}</p>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between items-center mb-2">
                                    <span className="font-semibold text-sm">{t('Progress')}</span>
                                    <span className="text-sm text-gray-600">{progressPercentage.toFixed(1)}%</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-3">
                                    <div
                                        className="bg-blue-600 h-3 rounded-full transition-all duration-300"
                                        style={{ width: `${Math.min(progressPercentage, 100)}%` }}
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </DialogContent>
    );
}
