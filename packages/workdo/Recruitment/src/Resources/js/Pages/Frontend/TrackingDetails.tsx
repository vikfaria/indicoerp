import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, Clock, MapPin, DollarSign, Briefcase, Mail, Phone, Copy, Check, Video, ExternalLink, Calendar, Star } from 'lucide-react';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { formatDate, formatTime, formatCurrency } from '@/utils/helpers';
import { useFormFields } from '@/hooks/useFormFields';



interface JobDetails {
    title: string;
    location: string;
    jobType: string;
    salaryFrom: number;
    salaryTo: number;
    department: string;
}

interface CandidateDetails {
    fullName: string;
    email: string;
    phone: string;
    appliedDate: string;
}

interface ApplicationStatus {
    currentStatus: number;
    timeline: {
        status: number;
        title: string;
        description: string;
        date: string | null;
        completed: boolean;
        details?: any;
    }[];
}

interface InterviewDetails {
    id: number;
    date: string;
    time: string;
    duration: string;
    location: string;
    meeting_link: string;
    round: string;
    type: string;
    status: string;
}

interface OfferDetails {
    id: number;
    offer_date: string;
    position: string;
    salary: number;
    bonus: number;
    start_date: string;
    expiration_date: string;
    status: string;
    benefits: string;
}

interface TrackingDetailsProps {
    trackingId: string;
    jobDetails?: JobDetails;
    candidateDetails?: CandidateDetails;
    applicationStatus?: ApplicationStatus;
    interviewDetails?: InterviewDetails;
    offerDetails?: OfferDetails;
    needHelp?: {
        title: string;
        description: string;
        email: string;
        phone: string;
    } | null;
}

export default function TrackingDetails({ trackingId, jobDetails, candidateDetails, applicationStatus, interviewDetails, offerDetails, needHelp }: TrackingDetailsProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;
    const [copied, setCopied] = useState(false);

    const integrationFields = useFormFields('getIntegrationFields', {}, () => {}, {}, 'create', t, 'Recruitment');
    const [updatingOffer, setUpdatingOffer] = useState<number | null>(null);

    // Fallback application data when no real data is available
    const fallbackApplication = {
        currentStatus: 0,
        timeline: [
            {
                status: 0,
                title: t('Application Submitted'),
                description: t('Your application has been successfully submitted'),
                date: new Date().toISOString().split('T')[0],
                completed: true
            },
            {
                status: 1,
                title: t('Screening'),
                description: t('Your application is being screened by our team'),
                date: null,
                completed: false
            },
            {
                status: 2,
                title: t('Interview'),
                description: t('Interview process will be scheduled'),
                date: null,
                completed: false
            },
            {
                status: 3,
                title: t('Offer'),
                description: t('Job offer decision pending'),
                date: null,
                completed: false
            },
            {
                status: 4,
                title: t('Final Decision'),
                description: t('We will notify you of our final decision'),
                date: null,
                completed: false
            }
        ]
    };

    const handleOfferResponse = async (offerId: number, status: string) => {
        setUpdatingOffer(offerId);

        router.post(route('recruitment.frontend.careers.offer.respond', { userSlug, offerId }), {
            status: status
        }, {
            onSuccess: () => {
                setUpdatingOffer(null);
            },
            onError: () => {
                setUpdatingOffer(null);
            }
        });
    };

    const copyTrackingId = async () => {
        try {
            await navigator.clipboard.writeText(trackingId);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
        }
    };

    const formatSalary = (from: number, to: number) => {
        return `${formatCurrency(from)} - ${formatCurrency(to)}`;
    };

    const getStatusColor = (status: number) => {
        switch (status) {
            case 0:
                return 'bg-blue-100 text-blue-800';
            case 1:
                return 'bg-yellow-100 text-yellow-800';
            case 2:
                return 'bg-purple-100 text-purple-800';
            case 3:
                return 'bg-orange-100 text-orange-800';
            case 4:
                return 'bg-green-100 text-green-800';
            case 5:
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = (status: number) => {
        switch (status) {
            case 0:
                return t('New');
            case 1:
                return t('Screening');
            case 2:
                return t('Interview');
            case 3:
                return t('Offer');
            case 4:
                return t('Hired');
            case 5:
                return t('Rejected');
            default:
                return t('Pending');
        }
    };

    const getStatusIcon = (status: number) => {
        switch (status) {
            case 0:
                return <CheckCircle className="h-6 w-6 text-blue-600" />;
            case 1:
                return <Clock className="h-6 w-6 text-yellow-600" />;
            case 2:
                return <Clock className="h-6 w-6 text-purple-600" />;
            case 3:
                return <Clock className="h-6 w-6 text-orange-600" />;
            case 4:
                return <CheckCircle className="h-6 w-6 text-green-600" />;
            case 5:
                return <CheckCircle className="h-6 w-6 text-red-600" />;
            default:
                return <Clock className="h-6 w-6 text-gray-600" />;
        }
    };

    const getTimelineIcon = (status: number, completed: boolean, currentStatus: number) => {
        if (completed) {
            return <CheckCircle className="h-6 w-6 text-green-600" />;
        } else if (status === currentStatus) {
            return <Clock className="h-6 w-6 text-yellow-600" />;
        } else {
            return <div className="h-6 w-6 rounded-full border-2 border-gray-300 bg-white"></div>;
        }
    };

    // Use real application status or fallback
    const appStatus = applicationStatus || fallbackApplication;

    return (
        <FrontendLayout
            title="Track Application"
            currentPage="track"
        >
            <Head title={`${t('Application Status')} - ${trackingId}`} />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Main Content */}
                    <div className="lg:w-2/3">
                        {/* Current Status Card */}
                        <Card className="mb-8 shadow-sm border-l-4 border-l-slate-600">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 mb-2">{t('Current Status')}</h2>
                                        <Badge className={`text-base px-3 py-1 ${getStatusColor(appStatus.currentStatus)} hover:${getStatusColor(appStatus.currentStatus)}`}>
                                            {getStatusText(appStatus.currentStatus)}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center justify-center h-12 w-12 rounded-full bg-slate-100">
                                        {getStatusIcon(appStatus.currentStatus)}
                                    </div>
                                </div>

                                <div className="bg-slate-50 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-700 mb-1">{t('Tracking ID')}</h3>
                                            <p className="text-xl font-mono font-bold text-slate-700 tracking-wider">
                                                {trackingId}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="text-slate-700 border-slate-300 hover:bg-slate-50"
                                            onClick={copyTrackingId}
                                        >
                                            {copied ? (
                                                <>
                                                    <Check className="h-4 w-4 mr-1" />
                                                    {t('Copied')}
                                                </>
                                            ) : (
                                                <>
                                                    <Copy className="h-4 w-4 mr-1" />
                                                    {t('Copy ID')}
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Application Timeline */}
                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <h2 className="text-xl font-bold text-gray-900 mb-6 pb-3 border-b border-gray-200">{t('Application Timeline')}</h2>

                                <div className="space-y-6">
                                    {appStatus.timeline.map((step, index) => (
                                        <div key={step.status} className="flex items-start space-x-4">
                                            <div className="flex-shrink-0 relative">
                                                {getTimelineIcon(step.status, step.completed, appStatus.currentStatus)}
                                                {index < appStatus.timeline.length - 1 && (
                                                    <div className="absolute top-8 left-3 w-0.5 h-12 bg-gray-200"></div>
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0 pb-2">
                                                <div className="flex items-center justify-between mb-1">
                                                    <h3 className={`text-base font-semibold ${step.completed ? 'text-gray-900' : step.status === appStatus.currentStatus ? 'text-slate-700' : 'text-gray-400'}`}>
                                                        {step.title}
                                                    </h3>
                                                    {step.date && (
                                                        <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                            {formatDate(step.date)}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className={`text-sm ${step.completed ? 'text-gray-600' : step.status === appStatus.currentStatus ? 'text-slate-600' : 'text-gray-400'}`}>
                                                    {step.description}
                                                </p>

                                                {/* Interview Details */}
                                                {step.status === 2 && step.details && (
                                                    <div className="mt-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                                                        <h4 className="text-sm font-medium text-purple-900 mb-2">{t('Interview Details')}</h4>
                                                        <div className="space-y-2 text-sm text-purple-800">
                                                            <div className="flex items-center">
                                                                <Calendar className="h-4 w-4 mr-2" />
                                                                <span>{formatDate(step.details.date)} at {formatTime(step.details.time)}</span>
                                                            </div>
                                                            <div className="flex items-center">
                                                                <Clock className="h-4 w-4 mr-2" />
                                                                <span>{t('Duration')}: {step.details.duration} {t('minutes')}</span>
                                                            </div>
                                                            {step.details.location && (
                                                                <div className="flex items-center">
                                                                    <MapPin className="h-4 w-4 mr-2" />
                                                                    <span>{step.details.location}</span>
                                                                </div>
                                                            )}
                                                            {step.details.meeting_link && (
                                                                <div className="flex items-center">
                                                                    <Video className="h-4 w-4 mr-2" />
                                                                    <a href={step.details.meeting_link} target="_blank" rel="noopener noreferrer" className="text-purple-600 hover:text-purple-800 underline">
                                                                        {t('Join Meeting')} <ExternalLink className="h-3 w-3 inline ml-1" />
                                                                    </a>
                                                                </div>
                                                            )}
                                                            <div className="flex items-center justify-between">
                                                                <div className="text-xs text-purple-600">
                                                                    {step.details.round} - {step.details.type}
                                                                </div>
                                                                <div className="text-xs">
                                                                    {step.details.status === '0' && (
                                                                        <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded">{t('Scheduled')}</span>
                                                                    )}
                                                                    {step.details.status === '1' && (
                                                                        <span className="bg-green-100 text-green-800 px-2 py-1 rounded">{t('Completed')}</span>
                                                                    )}
                                                                    {step.details.status === '2' && (
                                                                        <span className="bg-red-100 text-red-800 px-2 py-1 rounded">{t('Cancelled')}</span>
                                                                    )}
                                                                    {step.details.status === '3' && (
                                                                        <span className="bg-gray-100 text-gray-800 px-2 py-1 rounded">{t('No Show')}</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Offer Details */}
                                                {step.status === 3 && step.details && (
                                                    <div className="mt-3 p-4 bg-orange-50 rounded-lg border border-orange-200">
                                                        <h4 className="text-sm font-medium text-orange-900 mb-3">{t('Offer Details')}</h4>
                                                        <div className="space-y-2 text-sm text-orange-800 mb-4">
                                                            <div className="flex items-center">
                                                                <Briefcase className="h-4 w-4 mr-2" />
                                                                <span>{t('Position')}: {step.details.position}</span>
                                                            </div>
                                                            <div className="flex items-center">
                                                                <DollarSign className="h-4 w-4 mr-2" />
                                                                <span>{t('Salary')}: ${step.details.salary?.toLocaleString()}/{t('year')}</span>
                                                            </div>
                                                            {step.details.bonus > 0 && (
                                                                <div className="flex items-center">
                                                                    <Star className="h-4 w-4 mr-2" />
                                                                    <span>{t('Bonus')}: ${step.details.bonus?.toLocaleString()}</span>
                                                                </div>
                                                            )}
                                                            <div className="flex items-center">
                                                                <Calendar className="h-4 w-4 mr-2" />
                                                                <span>{t('Start Date')}: {formatDate(step.details.start_date)}</span>
                                                            </div>
                                                            <div className="flex items-center">
                                                                <Clock className="h-4 w-4 mr-2" />
                                                                <span>{t('Expires')}: {formatDate(step.details.expiration_date)}</span>
                                                            </div>
                                                            {step.details.benefits && (
                                                                <div className="mt-2">
                                                                    <span className="font-medium">{t('Benefits')}:</span>
                                                                    <p className="text-xs mt-1">{step.details.benefits}</p>
                                                                </div>
                                                            )}
                                                        </div>

                                                        {/* Accept/Decline Buttons */}
                                                        {step.details.status === '1' && (
                                                            <div className="flex space-x-3">
                                                                <Button
                                                                    className="bg-green-600 hover:bg-green-700 text-white flex-1"
                                                                    onClick={() => handleOfferResponse(step.details.id, '2')}
                                                                    disabled={updatingOffer === step.details.id}
                                                                >
                                                                    <Check className="h-4 w-4 mr-2" />
                                                                    {updatingOffer === step.details.id ? t('Accepting...') : t('Accept Offer')}
                                                                </Button>
                                                                <Button
                                                                    variant="outline"
                                                                    className="border-red-300 text-red-700 hover:bg-red-50 flex-1"
                                                                    onClick={() => handleOfferResponse(step.details.id, '4')}
                                                                    disabled={updatingOffer === step.details.id}
                                                                >
                                                                    {updatingOffer === step.details.id ? t('Declining...') : t('Decline Offer')}
                                                                </Button>
                                                            </div>
                                                        )}

                                                        {step.details.status === '2' && (
                                                            <div className="bg-green-100 text-green-800 px-3 py-2 rounded text-sm font-medium text-center">
                                                                ✓ {t('Offer Accepted')}
                                                            </div>
                                                        )}

                                                        {step.details.status === '3' && (
                                                            <div className="bg-orange-100 text-orange-800 px-3 py-2 rounded text-sm font-medium text-center">
                                                                ↻ {t('Negotiating')}
                                                            </div>
                                                        )}

                                                        {step.details.status === '4' && (
                                                            <div className="bg-red-100 text-red-800 px-3 py-2 rounded text-sm font-medium text-center">
                                                                ✗ {t('Offer Declined')}
                                                            </div>
                                                        )}

                                                        {step.details.status === '5' && (
                                                            <div className="bg-gray-100 text-gray-800 px-3 py-2 rounded text-sm font-medium text-center">
                                                                ⏰ {t('Offer Expired')}
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="lg:w-1/3 space-y-6">
                        {/* Job Details Card */}
                        <Card className="shadow-sm">
                            <CardContent className="p-5">
                                <h3 className="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">{t('Job Details')}</h3>

                                <div className="space-y-4">
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">{jobDetails?.title || 'N/A'}</h4>
                                        <div className="flex items-center text-gray-600 text-sm mb-2">
                                            <MapPin className="h-4 w-4 mr-1" />
                                            {jobDetails?.location || 'N/A'}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3">
                                        <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center">
                                                <DollarSign className="h-4 w-4 mr-2 text-green-600" />
                                                <div>
                                                    <p className="text-xs text-gray-500">{t('Salary')}</p>
                                                    <p className="font-medium text-gray-900">
                                                        {jobDetails ? formatSalary(jobDetails.salaryFrom, jobDetails.salaryTo) : 'N/A'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center">
                                                <Briefcase className="h-4 w-4 mr-2 text-blue-600" />
                                                <div>
                                                    <p className="text-xs text-gray-500">{t('Type')}</p>
                                                    <p className="font-medium text-gray-900">{jobDetails?.jobType || 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">{t('Department')}</p>
                                        <p className="font-medium text-gray-900">{jobDetails?.department || 'N/A'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Applicant Details Card */}
                        <Card className="shadow-sm">
                            <CardContent className="p-5">
                                <h3 className="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">{t('Your Details')}</h3>

                                <div className="space-y-4">
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">{t('Full Name')}</p>
                                        <p className="font-medium text-gray-900">{candidateDetails?.fullName || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">{t('Email')}</p>
                                        <p className="font-medium text-gray-900">{candidateDetails?.email || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">{t('Phone')}</p>
                                        <p className="font-medium text-gray-900">{candidateDetails?.phone || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">{t('Applied Date')}</p>
                                        <p className="font-medium text-gray-900">
                                            {candidateDetails?.appliedDate ? formatDate(candidateDetails.appliedDate) : 'N/A'}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Need Help Card - Dynamic */}
                        {needHelp && (
                            <Card className="shadow-sm bg-blue-50">
                                <CardContent className="p-5">
                                    <h3 className="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-blue-200">{needHelp.title}</h3>

                                    <div className="space-y-4">
                                        <p className="text-sm text-gray-600">
                                            {needHelp.description}
                                        </p>

                                        <div className="space-y-2">
                                            <div className="flex items-center text-sm text-gray-600">
                                                <Mail className="h-4 w-4 mr-2" />
                                                <span>{needHelp.email}</span>
                                            </div>
                                            <div className="flex items-center text-sm text-gray-600">
                                                <Phone className="h-4 w-4 mr-2" />
                                                <span>{needHelp.phone}</span>
                                            </div>
                                        </div>


                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Integration Widgets (Tawk.to, etc.) */}
            {integrationFields.map((field) => (
                <div key={field.id}>
                    {field.component}
                </div>
            ))}
        </FrontendLayout>
    );
}
