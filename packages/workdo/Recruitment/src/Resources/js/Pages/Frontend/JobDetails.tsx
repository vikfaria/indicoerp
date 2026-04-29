import React, { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { MapPin, Clock, DollarSign, Briefcase, Star, ArrowLeft, Bookmark, Users, Calendar } from 'lucide-react';
import { formatDate, formatCurrency } from '@/utils/helpers';
import { useFormFields } from '@/hooks/useFormFields';



interface Job {
    id: number;
    encrypted_id: string;
    title: string;
    location: string;
    jobType: string;
    salaryFrom: number;
    salaryTo: number;
    positions: number;
    startDate: string;
    endDate: string;
    postedDate: string;
    skills: string[];
    featured: boolean;
    description: string;
    requirements: string;
    benefits: string;
    job_application?: string;
    application_url?: string;
}

interface JobDetailsProps {
    job: Job;
    companyInfo: {
        ourMission: string;
        companySize: string;
        industry: string;
    };
}

export default function JobDetails({ job, companyInfo }: JobDetailsProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;
    const [savedJobs, setSavedJobs] = useState<number[]>([]);

    const integrationFields = useFormFields('getIntegrationFields', {}, () => { }, {}, 'create', t, 'Recruitment');

    useEffect(() => {
        const storageKey = `savedJobs_${userSlug}`;
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                setSavedJobs(JSON.parse(saved));
            } catch (e) {
                setSavedJobs([]);
            }
        }
    }, [userSlug]);

    const formatSalary = (from: number, to: number) => {
        return `${formatCurrency(from)} - ${formatCurrency(to)}`;
    };

    const handleSaveJob = () => {
        const storageKey = `savedJobs_${userSlug}`;
        let updatedSavedJobs;

        if (savedJobs.includes(job.id)) {
            updatedSavedJobs = savedJobs.filter(id => id !== job.id);
        } else {
            updatedSavedJobs = [...savedJobs, job.id];
        }

        setSavedJobs(updatedSavedJobs);
        localStorage.setItem(storageKey, JSON.stringify(updatedSavedJobs));
    };



    return (
        <FrontendLayout title={job.title}>
            <Head title={`${job.title} - Job Details`} />



            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Main Content */}
                    <div className="lg:w-2/3">
                        {/* Job Header */}
                        <Card className="mb-8">
                            <CardContent className="p-8">
                                {/* Action Buttons */}
                                <div className="flex items-center justify-between mb-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-gray-600 hover:text-gray-900 hover:bg-gray-100"
                                        onClick={() => router.visit(route('recruitment.frontend.careers.jobs.index', { userSlug }))}
                                    >
                                        <ArrowLeft className="h-4 w-4 mr-2" />
                                        {t('Back to Jobs')}
                                    </Button>

                                </div>

                                <div className="flex justify-between items-start mb-6">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-4">
                                            <h1 className="text-3xl font-bold text-gray-900">{job.title}</h1>
                                            <div className="flex gap-2">
                                                {job.featured && (
                                                    <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200 hover:bg-yellow-100">
                                                        <Star className="h-3 w-3 mr-1" />
                                                        {t('Featured')}
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center text-gray-600 mb-6">
                                            <MapPin className="h-5 w-5 mr-2 text-blue-500" />
                                            <span className="font-medium text-lg">{job.location}</span>
                                        </div>

                                        {/* Job Info Grid */}
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <DollarSign className="h-6 w-6 mb-2 text-green-600" />
                                                <p className="text-xs text-gray-500 uppercase tracking-wide mb-1"> {t('Salary Range')} </p>
                                                <p className="font-bold text-gray-900">{formatSalary(job.salaryFrom, job.salaryTo)}</p>
                                            </div>
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <Briefcase className="h-6 w-6 mb-2 text-blue-600" />
                                                <p className="text-xs text-gray-500 uppercase tracking-wide mb-1"> {t('Job Type')} </p>
                                                <p className="font-bold text-gray-900">{job.jobType}</p>
                                            </div>
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <Users className="h-6 w-6 mb-2 text-purple-600" />
                                                <p className="text-xs text-gray-500 uppercase tracking-wide mb-1"> {t('Positions')} </p>
                                                <p className="font-bold text-gray-900">{job.positions} {t('Available')} </p>
                                            </div>
                                            <div className="bg-red-50 rounded-lg p-4">
                                                <Clock className="h-6 w-6 mb-2 text-red-600" />
                                                <p className="text-xs text-gray-500 uppercase tracking-wide mb-1"> {t('Deadline')} </p>
                                                <p className="font-bold text-red-700">{formatDate(job.endDate)}</p>
                                            </div>
                                        </div>

                                        {/* Skills */}
                                        {job.skills && job.skills.length > 0 && (
                                            <div className="mb-6">
                                                <h3 className="text-sm font-medium text-gray-700 mb-3"> {t('Required Skills')} </h3>
                                                <div className="flex flex-wrap gap-2">
                                                    {job.skills.map((skill) => (
                                                        <Badge key={skill} variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                                                            {skill}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Job Description */}
                            {job.description && (
                            <Card className="mb-8">
                                <CardContent className="p-8">
                                    <h2 className="text-2xl font-bold text-gray-900 mb-6"> {t('Job Description')} </h2>
                                    <div
                                        className="prose prose-gray max-w-none prose-headings:text-gray-900 prose-h3:text-xl prose-h4:text-lg prose-ul:list-disc prose-li:mb-2"
                                        dangerouslySetInnerHTML={{ __html: job.description }}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {/* Job Requirements */}
                        {job.requirements && (
                            <Card className="mb-8">
                                <CardContent className="p-8">
                                    <h2 className="text-2xl font-bold text-gray-900 mb-6"> {t('Job Requirements')} </h2>
                                    <div
                                        className="prose prose-gray max-w-none prose-headings:text-gray-900 prose-h3:text-xl prose-h4:text-lg prose-ul:list-disc prose-li:mb-2"
                                        dangerouslySetInnerHTML={{ __html: job.requirements }}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {/* Benefits */}
                        {job.benefits && (
                            <Card className="mb-8">
                                <CardContent className="p-8">
                                    <h2 className="text-2xl font-bold text-gray-900 mb-6"> {t('Benefits')} </h2>
                                    <div
                                        className="prose prose-gray max-w-none prose-headings:text-gray-900 prose-h3:text-xl prose-h4:text-lg prose-ul:list-disc prose-li:mb-2"
                                        dangerouslySetInnerHTML={{ __html: job.benefits }}
                                    />
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="lg:w-1/3">
                        {/* Apply Card */}
                        <Card className="mb-6">
                            <CardContent className="p-6">
                                <div className="text-center mb-6">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2"> {t('Ready to Apply?')} </h3>
                                    <p className="text-gray-600 text-sm"> {t('Join our team and make a difference')} </p>
                                </div>

                                <div className="space-y-4">
                                    <Button
                                        className="w-full bg-slate-700 hover:bg-slate-800 text-white py-3 text-lg"
                                        onClick={() => {
                                            if (job.job_application === 'custom' && job.application_url) {
                                                window.open(job.application_url, '_blank');
                                            } else {
                                                router.visit(route('recruitment.frontend.careers.jobs.apply', { userSlug, id: job.encrypted_id }));
                                            }
                                        }}
                                    >
                                        {t('Apply for This Position')}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        className={`w-full border-slate-300 hover:bg-slate-50 ${savedJobs.includes(job.id) ? 'bg-blue-50 text-blue-600 border-blue-200' : 'text-slate-700'
                                            }`}
                                        onClick={handleSaveJob}
                                    >
                                        {savedJobs.includes(job.id) ? 'Saved' : 'Save for Later'}
                                    </Button>
                                </div>

                                <div className="border-t border-gray-200 my-6"></div>

                                <div className="space-y-4 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500"> {t('Posted')} :</span>
                                        <span className="font-medium">{formatDate(job.postedDate)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500"> {t('Application Deadline')} :</span>
                                        <span className="font-medium">{formatDate(job.endDate)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500"> {t('Start Date')} :</span>
                                        <span className="font-medium">{formatDate(job.startDate)}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Company Info Card */}
                        {(companyInfo.ourMission || companyInfo.companySize || companyInfo.industry) && (
                            <Card>
                                <CardContent className="p-6">
                                    <h3 className="text-lg font-bold text-gray-900 mb-4"> {t('About the Company')} </h3>
                                    <div className="space-y-4">
                                        {companyInfo.ourMission && (
                                            <div>
                                                <h4 className="font-medium text-gray-900 mb-2"> {t('Our Mission')} </h4>
                                                <p className="text-gray-600 text-sm">
                                                    {companyInfo.ourMission}
                                                </p>
                                            </div>
                                        )}
                                        {companyInfo.companySize && (
                                            <div>
                                                <h4 className="font-medium text-gray-900 mb-2"> {t('Company Size')} </h4>
                                                <p className="text-gray-600 text-sm">{companyInfo.companySize}</p>
                                            </div>
                                        )}
                                        {companyInfo.industry && (
                                            <div>
                                                <h4 className="font-medium text-gray-900 mb-2"> {t('Industry')} </h4>
                                                <p className="text-gray-600 text-sm">{companyInfo.industry}</p>
                                            </div>
                                        )}
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
