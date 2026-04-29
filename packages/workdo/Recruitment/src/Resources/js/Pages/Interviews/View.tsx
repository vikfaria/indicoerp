import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { Users, Calendar, Clock, MapPin, Video, User, Briefcase, CheckCircle, AlertCircle } from 'lucide-react';
import { Interview } from './types';
import { formatDate, formatTime, formatDateTime, formatCurrency, getImagePath } from '@/utils/helpers';

interface ViewProps {
    interview: Interview;
}

export default function View({ interview }: ViewProps) {
    const { t } = useTranslation();

    const statusOptions: any = { "0": "Scheduled", "1": "Completed", "2": "Cancelled", "3": "No-show" };
    const getStatusColor = (status: string) => {
        switch(status) {
            case '0': return 'bg-blue-100 text-blue-800';
            case '1': return 'bg-green-100 text-green-800';
            case '2': return 'bg-red-100 text-red-800';
            case '3': return 'bg-orange-100 text-orange-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const statusValue = String(interview.status || '0');
    const isSubmitted = interview.feedback_submitted === true || interview.feedback_submitted === 1 || interview.feedback_submitted === '1';
    const interviewers = interview.interviewer_names
        ? interview.interviewer_names.split(',').map(name => name.trim()).filter(Boolean)
        : (interview.interviewers ? interview.interviewers.split(',').filter(Boolean) : []);

    return (
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader className="pb-4 border-b">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Calendar className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle className="text-xl font-semibold">{t('Interview Details')}</DialogTitle>
                    </div>
                </div>
            </DialogHeader>

            <div className="overflow-y-auto flex-1 p-6">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Candidate Information */}
                    <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                        <div className="flex items-center gap-2 mb-3">
                            <Users className="h-4 w-4 text-primary" />
                            <h3 className="font-semibold text-gray-900">{t('Candidate Information')}</h3>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Full Name')}</label>
                            <p className="font-medium">{`${interview.candidate?.first_name || ''} ${interview.candidate?.last_name || ''}`.trim() || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Applied Job')}</label>
                            <p className="font-medium">{interview.jobPosting?.title || interview.job_posting?.title || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                            <div className="mt-1">
                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusColor(statusValue)}`}>
                                    {t(statusOptions[statusValue] || statusValue || 'Scheduled')}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Schedule Information */}
                    <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                        <div className="flex items-center gap-2 mb-3">
                            <Calendar className="h-4 w-4 text-primary" />
                            <h3 className="font-semibold text-gray-900">{t('Schedule Information')}</h3>
                        </div>
                        <div className="flex items-center gap-2">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Date')}</label>
                                <p className="font-medium">{formatDate(interview.scheduled_date) || '-'}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Time & Duration')}</label>
                                <p className="font-medium">{formatTime(interview.scheduled_time) || '-'} ({interview.duration ? `${interview.duration} min` : '-'})</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Location')}</label>
                                <p className={`font-medium ${interview.location === 'Online' ? 'text-blue-600' : ''}`}>
                                    {interview.location || '-'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Interview Details */}
                    <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                        <div className="flex items-center gap-2 mb-3">
                            <Briefcase className="h-4 w-4 text-primary" />
                            <h3 className="font-semibold text-gray-900">{t('Interview Details')}</h3>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Round')}</label>
                            <p className="font-medium">{interview.interviewRound?.name || interview.interview_round?.name || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Interview Type')}</label>
                            <p className="font-medium">{interview.interviewType?.name || interview.interview_type?.name || '-'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Feedback Status')}</label>
                            <div className="mt-1">
                                <span className={`px-2 py-1 rounded-full text-sm ${
                                    isSubmitted
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'
                                }`}>
                                    {t(isSubmitted ? 'Submitted' : 'Pending')}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Interviewers */}
                    <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                        <div className="flex items-center gap-2 mb-3">
                            <User className="h-4 w-4 text-primary" />
                            <h3 className="font-semibold text-gray-900">{t('Interviewers')}</h3>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Assigned Interviewers')}</label>
                            {interviewers.length > 0 ? (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {interviewers.map((interviewer, index) => (
                                        <Badge key={index} variant="outline" className="px-3 py-1">
                                            {interviewer.trim()}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="font-medium text-muted-foreground mt-1">-</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Meeting Link */}
                {interview.meeting_link && (
                    <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                        <div className="flex items-center gap-2 mb-3">
                            <Video className="h-4 w-4 text-blue-600" />
                            <h3 className="font-semibold text-gray-900">{t('Meeting Information')}</h3>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-muted-foreground">{t('Meeting Link')}</label>
                            <div className="mt-2 p-3 bg-white rounded-md border">
                                <a
                                    href={interview.meeting_link}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 hover:text-blue-800 font-medium break-all text-sm"
                                >
                                    {interview.meeting_link}
                                </a>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </DialogContent>
    );
}