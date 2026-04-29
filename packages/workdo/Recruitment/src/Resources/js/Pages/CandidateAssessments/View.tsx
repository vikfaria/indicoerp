import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { ClipboardCheck } from 'lucide-react';
import { CandidateAssessment } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    candidateassessment: CandidateAssessment;
}

export default function View({ candidateassessment }: ViewProps) {
    const { t } = useTranslation();

    const score = candidateassessment.score || 0;
    const maxScore = candidateassessment.max_score || 0;
    const percentage = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
    const firstName = candidateassessment.candidate?.first_name || '';
    const lastName = candidateassessment.candidate?.last_name || '';
    const fullName = firstName && lastName ? `${firstName} ${lastName}` : (firstName || lastName || '-');

    const getStatusColor = (status: string) => {
        switch(status) {
            case '0': return 'bg-green-100 text-green-800';
            case '1': return 'bg-red-100 text-red-800';
            case '2': return 'bg-yellow-100 text-yellow-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = (status: string) => {
        const options: any = {"0":"Pass","1":"Fail","2":"Pending"};
        return options[status] || status || '-';
    };

    return (
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <ClipboardCheck className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Candidate Assessments Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>

            <div className="p-6 space-y-6">
                {/* Basic Information */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-600">{t('Assessment Name')}</label>
                            <p className="text-gray-900 font-medium">{candidateassessment.assessment_name || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-600">{t('Candidate')}</label>
                            <p className="text-gray-900 font-medium">{fullName}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-600">{t('Conducted By')}</label>
                            <p className="text-gray-900">{candidateassessment.conducted_by?.name || '-'}</p>
                        </div>
                    </div>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-600">{t('Assessment Date')}</label>
                            <p className="text-gray-900">{formatDate(candidateassessment.assessment_date) || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-600">{t('Status')}</label>
                            <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(candidateassessment.pass_fail_status)}`}>
                                {t(getStatusText(candidateassessment.pass_fail_status))}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Score Section */}
                <div className="bg-gray-50 rounded-lg p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('Score Details')}</h3>
                    <div className="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div className="text-2xl font-bold text-blue-600">{score}</div>
                            <div className="text-sm text-gray-600">{t('Score')}</div>
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-gray-700">{maxScore}</div>
                            <div className="text-sm text-gray-600">{t('Max Score')}</div>
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-green-600">{percentage}%</div>
                            <div className="text-sm text-gray-600">{t('Percentage')}</div>
                        </div>
                    </div>
                    <div className="mt-4">
                        <div className="w-full bg-gray-200 rounded-full h-3">
                            <div
                                className="bg-blue-500 h-3 rounded-full"
                                style={{width: `${percentage}%`}}
                            ></div>
                        </div>
                    </div>
                </div>

                {/* Comments */}
                {candidateassessment.comments && (
                    <div>
                        <label className="text-sm font-medium text-gray-600 block mb-2">{t('Comments')}</label>
                        <div className="bg-gray-50 rounded-lg p-4">
                            <p className="text-gray-700 whitespace-pre-wrap">{candidateassessment.comments}</p>
                        </div>
                    </div>
                )}


            </div>
        </DialogContent>
    );
}