import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Target } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { formatDate } from '@/utils/helpers';

interface EmployeeGoal {
    id: number;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    target: string;
    progress: number;
    status: string;
    created_at: string;
    updated_at: string;
    employee: {
        id: number;
        name: string;
    };
    goal_type: {
        id: number;
        name: string;
    };
}

interface ShowProps {
    goal: EmployeeGoal;
}

export default function Show({ goal }: ShowProps) {
    const { t } = useTranslation();
    const { employees, goal_types } = usePage<any>().props;

    const getStatusColor = (status: string) => {
        const colors: { [key: string]: string } = {
            not_started: 'bg-gray-100 text-gray-800',
            in_progress: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            overdue: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusLabel = (status: string) => {
        const labels: { [key: string]: string } = {
            not_started: t('Not Started'),
            in_progress: t('In Progress'),
            completed: t('Completed'),
            overdue: t('Overdue')
        };
        return labels[status] || status;
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Target className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Employee Goal Details')}</DialogTitle>
                        <p className="text-sm text-muted-foreground">{goal.title}</p>
                    </div>
                </div>
            </DialogHeader>
            
            <div className="overflow-y-auto flex-1 p-4 space-y-6">
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Title')}</label>
                            <p className="mt-1 text-sm text-gray-900">{goal.title}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Employee')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {goal.employee?.name || '-'}
                            </p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Goal Type')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                {goal_types?.find((type: any) => type.id.toString() === goal.goal_type?.id?.toString())?.name || goal.goal_type?.name || '-'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Target')}</label>
                            <p className="mt-1 text-sm text-gray-900">{goal.target || '-'}</p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Progress')}</label>
                            <div className="mt-1 flex items-center gap-2">
                                <div className="flex-1 bg-gray-200 rounded-full h-2">
                                    <div 
                                        className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                        style={{ width: `${Math.min(goal.progress || 0, 100)}%` }}
                                    />
                                </div>
                                <span className="text-sm text-gray-900">{goal.progress || 0}%</span>
                            </div>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                            <p className="mt-1 text-sm text-gray-900">
                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(goal.status)}`}>
                                    {getStatusLabel(goal.status)}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Start Date')}</label>
                            <p className="mt-1 text-sm text-gray-900">{formatDate(goal.start_date)}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('End Date')}</label>
                            <p className="mt-1 text-sm text-gray-900">{formatDate(goal.end_date)}</p>
                        </div>
                    </div>
                </div>

                {goal.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Description')}</label>
                        <p className="mt-1 text-sm text-gray-900">{goal.description}</p>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}