import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { Milestone } from './types';

interface ViewMilestoneProps {
    milestone: Milestone;
}

export default function View({ milestone }: ViewMilestoneProps) {
    const { t } = useTranslation();

    const getStatusBadge = (status: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                status === 'achieved' ? 'bg-green-100 text-green-800' :
                status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                status === 'overdue' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
            }`}>
                {t(status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1))}
            </span>
        );
    };

    return (
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Milestone Details')} - {milestone.milestone_name}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6 mt-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex justify-between items-center">
                            {t('Milestone Information')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="font-semibold">{t('Milestone Name')}</span>
                                <p className="mt-1 text-gray-500">{milestone.milestone_name}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Goal')}</span>
                                <p className="mt-1 text-gray-500">{milestone.goal?.goal_name || '-'}</p>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Status')}</span>
                                <div className="mt-1">{getStatusBadge(milestone.status)}</div>
                            </div>
                            <div>
                                <span className="font-semibold">{t('Target Date')}</span>
                                <p className="mt-1 text-gray-500">{formatDate(milestone.target_date)}</p>
                            </div>
                        </div>

                        <div className="mt-6">
                            <span className="font-semibold text-sm">{t('Progress Information')}</span>
                            <div className="mt-3 p-4 bg-gray-50 rounded-lg">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span className="font-medium text-gray-600">{t('Target Amount')}</span>
                                        <p className="text-lg font-semibold text-blue-600">{formatCurrency(milestone.target_amount)}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-600">{t('Archive Amount')}</span>
                                        <p className="text-lg font-semibold text-green-600">{formatCurrency(milestone.achieved_amount || 0)}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-600">{t('Progress')}</span>
                                        <p className="text-lg font-semibold text-purple-600">
                                            {milestone.target_amount > 0 ? Math.round(((milestone.achieved_amount || 0) / milestone.target_amount) * 100) : 0}%
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            style={{
                                                width: `${milestone.target_amount > 0 ? Math.min(((milestone.achieved_amount || 0) / milestone.target_amount) * 100, 100) : 0}%`
                                            }}
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {milestone.milestone_description && (
                            <div className="text-sm mt-4">
                                <span className="font-semibold">{t('Description')}</span>
                                <p className="mt-1 p-3 bg-gray-50 rounded text-sm">{milestone.milestone_description}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DialogContent>
    );
}
