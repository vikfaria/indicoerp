import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { CheckCircle, Calendar, User, Tag, Clock, AlertCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { ChecklistItem } from './types';

interface ViewProps {
    checklistitem: ChecklistItem;
}

export default function View({ checklistitem }: ViewProps) {
    const { t } = useTranslation();

    const getCategoryBadge = (category: string) => {
        const styles = {
            'Other': 'bg-gray-100 text-gray-800',
            'Documentation': 'bg-blue-100 text-blue-800',
            'HR': 'bg-orange-100 text-orange-800',
            'IT Setup': 'bg-purple-100 text-purple-800',
            'Training': 'bg-green-100 text-green-800',
            'Facilities': 'bg-yellow-100 text-yellow-800'
        };
        return styles[category] || 'bg-gray-100 text-gray-800';
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <CheckCircle className="h-5 w-5 text-primary" />
                    </div>
                    <div className="flex-1">
                        <DialogTitle className="text-xl font-semibold">{t('Checklist Item Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>

            <div className="p-6 space-y-6">
                {/* Basic Information */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <Tag className="h-4 w-4" />
                                {t('Task Name')}
                            </label>
                            <p className="text-base font-medium">{checklistitem.task_name}</p>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Checklist')}
                            </label>
                            <p className="text-base font-medium">{checklistitem.checklist?.name || '-'}</p>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <Tag className="h-4 w-4" />
                                {t('Category')}
                            </label>
                            {checklistitem.category ? (
                                <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${getCategoryBadge(checklistitem.category)}`}>
                                    {checklistitem.category}
                                </span>
                            ) : (
                                <p className="text-gray-400">-</p>
                            )}
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <User className="h-4 w-4" />
                                {t('Assigned To Role')}
                            </label>
                            <p className="text-base">{checklistitem.assigned_to_role || '-'}</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <Clock className="h-4 w-4" />
                                {t('Due Day')}
                            </label>
                            {checklistitem.due_day ? (
                                <span className="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {t('Day')} {checklistitem.due_day}
                                </span>
                            ) : (
                                <p className="text-gray-400">-</p>
                            )}
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <CheckCircle className="h-4 w-4" />
                                {t('Status')}
                            </label>
                            <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${
                                checklistitem.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }`}>
                                {checklistitem.status ? t('Active') : t('Inactive')}
                            </span>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-500 flex items-center gap-2 mb-2">
                                <AlertCircle className="h-4 w-4" />
                                {t('Required')}
                            </label>
                            <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${
                                checklistitem.is_required ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                            }`}>
                                {checklistitem.is_required ? t('Yes') : t('No')}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Description */}
                {checklistitem.description && (
                    <div>
                        <label className="text-sm font-medium text-gray-500 mb-2 block">
                            {t('Description')}
                        </label>
                        <div className="bg-gray-50 rounded-lg p-4">
                            <p className="text-base leading-relaxed whitespace-pre-wrap">{checklistitem.description}</p>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}