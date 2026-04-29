import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PieChart } from '@/components/charts';
import CalendarView from '@/components/calendar-view';
import SocialLinks from '@/components/SocialLinks';
import {
    Users,
    Briefcase,
    Calendar,
    UserCheck,
    TrendingUp,
    Clock,
    AlertTriangle,
    Target,
    CheckCircle,
    Copy,
    ExternalLink,
    Star,
    Award,
    Activity
} from 'lucide-react';
import { toast } from 'sonner';
import { useState, useMemo } from 'react';
import QRCode from 'qrcode';

interface DashboardData {
    overview: {
        totalCandidates: number;
        activeJobPostings: number;
        pendingInterviews: number;
        completedOnboardings: number;
    };
    candidatesByStatus: {
        applied: number;
        shortlisted: number;
        interviewScheduled: number;
        hired: number;
        rejected: number;
    };
    onboardingStatus: {
        pending: number;
        inProgress: number;
        completed: number;
    };
    calendarEvents: any[];
    hiringFunnel?: {
        applications: number;
        shortlisted: number;
        interviewed: number;
        hired: number;
    };
    alerts: {
        overdueInterviews: number;
        pendingReviews: number;
        incompleteOnboardings: number;
        expiringJobs: number;
    };
}

interface RecruitmentProps {
    message: string;
    dashboardData?: DashboardData;
    userSlug?: string;
    welcomeCard?: {
        card_title: string;
        card_description: string;
        button_text: string;
        button_icon: string;
    };
}

export default function RecruitmentIndex({ message, dashboardData, userSlug, welcomeCard }: RecruitmentProps) {
    const { t } = useTranslation();
    const [qrCodeUrl, setQrCodeUrl] = useState('');

    const copyJobPortalLink = () => {
        const link = userSlug
            ? route('recruitment.frontend.careers.jobs.index', { userSlug })
            : route('recruitment.frontend.careers.jobs.index');
        const fullUrl = link.startsWith('http') ? link : window.location.origin + link;
        navigator.clipboard.writeText(fullUrl);
        toast.success(t('Career portal link copied!'));
    };

    useMemo(() => {
        if (userSlug) {
            const link = route('recruitment.frontend.careers.jobs.index', { userSlug });
            const fullUrl = link.startsWith('http') ? link : window.location.origin + link;
            QRCode.toDataURL(fullUrl)
                .then(setQrCodeUrl)
                .catch(() => {
                    toast.error(t('Failed to generate QR code'));
                });
        }
    }, [userSlug]);

    const candidateStatusChart = [
        { name: t('Applied'), value: dashboardData?.candidatesByStatus?.applied || 0, color: '#6b7280' },
        { name: t('Shortlisted'), value: dashboardData?.candidatesByStatus?.shortlisted || 0, color: '#3b82f6' },
        { name: t('Interview'), value: dashboardData?.candidatesByStatus?.interviewScheduled || 0, color: '#f59e0b' },
        { name: t('Hired'), value: dashboardData?.candidatesByStatus?.hired || 0, color: '#10b981' },
        { name: t('Rejected'), value: dashboardData?.candidatesByStatus?.rejected || 0, color: '#ef4444' }
    ];

    const onboardingChart = [
        { name: t('Pending'), value: dashboardData?.onboardingStatus?.pending || 0, color: '#f59e0b' },
        { name: t('In Progress'), value: dashboardData?.onboardingStatus?.inProgress || 0, color: '#3b82f6' },
        { name: t('Completed'), value: dashboardData?.onboardingStatus?.completed || 0, color: '#10b981' }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Recruitment Dashboard') }]}
            pageTitle={t('Recruitment Dashboard')}
        >
            <Head title={t('Recruitment Dashboard')} />

            <div className="space-y-6">
                {/* Hero Section - Left Side Copy Link, Right Side 4 Cards */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Left Side - Welcome Banner with Copy Link */}
                    <div className="bg-gradient-to-r from-primary/90 to-primary/70 rounded-lg p-8 text-white relative overflow-hidden" style={{ minHeight: '200px' }}>
                        {/* Background SVG Pattern */}
                        <div className="absolute inset-0 opacity-25">
                            <svg className="w-full h-full" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stopColor="currentColor" stopOpacity="0.3" />
                                        <stop offset="100%" stopColor="currentColor" stopOpacity="0.1" />
                                    </linearGradient>
                                    <filter id="glow">
                                        <feGaussianBlur stdDeviation="3" result="coloredBlur" />
                                        <feMerge>
                                            <feMergeNode in="coloredBlur" />
                                            <feMergeNode in="SourceGraphic" />
                                        </feMerge>
                                    </filter>
                                </defs>

                                {/* Users Vector */}
                                <g transform="translate(60, 40)" filter="url(#glow)">
                                    <circle cx="15" cy="12" r="8" fill="url(#grad1)">
                                        <animate attributeName="opacity" values="0.8;1;0.8" dur="3s" repeatCount="indefinite" />
                                    </circle>
                                    <path d="M5 35 Q5 25 15 25 Q25 25 25 35" fill="url(#grad1)">
                                        <animate attributeName="opacity" values="0.8;1;0.8" dur="3s" begin="1s" repeatCount="indefinite" />
                                    </path>
                                    <circle cx="15" cy="12" r="5" fill="none" stroke="currentColor" strokeWidth="1.5" opacity="0.4" />
                                    <path d="M7 30 Q7 22 15 22 Q23 22 23 30" stroke="currentColor" strokeWidth="1.5" fill="none" opacity="0.4" />
                                </g>

                                {/* Briefcase Vector */}
                                <g transform="translate(280, 30)" filter="url(#glow)">
                                    <rect width="25" height="18" rx="3" fill="url(#grad1)">
                                        <animateTransform attributeName="transform" type="scale" values="1;1.05;1" dur="3s" repeatCount="indefinite" />
                                    </rect>
                                    <rect x="2" y="4" width="21" height="12" rx="2" fill="none" stroke="currentColor" strokeWidth="1.5" opacity="0.4" />
                                    <rect x="10" y="0" width="5" height="4" rx="1" fill="currentColor" opacity="0.6" />
                                    <line x1="8" y1="10" x2="17" y2="10" stroke="currentColor" strokeWidth="1" opacity="0.5">
                                        <animate attributeName="opacity" values="0.5;0.8;0.5" dur="2s" repeatCount="indefinite" />
                                    </line>
                                </g>

                                {/* Calendar Vector */}
                                <g transform="translate(150, 120)" filter="url(#glow)">
                                    <rect width="30" height="35" rx="4" fill="url(#grad1)">
                                        <animateTransform attributeName="transform" type="scale" values="1;1.05;1" dur="3s" repeatCount="indefinite" />
                                    </rect>
                                    <rect x="3" y="8" width="24" height="24" rx="2" fill="none" stroke="currentColor" strokeWidth="1" opacity="0.4" />
                                    <line x1="8" y1="3" x2="8" y2="12" stroke="currentColor" strokeWidth="2" opacity="0.6" />
                                    <line x1="22" y1="3" x2="22" y2="12" stroke="currentColor" strokeWidth="2" opacity="0.6" />
                                    <circle cx="10" cy="18" r="1.5" fill="currentColor" opacity="0.7">
                                        <animate attributeName="opacity" values="0.7;1;0.7" dur="2s" repeatCount="indefinite" />
                                    </circle>
                                    <circle cx="15" cy="18" r="1.5" fill="currentColor" opacity="0.7">
                                        <animate attributeName="opacity" values="0.7;1;0.7" dur="2s" begin="0.5s" repeatCount="indefinite" />
                                    </circle>
                                    <circle cx="20" cy="18" r="1.5" fill="currentColor" opacity="0.7">
                                        <animate attributeName="opacity" values="0.7;1;0.7" dur="2s" begin="1s" repeatCount="indefinite" />
                                    </circle>
                                </g>

                                {/* User Check Vector */}
                                <g transform="translate(320, 120)" filter="url(#glow)">
                                    <rect width="25" height="25" rx="5" fill="url(#grad1)">
                                        <animateTransform attributeName="transform" type="rotate" values="0 12.5 12.5;5 12.5 12.5;0 12.5 12.5;-5 12.5 12.5;0 12.5 12.5" dur="4s" repeatCount="indefinite" />
                                    </rect>
                                    <path d="M8 12 L11 15 L17 9" stroke="currentColor" strokeWidth="2.5" fill="none" opacity="0.7">
                                        <animate attributeName="stroke-dasharray" values="0 20;20 0;0 20" dur="2s" repeatCount="indefinite" />
                                    </path>
                                </g>

                                {/* Flowing Lines */}
                                <path d="M0 100 Q100 80 200 100 T400 100" stroke="currentColor" strokeWidth="1" fill="none" opacity="0.2">
                                    <animate attributeName="stroke-dasharray" values="0 400;200 200;400 0;0 400" dur="6s" repeatCount="indefinite" />
                                </path>
                                <path d="M0 140 Q150 120 300 140 T400 140" stroke="currentColor" strokeWidth="1" fill="none" opacity="0.15">
                                    <animate attributeName="stroke-dasharray" values="400 0;200 200;0 400;400 0" dur="8s" repeatCount="indefinite" />
                                </path>
                            </svg>
                        </div>

                        <div className="flex items-center justify-between relative z-10 h-full">
                            <div className="flex-1 pr-4">
                                <h2 className="text-3xl font-bold mb-3">{welcomeCard?.card_title || t('Recruitment Hub')}</h2>
                                <p className="text-white/90 mb-4 text-sm max-w-2xl">{welcomeCard?.card_description || t('Streamline hiring process from job posting to onboarding.')}</p>
                                <div className="flex items-center gap-3">
                                    <Button
                                        onClick={copyJobPortalLink}
                                        className="bg-white/20 hover:bg-white/30 border-white/30 backdrop-blur-sm"
                                        size="sm"
                                    >
                                        {welcomeCard?.button_icon && (
                                            <SocialLinks
                                                icon={welcomeCard.button_icon}
                                                className="h-4 w-4 mr-2"
                                            />
                                        )}
                                        {welcomeCard?.button_text || t('Copy Portal Link')}
                                    </Button>
                                </div>
                            </div>
                            {userSlug && qrCodeUrl && (
                                <div className="hidden lg:block bg-white p-4 rounded-lg shadow-lg">
                                    <img src={qrCodeUrl} alt="QR Code" className="w-24 h-24" />
                                    <p className="text-xs text-gray-600 text-center mt-2">{t('Scan to visit')}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Side - 4 Stats Cards */}
                    <div className="grid grid-cols-2 gap-4">
                        <Link href={route('recruitment.candidates.index')}>
                            <Card className="bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200 hover:shadow-md transition-shadow cursor-pointer">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-700">{t('Total Candidates')}</CardTitle>
                                    <Users className="h-4 w-4 text-gray-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-gray-800">{dashboardData?.overview?.totalCandidates || 0}</div>
                                </CardContent>
                            </Card>
                        </Link>
                        <Link href={route('recruitment.job-postings.index')}>
                            <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-md transition-shadow cursor-pointer">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-blue-700">{t('Active Jobs')}</CardTitle>
                                    <Briefcase className="h-4 w-4 text-blue-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-blue-800">{dashboardData?.overview?.activeJobPostings || 0}</div>
                                </CardContent>
                            </Card>
                        </Link>
                        <Link href={route('recruitment.interviews.index')}>
                            <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200 hover:shadow-md transition-shadow cursor-pointer">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-green-700">{t('Interviews')}</CardTitle>
                                    <Calendar className="h-4 w-4 text-green-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-green-800">{dashboardData?.overview?.pendingInterviews || 0}</div>
                                </CardContent>
                            </Card>
                        </Link>
                        <Link href={route('recruitment.candidate-onboardings.index')}>
                            <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-md transition-shadow cursor-pointer">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-orange-700">{t('Onboarded')}</CardTitle>
                                    <UserCheck className="h-4 w-4 text-orange-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-orange-800">{dashboardData?.overview?.completedOnboardings || 0}</div>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>

                {/* Calendar and Charts Row */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Interview Calendar */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5 text-green-600" />
                                {t('Interview Calendar')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CalendarView
                                events={dashboardData?.calendarEvents?.map(event => ({
                                    id: event.id,
                                    title: event.title,
                                    startDate: event.date,
                                    endDate: event.date,
                                    time: event.time || '09:00',
                                    color: event.status === 'pending' ? '#f59e0b' :
                                        event.status === 'completed' ? '#10b77f' :
                                            event.status === 'cancelled' ? '#ef4444' : '#3b82f6',
                                    description: `${t('Interview')}: ${event.title} - ${t('Status')}: ${t(event.status)}`,
                                    type: 'Interview',
                                })) || []}
                                onEventClick={(event) => { }}
                                onDateClick={(date) => { }}
                            />
                        </CardContent>
                    </Card>

                    {/* Combined Charts Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Target className="h-5 w-5 text-blue-600" />
                                {t('Status Overview')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {/* Candidate Status */}
                                <div>
                                    <h4 className="text-sm font-medium mb-3 flex items-center gap-2">
                                        <Users className="h-4 w-4 text-blue-600" />
                                        {t('Candidate Status')}
                                    </h4>
                                    {candidateStatusChart.filter(item => item.value > 0).length > 0 ? (
                                        <>
                                            <PieChart
                                                data={candidateStatusChart.filter(item => item.value > 0)}
                                                dataKey="value"
                                                nameKey="name"
                                                height={170}
                                                donut={true}
                                                showTooltip={true}
                                                showLegend={false}
                                            />
                                            <div className="space-y-1 mt-3">
                                                {candidateStatusChart.filter(item => item.value > 0).map((item, index) => (
                                                    <div key={index} className="flex items-center justify-between p-1 bg-gray-50 rounded">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-2 h-2 rounded-full" style={{ backgroundColor: item.color }}></div>
                                                            <span className="text-xs font-medium">{item.name}</span>
                                                        </div>
                                                        <Badge variant="outline" className="text-xs">{item.value}</Badge>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <PieChart
                                                data={[{ name: t('No Data'), value: 1, color: '#3b82f6' }]}
                                                dataKey="value"
                                                nameKey="name"
                                                height={170}
                                                donut={true}
                                                showTooltip={false}
                                                showLegend={false}
                                            />
                                            <div className="space-y-1 mt-3">
                                                {candidateStatusChart.slice(0, 4).map((item, index) => (
                                                    <div key={index} className="flex items-center justify-between p-1 bg-gray-50 rounded">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-2 h-2 rounded-full" style={{ backgroundColor: item.color }}></div>
                                                            <span className="text-xs font-medium">{item.name}</span>
                                                        </div>
                                                        <Badge variant="outline" className="text-xs">{item.value}</Badge>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>

                                {/* Separator Line */}
                                <div className="relative">
                                    <div className="absolute inset-0 flex items-center">
                                        <div className="w-full border-t border-gray-200"></div>
                                    </div>
                                    <div className="relative flex justify-center text-xs uppercase">
                                        <span className="bg-white px-2 text-gray-500">•••</span>
                                    </div>
                                </div>

                                {/* Onboarding Progress */}
                                <div>
                                    <h4 className="text-sm font-medium mb-3 flex items-center gap-2">
                                        <UserCheck className="h-4 w-4 text-green-600" />
                                        {t('Onboarding Progress')}
                                    </h4>
                                    {onboardingChart.filter(item => item.value > 0).length > 0 ? (
                                        <>
                                            <PieChart
                                                data={onboardingChart.filter(item => item.value > 0)}
                                                dataKey="value"
                                                nameKey="name"
                                                height={170}
                                                donut={true}
                                                showTooltip={true}
                                                showLegend={false}
                                            />
                                            <div className="space-y-1 mt-3">
                                                <div className="flex items-center justify-between p-1 bg-yellow-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('Pending')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-yellow-600">{dashboardData?.onboardingStatus?.pending || 0}</span>
                                                </div>
                                                <div className="flex items-center justify-between p-1 bg-blue-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('In Progress')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-blue-600">{dashboardData?.onboardingStatus?.inProgress || 0}</span>
                                                </div>
                                                <div className="flex items-center justify-between p-1 bg-green-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('Completed')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-green-600">{dashboardData?.onboardingStatus?.completed || 0}</span>
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <PieChart
                                                data={[{ name: t('No Data'), value: 1, color: '#3b82f6' }]}
                                                dataKey="value"
                                                nameKey="name"
                                                height={170}
                                                donut={true}
                                                showTooltip={false}
                                                showLegend={false}
                                            />
                                            <div className="space-y-1 mt-3">
                                                <div className="flex items-center justify-between p-1 bg-yellow-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('Pending')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-yellow-600">{dashboardData?.onboardingStatus?.pending || 0}</span>
                                                </div>
                                                <div className="flex items-center justify-between p-1 bg-blue-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('In Progress')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-blue-600">{dashboardData?.onboardingStatus?.inProgress || 0}</span>
                                                </div>
                                                <div className="flex items-center justify-between p-1 bg-green-50 rounded">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                                        <span className="text-xs font-medium">{t('Completed')}</span>
                                                    </div>
                                                    <span className="text-xs font-bold text-green-600">{dashboardData?.onboardingStatus?.completed || 0}</span>
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Hiring Funnel */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-blue-600" />
                            {t('Hiring Funnel')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {dashboardData?.hiringFunnel ? (
                                <>
                                    <div className="relative">
                                        <div className="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-gray-500 rounded-lg">
                                                    <Users className="h-4 w-4 text-white" />
                                                </div>
                                                <div>
                                                    <span className="font-medium text-gray-900">{t('Applications')}</span>
                                                    <p className="text-xs text-gray-700">{t('Initial stage')}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <span className="px-2 py-1 rounded-full text-sm bg-gray-100 text-gray-800 mb-1 inline-block">
                                                    {dashboardData.hiringFunnel.applications} {t('Candidates')}
                                                </span>
                                                <p className="text-xs text-gray-700">{t('100%')}</p>
                                            </div>
                                        </div>
                                        <div className="absolute left-8 top-full w-0.5 h-4 bg-gray-300"></div>
                                    </div>
                                    <div className="relative">
                                        <div className="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-blue-500 rounded-lg">
                                                    <Target className="h-4 w-4 text-white" />
                                                </div>
                                                <div>
                                                    <span className="font-medium text-blue-900">{t('Shortlisted')}</span>
                                                    <p className="text-xs text-blue-700">{t('Screening passed')}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <span className="px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800 mb-1 inline-block">
                                                    {dashboardData.hiringFunnel.shortlisted} {t('Candidates')}
                                                </span>
                                                <p className="text-xs text-blue-700">{dashboardData.hiringFunnel.applications > 0 ? Math.round((dashboardData.hiringFunnel.shortlisted / dashboardData.hiringFunnel.applications) * 100) : 0}%</p>
                                            </div>
                                        </div>
                                        <div className="absolute left-8 top-full w-0.5 h-4 bg-blue-300"></div>
                                    </div>
                                    <div className="relative">
                                        <div className="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg border border-orange-200">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-orange-500 rounded-lg">
                                                    <Calendar className="h-4 w-4 text-white" />
                                                </div>
                                                <div>
                                                    <span className="font-medium text-orange-900">{t('Interviewed')}</span>
                                                    <p className="text-xs text-orange-700">{t('Interview completed')}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <span className="px-2 py-1 rounded-full text-sm bg-orange-100 text-orange-800 mb-1 inline-block">
                                                    {dashboardData.hiringFunnel.interviewed} {t('Candidates')}
                                                </span>
                                                <p className="text-xs text-orange-700">{dashboardData.hiringFunnel.applications > 0 ? Math.round((dashboardData.hiringFunnel.interviewed / dashboardData.hiringFunnel.applications) * 100) : 0}%</p>
                                            </div>
                                        </div>
                                        <div className="absolute left-8 top-full w-0.5 h-4 bg-orange-300"></div>
                                    </div>
                                    <div className="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-lg border border-green-200">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 bg-green-500 rounded-lg">
                                                <CheckCircle className="h-4 w-4 text-white" />
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-900">{t('Hired')}</span>
                                                <p className="text-xs text-green-700">{t('Successfully hired')}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="px-2 py-1 rounded-full text-sm bg-green-100 text-green-800 mb-1 inline-block">
                                                {dashboardData.hiringFunnel.hired} {t('Candidates')}
                                            </span>
                                            <p className="text-xs text-green-700">{dashboardData.hiringFunnel.applications > 0 ? Math.round((dashboardData.hiringFunnel.hired / dashboardData.hiringFunnel.applications) * 100) : 0}%</p>
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-gray-500">
                                    <TrendingUp className="h-12 w-12 text-gray-300 mb-2" />
                                    <p className="text-sm">{t('No funnel data available')}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Alerts */}
                <div className="grid grid-cols-1 gap-6">
                    {/* Alerts */}
                    <Card className="border-amber-200 bg-amber-50/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-amber-800">
                                <AlertTriangle className="h-5 w-5" />
                                {t('Attention Required')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {dashboardData?.onboardingStatus?.pending > 0 && (
                                    <div className="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-200">
                                        <div className="flex items-center gap-3">
                                            <Clock className="h-5 w-5 text-amber-600" />
                                            <div>
                                                <p className="font-medium text-amber-900">{t('Pending Onboardings')}</p>
                                                <p className="text-sm text-amber-700">{dashboardData.onboardingStatus.pending} {t('items need attention')}</p>
                                            </div>
                                        </div>
                                        <Link href={route('recruitment.candidate-onboardings.index')}>
                                            <Button size="sm" variant="outline" className="border-amber-300 text-amber-700 hover:bg-amber-100">
                                                {t('Review')}
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                                {!dashboardData?.onboardingStatus?.pending && (
                                    <div className="text-center py-8 text-gray-500">
                                        <CheckCircle className="h-12 w-12 mx-auto mb-2 text-green-500" />
                                        <p>{t('All tasks are up to date!')}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
