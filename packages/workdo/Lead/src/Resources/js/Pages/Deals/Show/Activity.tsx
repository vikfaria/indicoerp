import { useTranslation } from 'react-i18next';
import { CheckSquare, Mail, Phone, Users, MessageSquare, Activity as ActivityIcon } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';
import { formatDateTime } from '@/utils/helpers';
import { Deal } from '../types';

interface ActivityProps {
    deal: Deal;
}

export default function Activity({ deal }: ActivityProps) {
    const { t } = useTranslation();

    return (
        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[75vh] rounded-none w-full">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {deal.activities && deal.activities.length > 0 ? (
                    deal.activities.map((activity: any, index: number) => (
                        <div key={index} className="bg-white border rounded-lg p-4 shadow-sm">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                        {(() => {
                                            const remark = activity.remark || '';
                                            if (remark.includes('task') || remark.includes('Task')) return <CheckSquare className="h-4 w-4 text-primary" />;
                                            if (remark.includes('email') || remark.includes('Email')) return <Mail className="h-4 w-4 text-primary" />;
                                            if (remark.includes('call') || remark.includes('Call')) return <Phone className="h-4 w-4 text-primary" />;
                                            if (remark.includes('user') || remark.includes('User')) return <Users className="h-4 w-4 text-primary" />;
                                            if (remark.includes('discussion') || remark.includes('Discussion')) return <MessageSquare className="h-4 w-4 text-primary" />;
                                            return <ActivityIcon className="h-4 w-4 text-primary" />;
                                        })()}
                                    </div>
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="text-sm text-gray-900 mb-1">
                                        {(() => {
                                            try {
                                                const parsed = JSON.parse(activity.remark || '{}');
                                                return parsed.title || 'Activity';
                                            } catch {
                                                return activity.remark || 'Activity';
                                            }
                                        })()}
                                    </div>
                                    <p className="text-xs text-gray-500">
                                        {formatDateTime(activity.created_at)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="col-span-full flex items-center justify-center min-h-[400px]">
                        <NoRecordsFound
                            icon={ActivityIcon}
                            title={t('No Activities found')}
                            description={t('Activities will appear here when actions are performed.')}
                            className="h-auto"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}