import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Badge } from '@/components/ui/badge';
import { Megaphone, Calendar, MapPin, Building2, DollarSign, Clock, Users, FileText, CheckCircle, Star } from 'lucide-react';
import { JobPosting, JobPostingShowProps } from './types';
import { formatCurrency, formatDate } from '@/utils/helpers';

export default function Show() {
    const { t } = useTranslation();
    const { jobposting } = usePage<JobPostingShowProps>().props;

    const statusConfig: any = {
        "0": { label: "Draft", class: "bg-gray-100 text-gray-800" },
        "1": { label: "Published", class: "bg-green-100 text-green-800" },
        "2": { label: "Closed", class: "bg-red-100 text-red-800" },
        "draft": { label: "Draft", class: "bg-gray-100 text-gray-800" },
        "active": { label: "Published", class: "bg-green-100 text-green-800" },
        "closed": { label: "Closed", class: "bg-red-100 text-red-800" }
    };
    const statusInfo = statusConfig[jobposting.status] || { label: jobposting.status || '-', class: 'bg-gray-100 text-gray-800' };

    const departmentOptions: any = {"0":"Technology","1":"Accounting","2":"HR section"};

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                {label: t('Job Postings'), url: route('recruitment.job-postings.index')},
                {label: t('Job Posting Details')}
            ]}
            pageTitle={t('Job Posting Details')}
            backUrl={route('recruitment.job-postings.index')}
        >
            <Head title={`${t('Job Posting Details')} - ${jobposting.title}`} />

            <div className="space-y-6">
                {/* Header Card */}
                <Card className="shadow-sm">
                    <CardHeader className="pb-4">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-3 bg-primary/10 rounded-lg">
                                    <Megaphone className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-3 mb-2">
                                        <h1 className="text-2xl font-bold text-gray-900">{jobposting.title}</h1>
                                        {jobposting.is_featured && (
                                            <Badge variant="secondary" className="bg-purple-100 text-purple-800">
                                                <Star className="h-3 w-3 mr-1" />
                                                {t('Featured')}
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-4 text-sm text-gray-600">
                                        <span className="flex items-center gap-1">
                                            <Building2 className="h-4 w-4" />
                                            {jobposting.jobType?.name || jobposting.job_type?.name || '-'}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4" />
                                            {jobposting.location?.name || '-'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusInfo.class}`}>
                                {t(statusInfo.label)}
                            </span>
                        </div>
                    </CardHeader>
                </Card>

                {/* Main Content */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Details */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Job Description */}
                        {jobposting.description && (
                            <Card>
                                <CardHeader>
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <FileText className="h-5 w-5" />
                                        {t('Job Description')}
                                    </h3>
                                </CardHeader>
                                <CardContent>
                                    <div className="prose max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: jobposting.description }} />
                                </CardContent>
                            </Card>
                        )}

                        {/* Requirements */}
                        {jobposting.requirements && (
                            <Card>
                                <CardHeader>
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5" />
                                        {t('Requirements')}
                                    </h3>
                                </CardHeader>
                                <CardContent>
                                    <div className="prose max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: jobposting.requirements }} />
                                </CardContent>
                            </Card>
                        )}

                        {/* Benefits */}
                        {jobposting.benefits && (
                            <Card>
                                <CardHeader>
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <Star className="h-5 w-5" />
                                        {t('Benefits')}
                                    </h3>
                                </CardHeader>
                                <CardContent>
                                    <div className="prose max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: jobposting.benefits }} />
                                </CardContent>
                            </Card>
                        )}

                        {/* Skills */}
                        {jobposting.skills && (
                            <Card>
                                <CardHeader>
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5" />
                                        {t('Required Skills')}
                                    </h3>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {Array.isArray(jobposting.skills) ? (
                                            jobposting.skills.map((skill: string, index: number) => (
                                                <Badge key={index} variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                                                    {skill}
                                                </Badge>
                                            ))
                                        ) : (
                                            jobposting.skills.split(',').map((skill: string, index: number) => (
                                                <Badge key={index} variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                                                    {skill.trim()}
                                                </Badge>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Terms & Conditions */}
                        {jobposting.terms_condition && (
                            <Card>
                                <CardHeader>
                                    <h3 className="text-lg font-semibold flex items-center gap-2">
                                        <FileText className="h-5 w-5" />
                                        {t('Terms & Conditions')}
                                    </h3>
                                </CardHeader>
                                <CardContent>
                                    <div className="prose max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: jobposting.terms_condition }} />
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right Column - Summary */}
                    <div className="space-y-6">
                        {/* Job Summary */}
                        <Card>
                            <CardHeader>
                                <h3 className="text-lg font-semibold">{t('Job Summary')}</h3>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-600">{t('Job Type')}</span>
                                    <span className="text-sm font-medium">{jobposting.jobType?.name || jobposting.job_type?.name || '-'}</span>
                                </div>
                                <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-600">{t('Location')}</span>
                                    <span className="text-sm font-medium">{jobposting.location?.name || '-'}</span>
                                </div>
                                <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-600">{t('Branch')}</span>
                                    <span className="text-sm font-medium">{jobposting.branch_name || '-'}</span>
                                </div>
                                {(jobposting.min_salary || jobposting.max_salary) && (
                                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-600 flex items-center gap-1">
                                            {t('Salary Range')}
                                        </span>
                                        <span className="text-sm font-medium">
                                            {formatCurrency(jobposting.min_salary) || 0} - {formatCurrency(jobposting.max_salary) || 0}
                                        </span>
                                    </div>
                                )}
                                {(jobposting.min_experience || jobposting.max_experience) && (
                                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-600 flex items-center gap-1">
                                            <Clock className="h-4 w-4" />
                                            {t('Experience')}
                                        </span>
                                        <span className="text-sm font-medium">
                                            {jobposting.min_experience || 0} - {jobposting.max_experience || 0} {t('years')}
                                        </span>
                                    </div>
                                )}
                                {jobposting.application_deadline && (
                                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-600 flex items-center gap-1">
                                            <Calendar className="h-4 w-4" />
                                            {t('Application Deadline')}
                                        </span>
                                        <span className="text-sm font-medium">{formatDate(jobposting.application_deadline)}</span>
                                    </div>
                                )}
                                <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span className="text-sm text-gray-600">{t('Published')}</span>
                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-sm ${jobposting.is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        }`}>
                                        {jobposting.is_published ? t('Yes') : t('No')}
                                    </span>
                                </div>
                                {jobposting.job_application && (
                                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-600">{t('Application Type')}</span>
                                        <span className="text-sm font-medium capitalize">{jobposting.job_application}</span>
                                    </div>
                                )}
                                {jobposting.application_url && (
                                    <div className="py-2 border-b border-gray-100">
                                        <span className="text-sm text-gray-600 block mb-1">{t('Application URL')}</span>
                                        <a href={jobposting.application_url} target="_blank" rel="noopener noreferrer" className="text-sm font-medium text-blue-600 hover:text-blue-800 break-all">
                                            {jobposting.application_url}
                                        </a>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
