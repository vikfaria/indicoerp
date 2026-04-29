import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { MessageSquare, Star, User, Calendar } from 'lucide-react';
import { InterviewFeedback } from './types';
import { formatDate } from '@/utils/helpers';

interface ViewProps {
    interviewfeedback: InterviewFeedback;
}

export default function View({ interviewfeedback }: ViewProps) {
    const { t } = useTranslation();

    const recommendationOptions: any = {"0":"Strong Hire","1":"Hire","2":"Maybe","3":"Reject","4":"Strong Reject"};
    const recommendationText = recommendationOptions[interviewfeedback.recommendation] || 'No Recommendation';

    const getBadgeColor = (val: string) => {
        switch(val) {
            case '0': return 'bg-green-100 text-green-800';
            case '1': return 'bg-blue-100 text-blue-800';
            case '2': return 'bg-yellow-100 text-yellow-800';
            case '3': case '4': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const renderStars = (rating: number) => {
        return (
            <div className="flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((star) => (
                    <Star
                        key={star}
                        className={`h-4 w-4 ${
                            star <= rating
                                ? 'text-yellow-400 fill-yellow-400'
                                : 'text-gray-300'
                        }`}
                    />
                ))}
                <span className="ml-2 text-sm font-medium">{rating}/5</span>
            </div>
        );
    };

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary/10 rounded-lg">
                            <MessageSquare className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <DialogTitle className="text-xl font-semibold">{t('Interview Feedback Details')}</DialogTitle>
                        </div>
                    </div>
                    <div className={`px-3 py-1 rounded-full text-sm font-medium mr-6 ${getBadgeColor(interviewfeedback.recommendation)}`}>
                        {t(recommendationText)}
                    </div>
                </div>
            </DialogHeader>

            <div className="p-6 space-y-6">
                {/* Candidate & Interview Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-gray-50 rounded-lg p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <User className="h-5 w-5 text-gray-600" />
                            <h3 className="font-semibold text-gray-900">{t('Candidate Information')}</h3>
                        </div>
                        <div className="space-y-2">
                            <div>
                                <span className="text-sm text-gray-600">{t('Name')}: </span>
                                <span className="font-medium">
                                    {(interviewfeedback.interview?.candidate?.first_name && interviewfeedback.interview?.candidate?.last_name)
                                        ? `${interviewfeedback.interview.candidate.first_name} ${interviewfeedback.interview.candidate.last_name}`
                                        : 'Unknown Candidate'
                                    }
                                </span>
                            </div>
                            <div>
                                <span className="text-sm text-gray-600">{t('Position')}: </span>
                                <span className="font-medium">{interviewfeedback.interview?.job_posting?.title || 'No Job Title'}</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gray-50 rounded-lg p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <Calendar className="h-5 w-5 text-gray-600" />
                            <h3 className="font-semibold text-gray-900">{t('Interview Details')}</h3>
                        </div>
                        <div className="space-y-2">
                            <div>
                                <span className="text-sm text-gray-600">{t('Interviewer(s)')}: </span>
                                <span className="font-medium">{interviewfeedback.interviewer_names || 'No interviewer assigned'}</span>
                            </div>
                            <div>
                                <span className="text-sm text-gray-600">{t('Date')}: </span>
                                <span className="font-medium">{interviewfeedback.created_at ? formatDate(interviewfeedback.created_at) : '-'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Ratings */}
                <div>
                    <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <Star className="h-5 w-5" />
                        {t('Ratings')}
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="bg-blue-50 rounded-lg p-4 text-center">
                            <h4 className="text-sm font-medium text-blue-700 mb-2">{t('Technical')}</h4>
                            {interviewfeedback.technical_rating ? renderStars(interviewfeedback.technical_rating) : <span className="text-gray-500">{t('No rating')}</span>}
                        </div>
                        <div className="bg-green-50 rounded-lg p-4 text-center">
                            <h4 className="text-sm font-medium text-green-700 mb-2">{t('Communication')}</h4>
                            {interviewfeedback.communication_rating ? renderStars(interviewfeedback.communication_rating) : <span className="text-gray-500">{t('No rating')}</span>}
                        </div>
                        <div className="bg-purple-50 rounded-lg p-4 text-center">
                            <h4 className="text-sm font-medium text-purple-700 mb-2">{t('Cultural Fit')}</h4>
                            {interviewfeedback.cultural_fit_rating ? renderStars(interviewfeedback.cultural_fit_rating) : <span className="text-gray-500">{t('No rating')}</span>}
                        </div>
                        <div className="bg-orange-50 rounded-lg p-4 text-center">
                            <h4 className="text-sm font-medium text-orange-700 mb-2">{t('Overall')}</h4>
                            {interviewfeedback.overall_rating ? renderStars(interviewfeedback.overall_rating) : <span className="text-gray-500">{t('No rating')}</span>}
                        </div>
                    </div>
                </div>

                {/* Feedback Content */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 className="font-medium text-green-700 mb-2">{t('Strengths')}</h4>
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4 max-h-[200px] overflow-y-auto">
                            <p className="text-gray-900 whitespace-pre-wrap">{interviewfeedback.strengths || 'No strengths mentioned'}</p>
                        </div>
                    </div>
                    <div>
                        <h4 className="font-medium text-red-700 mb-2">{t('Weaknesses')}</h4>
                        <div className="bg-red-50 border border-red-200 rounded-lg p-4 max-h-[200px] overflow-y-auto">
                            <p className="text-gray-900 whitespace-pre-wrap">{interviewfeedback.weaknesses || 'No weaknesses mentioned'}</p>
                        </div>
                    </div>
                </div>

                {/* Comments */}
                <div>
                    <h4 className="font-medium text-blue-700 mb-2">{t('Additional Comments')}</h4>
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 max-h-[200px] overflow-y-auto">
                        <p className="text-gray-900 whitespace-pre-wrap">{interviewfeedback.comments || 'No additional comments'}</p>
                    </div>
                </div>
            </div>
        </DialogContent>
    );
}