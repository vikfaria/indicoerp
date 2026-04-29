import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft, MapPin, DollarSign, Briefcase, Star, Upload, FileText, File, Image, X } from 'lucide-react';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { DatePicker } from '@/components/ui/date-picker';
import { useFormFields } from '@/hooks/useFormFields';
import { formatCurrency } from '@/utils/helpers';
import InputError from '@/components/ui/input-error';

interface Job {
    id: number;
    encrypted_id: string;
    title: string;
    location: string;
    jobType: string;
    salaryFrom: number;
    salaryTo: number;
    skills: string[];
    featured: boolean;
    terms_condition?: string;
    show_terms_condition?: boolean;
}

interface CustomQuestion {
    id: number;
    question: string;
    type: string;
    options: string[];
    is_required: boolean;
}

interface JobApplyProps {
    job: Job;
    applicationTips: { title: string; }[];
    storageSettings: {
        allowedFileTypes: string;
        maxUploadSize: number;
    };
    customQuestions: CustomQuestion[];
    jobPostingSettings: {
        applicant: string[];
        visibility: string[];
    };
}

export default function JobApply({ job, applicationTips, storageSettings, customQuestions, jobPostingSettings }: JobApplyProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;
    const errors = props.errors as Record<string, string> || {};

    const integrationFields = useFormFields('getIntegrationFields', {}, () => {}, {}, 'create', t, 'Recruitment');

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        gender: '',
        dateOfBirth: '',
        country: '',
        state: '',
        city: '',
        currentCompany: '',
        currentPosition: '',
        experienceYears: '',
        currentSalary: '',
        expectedSalary: '',
        noticePeriod: '',
        skills: '',
        education: '',
        portfolioUrl: '',
        linkedinUrl: ''
    });
    const [customAnswers, setCustomAnswers] = useState<Record<string, string | string[]>>({});
    const [uploadedFiles, setUploadedFiles] = useState<{resume?: File, coverLetter?: File, profilePhoto?: File}>({});
    const [filePreviews, setFilePreviews] = useState<{resume?: string, coverLetter?: string, profilePhoto?: string}>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [termsAccepted, setTermsAccepted] = useState(false);

    const formatSalary = (from: number, to: number) => {
        return `${formatCurrency(from)} - ${formatCurrency(to)}`;
    };

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const formDataToSubmit = new FormData();

        // Add form fields
        Object.entries(formData).forEach(([key, value]) => {
            if (value) formDataToSubmit.append(key, value);
        });

        // Add custom question answers
        Object.entries(customAnswers).forEach(([key, value]) => {
            if (value) {
                if (Array.isArray(value)) {
                    // For checkbox arrays, join with commas
                    formDataToSubmit.append(key, value.join(','));
                } else {
                    formDataToSubmit.append(key, value as string);
                }
            }
        });

        // Add files
        if (uploadedFiles.resume) formDataToSubmit.append('resume', uploadedFiles.resume);
        if (uploadedFiles.coverLetter) formDataToSubmit.append('coverLetter', uploadedFiles.coverLetter);
        if (uploadedFiles.profilePhoto) formDataToSubmit.append('profilePhoto', uploadedFiles.profilePhoto);

        // Use Inertia router for form submission
        router.post(route('recruitment.frontend.careers.jobs.apply.submit', { userSlug, id: job.encrypted_id }), formDataToSubmit, {
            onStart: () => setIsSubmitting(true),
            onFinish: () => setIsSubmitting(false),
            onError: () => setIsSubmitting(false)
        });
    };

    const isFormValid = () => {
        const basicFieldsValid = formData.name && formData.email && formData.experienceYears;

        // Check required custom questions
        const requiredCustomQuestionsValid = customQuestions.every(question => {
            if (question.is_required) {
                const fieldName = `custom_question_${question.id}`;
                const value = customAnswers[fieldName];
                if (Array.isArray(value)) {
                    return value.length > 0;
                } else {
                    return value && value.toString().trim() !== '';
                }
            }
            return true;
        });

        // Check terms acceptance if terms are shown
        const termsValid = !job.show_terms_condition || termsAccepted;

        return basicFieldsValid && requiredCustomQuestionsValid && termsValid;
    };

    return (
        <FrontendLayout title={`Apply - ${job.title}`}>
            <Head title={`Apply for ${job.title}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Main Application Form */}
                    <div className="lg:w-2/3">
                        <div className="mb-8">
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-gray-600 hover:text-gray-900 hover:bg-gray-100 mb-6"
                                onClick={() => router.visit(route('recruitment.frontend.careers.jobs.show', { userSlug, id: job.encrypted_id }))}
                            >
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                {t('Back to Job Details')}
                            </Button>
                            <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('Apply for Position')}</h1>
                            <p className="text-gray-600">{t('Please fill out the form below to submit your application')}</p>
                        </div>

                        <Card className="shadow-sm">
                            <CardContent className="p-6">
                                <form onSubmit={handleSubmit} className="space-y-8">
                                    {/* Personal Information Section */}
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-200">{t('Personal Information')}</h2>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Full Name */}
                                            <div className="md:col-span-2">
                                                <Label htmlFor="name" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Full Name')}
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    type="text"
                                                    value={formData.name}
                                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                                    placeholder={t('Enter your full name')}
                                                    className="h-11"
                                                    required
                                                />
                                            </div>

                                            {/* Email */}
                                            <div>
                                                <Label htmlFor="email" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Email Address')}
                                                </Label>
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    value={formData.email}
                                                    onChange={(e) => handleInputChange('email', e.target.value)}
                                                    placeholder={t('Enter your email address')}
                                                    className="h-11"
                                                    required
                                                />
                                            </div>

                                            {/* Phone */}
                                            <div>
                                                <PhoneInputComponent
                                                    label={t('Phone Number')}
                                                    value={formData.phone}
                                                    onChange={(value) => handleInputChange('phone', value)}
                                                    placeholder={t('Enter your phone number')}
                                                    id="phone"
                                                    name="phone"
                                                />
                                            </div>

                                            {/* Gender */}
                                            {jobPostingSettings.applicant.includes('gender') && (
                                                <div>
                                                    <Label htmlFor="gender" className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('Gender')}
                                                    </Label>
                                                    <Select value={formData.gender} onValueChange={(value) => handleInputChange('gender', value)}>
                                                        <SelectTrigger className="h-11">
                                                            <SelectValue placeholder={t('Select Gender')} />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="male">{t('Male')}</SelectItem>
                                                            <SelectItem value="female">{t('Female')}</SelectItem>
                                                            <SelectItem value="other">{t('Other')}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            )}

                                            {/* Date of Birth */}
                                            {jobPostingSettings.applicant.includes('date_of_birth') && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('Date of Birth')}
                                                    </Label>
                                                    <DatePicker
                                                        value={formData.dateOfBirth}
                                                        onChange={(date) => handleInputChange('dateOfBirth', date)}
                                                        placeholder={t('Select Date of Birth')}
                                                        maxDate={new Date(Date.now() - 24 * 60 * 60 * 1000)}
                                                    />
                                                </div>
                                            )}

                                            {/* Country */}
                                            {jobPostingSettings.applicant.includes('country') && (
                                                <div>
                                                    <Label htmlFor="country" className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('Country')}
                                                    </Label>
                                                    <Input
                                                        id="country"
                                                        type="text"
                                                        value={formData.country}
                                                        onChange={(e) => handleInputChange('country', e.target.value)}
                                                        placeholder={t('Enter your country')}
                                                        className="h-11"
                                                        required
                                                    />
                                                </div>
                                            )}

                                            {/* State */}
                                            {jobPostingSettings.applicant.includes('country') && (
                                                <div>
                                                    <Label htmlFor="state" className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('State')}
                                                    </Label>
                                                    <Input
                                                        id="state"
                                                        type="text"
                                                        value={formData.state}
                                                        onChange={(e) => handleInputChange('state', e.target.value)}
                                                        placeholder={t('Enter your state')}
                                                        className="h-11"
                                                    />
                                                </div>
                                            )}

                                            {/* City */}
                                            {jobPostingSettings.applicant.includes('country') && (
                                                <div>
                                                    <Label htmlFor="city" className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('City')}
                                                    </Label>
                                                    <Input
                                                        id="city"
                                                        type="text"
                                                        value={formData.city}
                                                        onChange={(e) => handleInputChange('city', e.target.value)}
                                                        placeholder={t('Enter your city')}
                                                        className="h-11"
                                                    />
                                                </div>
                                            )}

                                            {/* Profile Photo */}
                                            {jobPostingSettings.visibility.includes('profile_image') && (
                                                <div>
                                                    <Label htmlFor="profilePhoto" className="text-sm font-medium text-gray-700 mb-2 block">
                                                        {t('Profile Photo')}
                                                    </Label>
                                                    {!uploadedFiles.profilePhoto ? (
                                                        <div className="mt-1">
                                                            <Input
                                                                id="profilePhoto"
                                                                name="profilePhoto"
                                                                type="file"
                                                                accept={storageSettings.allowedFileTypes.split(',').map(type => `.${type.trim()}`).join(',')}
                                                                onChange={(e) => {
                                                                    const file = e.target.files?.[0];
                                                                    if (file) {
                                                                        setUploadedFiles(prev => ({...prev, profilePhoto: file}));
                                                                        if (file.type.startsWith('image/')) {
                                                                            const reader = new FileReader();
                                                                            reader.onload = (e) => {
                                                                                setFilePreviews(prev => ({...prev, profilePhoto: e.target?.result as string}));
                                                                            };
                                                                            reader.readAsDataURL(file);
                                                                        }
                                                                    }
                                                                }}
                                                                className="h-11"
                                                            />
                                                            <p className="text-xs text-gray-500 mt-1">{t('JPG, PNG, GIF up to')} {storageSettings.maxUploadSize} {t('MB')}</p>
                                                        </div>
                                                    ) : (
                                                        <div className="mt-1 relative">
                                                            {filePreviews.profilePhoto ? (
                                                                <div className="relative inline-block">
                                                                    <img src={filePreviews.profilePhoto} alt="Profile photo preview" className="w-32 h-32 object-cover rounded-lg border" />
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setUploadedFiles(prev => ({...prev, profilePhoto: undefined}));
                                                                            setFilePreviews(prev => ({...prev, profilePhoto: undefined}));
                                                                        }}
                                                                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                                                    >
                                                                        <X className="w-4 h-4" />
                                                                    </button>
                                                                    <p className="text-sm text-gray-700 mt-2">{uploadedFiles.profilePhoto.name}</p>
                                                                </div>
                                                            ) : (
                                                                <div className="flex items-center space-x-3 p-3 border rounded-lg bg-gray-50">
                                                                    <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                                        <Image className="w-6 h-6 text-blue-600" />
                                                                    </div>
                                                                    <div className="flex-1">
                                                                        <p className="text-sm font-medium text-gray-900">{uploadedFiles.profilePhoto.name}</p>
                                                                        <p className="text-xs text-gray-500">{(uploadedFiles.profilePhoto.size / 1024 / 1024).toFixed(2)} MB</p>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setUploadedFiles(prev => ({...prev, profilePhoto: undefined}));
                                                                            setFilePreviews(prev => ({...prev, profilePhoto: undefined}));
                                                                        }}
                                                                        className="text-red-500 hover:text-red-700"
                                                                    >
                                                                        <X className="w-5 h-5" />
                                                                    </button>
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Professional Information Section */}
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-200"> {t('Professional Information')} </h2>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Current Company */}
                                            <div>
                                                <Label htmlFor="currentCompany" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Current Company')}
                                                </Label>
                                                <Input
                                                    id="currentCompany"
                                                    type="text"
                                                    value={formData.currentCompany}
                                                    onChange={(e) => handleInputChange('currentCompany', e.target.value)}
                                                    placeholder={t('Enter your current company')}
                                                    className="h-11"
                                                />
                                            </div>

                                            {/* Current Position */}
                                            <div>
                                                <Label htmlFor="currentPosition" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Current Position')}
                                                </Label>
                                                <Input
                                                    id="currentPosition"
                                                    type="text"
                                                    value={formData.currentPosition}
                                                    onChange={(e) => handleInputChange('currentPosition', e.target.value)}
                                                    placeholder={t('Enter your current position')}
                                                    className="h-11"
                                                />
                                            </div>

                                            {/* Experience Years */}
                                            <div>
                                                <Label htmlFor="experienceYears" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Experience (Years)')}
                                                </Label>
                                                <Input
                                                    id="experienceYears"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    value={formData.experienceYears}
                                                    onChange={(e) => handleInputChange('experienceYears', e.target.value)}
                                                    placeholder={t('Enter experience years')}
                                                    className="h-11"
                                                    required
                                                />
                                            </div>

                                            {/* Current Salary */}
                                            <div>
                                                <Label htmlFor="currentSalary" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Current Salary')}
                                                </Label>
                                                <Input
                                                    id="currentSalary"
                                                    type="number"
                                                    min="0"
                                                    value={formData.currentSalary}
                                                    onChange={(e) => handleInputChange('currentSalary', e.target.value)}
                                                    placeholder={t('Enter current salary')}
                                                    className="h-11"
                                                />
                                            </div>

                                            {/* Expected Salary */}
                                            <div>
                                                <Label htmlFor="expectedSalary" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Expected Salary')}
                                                </Label>
                                                <Input
                                                    id="expectedSalary"
                                                    type="number"
                                                    min="0"
                                                    value={formData.expectedSalary}
                                                    onChange={(e) => handleInputChange('expectedSalary', e.target.value)}
                                                    placeholder={t('Enter expected salary')}
                                                    className="h-11"
                                                />
                                            </div>

                                            {/* Notice Period */}
                                            <div>
                                                <Label htmlFor="noticePeriod" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Notice Period')}
                                                </Label>
                                                <Input
                                                    id="noticePeriod"
                                                    type="text"
                                                    value={formData.noticePeriod}
                                                    onChange={(e) => handleInputChange('noticePeriod', e.target.value)}
                                                    placeholder={t('Enter notice period')}
                                                    className="h-11"
                                                />
                                            </div>

                                            {/* Skills */}
                                            <div className="md:col-span-2">
                                                <Label htmlFor="skills" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Skills')}
                                                </Label>
                                                <Textarea
                                                    id="skills"
                                                    value={formData.skills}
                                                    onChange={(e) => handleInputChange('skills', e.target.value)}
                                                    placeholder={t('Enter skills')}
                                                    rows={3}
                                                />
                                            </div>

                                            {/* Education */}
                                            <div className="md:col-span-2">
                                                <Label htmlFor="education" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Education')}
                                                </Label>
                                                <Textarea
                                                    id="education"
                                                    value={formData.education}
                                                    onChange={(e) => handleInputChange('education', e.target.value)}
                                                    placeholder={t('Enter education')}
                                                    rows={3}
                                                />
                                            </div>

                                            {/* Portfolio URL */}
                                            <div>
                                                <Label htmlFor="portfolioUrl" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('Portfolio URL')}
                                                </Label>
                                                <Input
                                                    id="portfolioUrl"
                                                    type="url"
                                                    value={formData.portfolioUrl}
                                                    onChange={(e) => handleInputChange('portfolioUrl', e.target.value)}
                                                    placeholder={t('Enter portfolio URL')}
                                                    className="h-11"
                                                />
                                                <InputError message={errors.portfolioUrl} />
                                            </div>

                                            {/* LinkedIn URL */}
                                            <div>
                                                <Label htmlFor="linkedinUrl" className="text-sm font-medium text-gray-700 mb-2 block">
                                                    {t('LinkedIn URL')}
                                                </Label>
                                                <Input
                                                    id="linkedinUrl"
                                                    type="url"
                                                    value={formData.linkedinUrl}
                                                    onChange={(e) => handleInputChange('linkedinUrl', e.target.value)}
                                                    placeholder={t('Enter LinkedIn URL')}
                                                    className="h-11"
                                                />
                                                <InputError message={errors.linkedinUrl} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Documents Section */}
                                    {(jobPostingSettings.visibility.includes('resume') || jobPostingSettings.visibility.includes('cover_letter')) && (
                                        <div>
                                            <h2 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-200"> {t('Documents')} </h2>
                                            <div className="space-y-4">
                                                {/* Resume Upload */}
                                                {jobPostingSettings.visibility.includes('resume') && (
                                                    <div>
                                                        <Label htmlFor="resume" className="text-sm font-medium text-gray-700 mb-2 block">
                                                            {t('Resume/CV')}
                                                        </Label>
                                                        {!uploadedFiles.resume ? (
                                                            <div className="mt-1">
                                                                <Input
                                                                    id="resume"
                                                                    name="resume"
                                                                    type="file"
                                                                    accept={storageSettings.allowedFileTypes.split(',').map(type => `.${type.trim()}`).join(',')}
                                                                    onChange={(e) => {
                                                                        const file = e.target.files?.[0];
                                                                        if (file) {
                                                                            setUploadedFiles(prev => ({...prev, resume: file}));
                                                                            if (file.type.startsWith('image/')) {
                                                                                const reader = new FileReader();
                                                                                reader.onload = (e) => {
                                                                                    setFilePreviews(prev => ({...prev, resume: e.target?.result as string}));
                                                                                };
                                                                                reader.readAsDataURL(file);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="h-11"
                                                                />
                                                                <p className="text-xs text-gray-500 mt-1">{storageSettings.allowedFileTypes} {t('up to')} {storageSettings.maxUploadSize} {t('MB')}</p>
                                                            </div>
                                                        ) : (
                                                            <div className="mt-1 relative">
                                                                {filePreviews.resume ? (
                                                                    <div className="relative inline-block">
                                                                        <img src={filePreviews.resume} alt="Resume preview" className="w-32 h-32 object-cover rounded-lg border" />
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setUploadedFiles(prev => ({...prev, resume: undefined}));
                                                                                setFilePreviews(prev => ({...prev, resume: undefined}));
                                                                            }}
                                                                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                                                        >
                                                                            <X className="w-4 h-4" />
                                                                        </button>
                                                                        <p className="text-sm text-gray-700 mt-2">{uploadedFiles.resume.name}</p>
                                                                    </div>
                                                                ) : (
                                                                    <div className="flex items-center space-x-3 p-3 border rounded-lg bg-gray-50">
                                                                        {uploadedFiles.resume.type.includes('pdf') ? (
                                                                            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                                                                <FileText className="w-6 h-6 text-red-600" />
                                                                            </div>
                                                                        ) : uploadedFiles.resume.type.includes('doc') ? (
                                                                            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                                                <File className="w-6 h-6 text-blue-600" />
                                                                            </div>
                                                                        ) : (
                                                                            <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                                                <File className="w-6 h-6 text-gray-600" />
                                                                            </div>
                                                                        )}
                                                                        <div className="flex-1">
                                                                            <p className="text-sm font-medium text-gray-900">{uploadedFiles.resume.name}</p>
                                                                            <p className="text-xs text-gray-500">{(uploadedFiles.resume.size / 1024 / 1024).toFixed(2)} {t('MB')} </p>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setUploadedFiles(prev => ({...prev, resume: undefined}));
                                                                                setFilePreviews(prev => ({...prev, resume: undefined}));
                                                                            }}
                                                                            className="text-red-500 hover:text-red-700"
                                                                        >
                                                                            <X className="w-5 h-5" />
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                )}

                                                {/* Cover Letter Upload */}
                                                {jobPostingSettings.visibility.includes('cover_letter') && (
                                                    <div>
                                                        <Label htmlFor="coverLetter" className="text-sm font-medium text-gray-700 mb-2 block">
                                                            {t('Cover Letter')}
                                                        </Label>
                                                        {!uploadedFiles.coverLetter ? (
                                                            <div className="mt-1">
                                                                <Input
                                                                    id="coverLetter"
                                                                    name="coverLetter"
                                                                    type="file"
                                                                    accept={storageSettings.allowedFileTypes.split(',').map(type => `.${type.trim()}`).join(',')}
                                                                    onChange={(e) => {
                                                                        const file = e.target.files?.[0];
                                                                        if (file) {
                                                                            setUploadedFiles(prev => ({...prev, coverLetter: file}));
                                                                            if (file.type.startsWith('image/')) {
                                                                                const reader = new FileReader();
                                                                                reader.onload = (e) => {
                                                                                    setFilePreviews(prev => ({...prev, coverLetter: e.target?.result as string}));
                                                                                };
                                                                                reader.readAsDataURL(file);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="h-11"
                                                                />
                                                                <p className="text-xs text-gray-500 mt-1">{storageSettings.allowedFileTypes} {t('up to')} {storageSettings.maxUploadSize} {t('MB')}</p>
                                                            </div>
                                                        ) : (
                                                            <div className="mt-1 relative">
                                                                {filePreviews.coverLetter ? (
                                                                    <div className="relative inline-block">
                                                                        <img src={filePreviews.coverLetter} alt="Cover letter preview" className="w-32 h-32 object-cover rounded-lg border" />
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setUploadedFiles(prev => ({...prev, coverLetter: undefined}));
                                                                                setFilePreviews(prev => ({...prev, coverLetter: undefined}));
                                                                            }}
                                                                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                                                        >
                                                                            <X className="w-4 h-4" />
                                                                        </button>
                                                                        <p className="text-sm text-gray-700 mt-2">{uploadedFiles.coverLetter.name}</p>
                                                                    </div>
                                                                ) : (
                                                                    <div className="flex items-center space-x-3 p-3 border rounded-lg bg-gray-50">
                                                                        {uploadedFiles.coverLetter.type.includes('pdf') ? (
                                                                            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                                                                <FileText className="w-6 h-6 text-red-600" />
                                                                            </div>
                                                                        ) : uploadedFiles.coverLetter.type.includes('doc') ? (
                                                                            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                                                <File className="w-6 h-6 text-blue-600" />
                                                                            </div>
                                                                        ) : (
                                                                            <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                                                <File className="w-6 h-6 text-gray-600" />
                                                                            </div>
                                                                        )}
                                                                        <div className="flex-1">
                                                                            <p className="text-sm font-medium text-gray-900">{uploadedFiles.coverLetter.name}</p>
                                                                            <p className="text-xs text-gray-500">{(uploadedFiles.coverLetter.size / 1024 / 1024).toFixed(2)} MB</p>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setUploadedFiles(prev => ({...prev, coverLetter: undefined}));
                                                                                setFilePreviews(prev => ({...prev, coverLetter: undefined}));
                                                                            }}
                                                                            className="text-red-500 hover:text-red-700"
                                                                        >
                                                                            <X className="w-5 h-5" />
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Custom Questions Section */}
                                    {customQuestions.length > 0 && (
                                        <div>
                                            <h2 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-200"> {t('Application Questions')} </h2>
                                            <div className="space-y-6">
                                                {customQuestions.map((question) => {
                                                    const fieldName = `custom_question_${question.id}`;
                                                    return (
                                                        <div key={question.id}>
                                                            <Label className="text-sm font-medium text-gray-700 mb-2 block">
                                                                {question.question}
                                                                {question.is_required && <span className="text-red-500 ml-1">*</span>}
                                                            </Label>
                                                            {question.type === 'text' && (
                                                                <Input
                                                                    type="text"
                                                                    value={customAnswers[fieldName] || ''}
                                                                    onChange={(e) => setCustomAnswers(prev => ({ ...prev, [fieldName]: e.target.value }))}
                                                                    className="h-11"
                                                                    required={question.is_required}
                                                                />
                                                            )}
                                                            {question.type === 'number' && (
                                                                <Input
                                                                    type="number"
                                                                    value={customAnswers[fieldName] || ''}
                                                                    onChange={(e) => setCustomAnswers(prev => ({ ...prev, [fieldName]: e.target.value }))}
                                                                    className="h-11"
                                                                    required={question.is_required}
                                                                />
                                                            )}
                                                            {question.type === 'textarea' && (
                                                                <Textarea
                                                                    value={customAnswers[fieldName] || ''}
                                                                    onChange={(e) => setCustomAnswers(prev => ({ ...prev, [fieldName]: e.target.value }))}
                                                                    rows={3}
                                                                    required={question.is_required}
                                                                />
                                                            )}
                                                            {question.type === 'select' && (
                                                                <Select
                                                                    value={customAnswers[fieldName] as string || ''}
                                                                    onValueChange={(value) => setCustomAnswers(prev => ({ ...prev, [fieldName]: value }))}
                                                                >
                                                                    <SelectTrigger className="h-11">
                                                                        <SelectValue placeholder="Select an option" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {question.options.map((option, index) => (
                                                                            <SelectItem key={index} value={option}>
                                                                                {option}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            )}
                                                            {question.type === 'radio' && (
                                                                <RadioGroup
                                                                    value={customAnswers[fieldName] as string || ''}
                                                                    onValueChange={(value) => setCustomAnswers(prev => ({ ...prev, [fieldName]: value }))}
                                                                    className="flex flex-wrap gap-6"
                                                                >
                                                                    {question.options.map((option, index) => (
                                                                        <div key={index} className="flex items-center space-x-2">
                                                                            <RadioGroupItem
                                                                                value={option}
                                                                                id={`${fieldName}_${index}`}
                                                                                className="w-4 h-4"
                                                                            />
                                                                            <Label
                                                                                htmlFor={`${fieldName}_${index}`}
                                                                                className="text-sm text-gray-700 cursor-pointer"
                                                                            >
                                                                                {option}
                                                                            </Label>
                                                                        </div>
                                                                    ))}
                                                                </RadioGroup>
                                                            )}
                                                            {question.type === 'checkbox' && (
                                                                <div className="space-y-2">
                                                                    {question.options.map((option, index) => {
                                                                        const currentValues = (customAnswers[fieldName] as string[]) || [];
                                                                        const isChecked = currentValues.includes(option);

                                                                        return (
                                                                            <div key={index} className="flex items-center space-x-2">
                                                                                <Checkbox
                                                                                    id={`${fieldName}_${index}`}
                                                                                    checked={isChecked}
                                                                                    onCheckedChange={(checked) => {
                                                                                        setCustomAnswers(prev => {
                                                                                            const currentValues = (prev[fieldName] as string[]) || [];
                                                                                            if (checked) {
                                                                                                return { ...prev, [fieldName]: [...currentValues, option] };
                                                                                            } else {
                                                                                                return { ...prev, [fieldName]: currentValues.filter(v => v !== option) };
                                                                                            }
                                                                                        });
                                                                                    }}
                                                                                />
                                                                                <Label htmlFor={`${fieldName}_${index}`} className="text-sm font-normal cursor-pointer">
                                                                                    {option}
                                                                                </Label>
                                                                            </div>
                                                                        );
                                                                    })}
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}

                                    {/* Terms & Conditions Section */}
                                    {job.show_terms_condition && job.terms_condition && (
                                        <div>
                                            <h2 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-200"> {t('Terms & Conditions')} </h2>
                                            <div className="flex items-start space-x-2">
                                                <Checkbox
                                                    id="terms_accepted"
                                                    checked={termsAccepted}
                                                    onCheckedChange={(checked) => setTermsAccepted(!!checked)}
                                                    className="mt-1"
                                                />
                                                <Label htmlFor="terms_accepted" className="text-sm text-gray-700 cursor-pointer leading-relaxed">
                                                    {t('I have read and agree to the')} {' '}
                                                    <a
                                                        href={route('recruitment.frontend.careers.jobs.terms', { userSlug, id: job.encrypted_id })}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-blue-600 hover:text-blue-800 underline font-medium"
                                                    >
                                                        {t('terms and conditions')}
                                                    </a>
                                                    {' '}{t('for this position')}.
                                                </Label>
                                            </div>
                                        </div>
                                    )}

                                    {/* Submit Button */}
                                    <div className="pt-6 border-t border-gray-200">
                                        <div className="flex justify-end">
                                            <Button
                                                type="submit"
                                                className="bg-slate-700 hover:bg-slate-800 text-white px-8 h-11"
                                                disabled={!isFormValid() || isSubmitting}
                                            >
                                                {isSubmitting ? t('Submitting...') : t('Submit Application')}
                                            </Button>
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Job Summary Sidebar */}
                    <div className="lg:w-1/3">
                        <Card>
                            <CardContent className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4"> {t('Job Summary')} </h3>

                                <div className="space-y-4">
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-2">{job.title}</h4>
                                        <div className="flex items-center text-gray-600 text-sm mb-2">
                                            <MapPin className="h-4 w-4 mr-1" />
                                            {job.location}
                                        </div>
                                        <div className="flex gap-2 mb-3">
                                            {job.featured && (
                                                <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200 hover:bg-yellow-100">
                                                    <Star className="h-3 w-3 mr-1" />
                                                    {t('Featured')}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3">
                                        <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center">
                                                <DollarSign className="h-4 w-4 mr-2 text-green-600" />
                                                <div>
                                                    <p className="text-xs text-gray-500"> {t('Salary')} </p>
                                                    <p className="font-medium text-gray-900">{formatSalary(job.salaryFrom, job.salaryTo)}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center">
                                                <Briefcase className="h-4 w-4 mr-2 text-blue-600" />
                                                <div>
                                                    <p className="text-xs text-gray-500"> {t('Type')} </p>
                                                    <p className="font-medium text-gray-900">{job.jobType}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {job.skills && job.skills.length > 0 && (
                                        <div>
                                            <p className="text-xs text-gray-500 mb-2"> {t('Required Skills')} </p>
                                            <div className="flex flex-wrap gap-1">
                                                {job.skills.map((skill) => (
                                                    <Badge key={skill} variant="outline" className="text-xs bg-blue-50 text-blue-700 border-blue-200">
                                                        {skill}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {applicationTips.length > 0 && (
                                    <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                                        <h4 className="font-medium text-gray-900 mb-2"> {t('Application Tips')} </h4>
                                        <ul className="text-sm text-gray-600 space-y-1">
                                            {applicationTips.map((tip, index) => (
                                                <li key={index}>• {tip.title}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
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
