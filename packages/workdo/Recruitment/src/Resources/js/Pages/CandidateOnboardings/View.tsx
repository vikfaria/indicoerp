import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { UserCheck, Calendar, User, FileText, Users } from 'lucide-react';
import { CandidateOnboarding } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    candidateonboarding: CandidateOnboarding;
}

export default function View({ candidateonboarding }: ViewProps) {
    const { t } = useTranslation();

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'Pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'In Progress':
                return 'bg-blue-100 text-blue-800';
            case 'Completed':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-6 border-b">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <UserCheck className="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <DialogTitle className="text-xl font-semibold">{t('Candidate Onboarding Details')}</DialogTitle>
                        </div>
                    </div>
                </div>
            </DialogHeader>

            <div className="p-6 space-y-6">
                {/* Candidate Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <User className="h-5 w-5 text-gray-600" />
                        <h3 className="font-semibold text-gray-900">{t('Candidate Information')}</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Name')}</label>
                            <p className="text-gray-900 font-medium">{candidateonboarding.candidate?.name || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Email')}</label>
                            <p className="text-gray-900">{candidateonboarding.candidate?.email || '-'}</p>
                        </div>
                    </div>
                </div>

                {/* Onboarding Details */}
                <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <FileText className="h-5 w-5 text-gray-600" />
                        <h3 className="font-semibold text-gray-900">{t('Onboarding Details')}</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Checklist Name')}</label>
                            <p className="text-gray-900 font-medium">{candidateonboarding.checklist?.name || 'No checklist assigned'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
                            <div className="mt-1">
                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(candidateonboarding.status)}`}>
                                    {t(candidateonboarding.status)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Timeline Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <Calendar className="h-5 w-5 text-gray-600" />
                        <h3 className="font-semibold text-gray-900">{t('Timeline')}</h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Start Date')}</label>
                            <p className="text-gray-900 font-medium">
                                {candidateonboarding.start_date ? formatDate(candidateonboarding.start_date) : '-'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">{t('Created At')}</label>
                            <p className="text-gray-900">
                                {candidateonboarding.created_at ? formatDate(candidateonboarding.created_at) : '-'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Buddy Information */}
                <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-center gap-2 mb-3">
                        <Users className="h-5 w-5 text-gray-600" />
                        <h3 className="font-semibold text-gray-900">{t('Buddy Assignment')}</h3>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-500">{t('Assigned Buddy')}</label>
                        <p className="text-gray-900 font-medium">
                            {candidateonboarding.buddy?.name || 'No buddy assigned'}
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    );
}