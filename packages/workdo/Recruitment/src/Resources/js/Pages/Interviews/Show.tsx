import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from '@/components/ui/badge';
import { Users, Calendar, Clock, MapPin, Video, User, Briefcase, FileText, CheckCircle, AlertCircle } from 'lucide-react';
import { Interview } from './types';
import { formatDate, formatTime } from '@/utils/helpers';

interface InterviewShowProps {
    interview: Interview;
}

export default function Show() {
    const { t } = useTranslation();
    const { interview } = usePage<InterviewShowProps>().props;

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
    const interviewers = interview.interviewers ? interview.interviewers.split(',').filter(Boolean) : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Interviews'), url: route('recruitment.interviews.index')},
                {label: t('Interview Details')},
            ]}
            pageTitle={t('Interview Details')}
        >
            <Head title={`${t('Interview Details')} - ${interview.candidate?.first_name} ${interview.candidate?.last_name}`} />

            <div className="space-y-6">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Candidate Information */}
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {t('Candidate Information')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
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
                                        <Badge className={`px-3 py-1 ${getStatusColor(statusValue)}`}>
                                            {t(statusOptions[statusValue] || statusValue || 'Scheduled')}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Interview Schedule */}
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                {t('Schedule Information')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Date')}</label>
                                    <p className="font-medium">{interview.scheduled_date || '-'}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Clock className="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Time & Duration')}</label>
                                    <p className="font-medium">{interview.scheduled_time || '-'} ({interview.duration ? `${interview.duration} min` : '-'})</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {interview.location === 'Online' ? (
                                    <Video className="h-4 w-4 text-muted-foreground" />
                                ) : (
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                )}
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Location')}</label>
                                    <p className={`font-medium ${interview.location === 'Online' ? 'text-blue-600' : ''}`}>
                                        {interview.location || '-'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Interview Details */}
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Briefcase className="h-5 w-5" />
                                {t('Interview Details')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
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
                                    <Badge className={`px-3 py-1 ${
                                        isSubmitted
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        <div className="flex items-center gap-1">
                                            {isSubmitted ? (
                                                <CheckCircle className="h-3 w-3" />
                                            ) : (
                                                <AlertCircle className="h-3 w-3" />
                                            )}
                                            {t(isSubmitted ? 'Submitted' : 'Pending')}
                                        </div>
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Interviewers */}
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                {t('Interviewers')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
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
                        </CardContent>
                    </Card>
                </div>

                {/* Meeting Link */}
                {interview.meeting_link && (
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Video className="h-5 w-5" />
                                {t('Meeting Information')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Meeting Link')}</label>
                                <div className="mt-2 p-3 bg-blue-50 rounded-md">
                                    <a
                                        href={interview.meeting_link}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:text-blue-800 font-medium break-all"
                                    >
                                        {interview.meeting_link}
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Notes Section (if needed in future) */}
                {interview.notes && (
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                {t('Notes')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="bg-gray-50 p-4 rounded-md">
                                <p className="whitespace-pre-wrap">{interview.notes}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}