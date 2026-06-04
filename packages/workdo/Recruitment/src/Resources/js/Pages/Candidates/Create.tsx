import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Checkbox } from '@/components/ui/checkbox';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { DatePicker } from '@/components/ui/date-picker';
import { CreateCandidateProps, CreateCandidateFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { FileText, Image } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';


export default function Create({ onSuccess }: CreateCandidateProps) {
    const { jobpostings, candidatesources } = usePage<any>().props;
    const { t } = useTranslation();
    const [customQuestions, setCustomQuestions] = useState([]);
    const [jobPostingSettings, setJobPostingSettings] = useState<{applicant: string[], visibility: string[]}>({applicant: [], visibility: []});
    const [customErrors, setCustomErrors] = useState<{[key: string]: string}>({});
    const [profilePreview, setProfilePreview] = useState<string | null>(null);
    const [resumePreview, setResumePreview] = useState<string | null>(null);
    const [coverLetterPreview, setCoverLetterPreview] = useState<string | null>(null);

    const getFileIcon = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension || '')) {
            return <Image className="h-8 w-8 text-blue-500" />;
        }
        return <FileText className="h-8 w-8 text-red-500" />;
    };

    const handleFileChange = (file: File | null, type: 'profile_photo' | 'resume' | 'cover_letter') => {
        if (file) {
            const extension = file.name.split('.').pop()?.toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension || '')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (type === 'profile_photo') {
                        setProfilePreview(e.target?.result as string);
                    } else if (type === 'resume') {
                        setResumePreview(e.target?.result as string);
                    } else {
                        setCoverLetterPreview(e.target?.result as string);
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if (type === 'profile_photo') {
                    setProfilePreview(null);
                } else if (type === 'resume') {
                    setResumePreview(null);
                } else {
                    setCoverLetterPreview(null);
                }
            }
        } else {
            if (type === 'profile_photo') {
                setProfilePreview(null);
            } else if (type === 'resume') {
                setResumePreview(null);
            } else {
                setCoverLetterPreview(null);
            }
        }
        setData(type, file);
    };
    const { data, setData, post, processing, errors } = useForm<CreateCandidateFormData>({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        gender: '',
        dob: '',
        nationality: '',
        identification_document_type: '',
        identification_document_number: '',
        nuit: '',
        desired_professional_category: '',
        is_regulated_profession: false,
        professional_license_type: '',
        professional_license_number: '',
        professional_license_expiry_date: '',
        professional_license_document_path: '',
        minor_work_authorization_path: '',
        legal_exception_notes: '',
        country: '',
        state: '',
        city: '',
        job_id: '',
        source_id: '',
        current_company: '',
        current_position: '',
        experience_years: '',
        current_salary: '',
        expected_salary: '',
        notice_period: '',
        skills: '',
        education: '',
        portfolio_url: '',
        linkedin_url: '',
        profile_url: '',
        resume: null,
        cover_letter: null,
        status: '0',
        application_date: '',
        custom_question: '',
    });

    const parsedDob = data.dob ? new Date(data.dob) : null;
    const candidateAge = parsedDob && !Number.isNaN(parsedDob.getTime())
        ? Math.floor((Date.now() - parsedDob.getTime()) / (365.25 * 24 * 60 * 60 * 1000))
        : null;
    const requiresMinorAuthorization = candidateAge !== null && candidateAge >= 12 && candidateAge < 15;

    useEffect(() => {
        if (data.job_id) {
            axios.get(route('recruitment.job-postings.custom-questions', data.job_id))
                .then(response => {
                    setCustomQuestions(response.data);
                })
                .catch(() => {
                    setCustomQuestions([]);
                });

            // Get job posting settings for applicant and visibility
            axios.get(route('recruitment.job-postings.settings', data.job_id))
                .then(response => {
                    setJobPostingSettings({
                        applicant: response.data.applicant || [],
                        visibility: response.data.visibility || []
                    });
                })
                .catch(() => {
                    setJobPostingSettings({applicant: [], visibility: []});
                });
        } else {
            setCustomQuestions([]);
            setJobPostingSettings({applicant: [], visibility: []});
        }
    }, [data.job_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate required custom questions
        const requiredQuestions = customQuestions.filter((q: any) => q.is_required);
        const newErrors: {[key: string]: string} = {};

        requiredQuestions.forEach((q: any) => {
            const answer = data[`custom_question_${q.id}`];
            if (!answer || answer.trim() === '') {
                newErrors[`custom_question_${q.id}`] = 'This field is required.';
            }
        });

        setCustomErrors(newErrors);

        if (Object.keys(newErrors).length > 0) {
            return;
        }

        post(route('recruitment.candidates.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-4xl">
            <DialogHeader>
                <DialogTitle>{t('Create Candidate')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="job_id" required>{t('Job')} </Label>
                        <Select value={data.job_id?.toString() || ''} onValueChange={(value) => setData('job_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Job')} />
                            </SelectTrigger>
                            <SelectContent>
                                {jobpostings.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.job_id} />
                        {(!jobpostings || jobpostings.length === 0) && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Create job posting here. ')}
                                <a
                                    href={route('recruitment.job-postings.index')}
                                    className="text-blue-600 hover:text-blue-800 cursor-pointer"
                                >
                                    {t('job posting')}
                                </a>.
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="source_id" required>{t('Source')} </Label>
                        <Select
                            value={data.source_id?.toString() || ''}
                            onValueChange={(value) => setData('source_id', value)}
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Source')} />
                            </SelectTrigger>
                            <SelectContent>
                                {candidatesources?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.source_id} />
                        {(!candidatesources || candidatesources.length === 0) && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Create candidate source here. ')}
                                <a
                                    href={route('recruitment.candidate-sources.index')}
                                    className="text-blue-600 hover:text-blue-800 cursor-pointer"
                                >
                                    {t('candidate source')}
                                </a>.
                            </p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="first_name">{t('First Name')}</Label>
                        <Input
                            id="first_name"
                            type="text"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            placeholder={t('Enter First Name')}
                            required
                        />
                        <InputError message={errors.first_name} />
                    </div>

                    <div>
                        <Label htmlFor="last_name">{t('Last Name')}</Label>
                        <Input
                            id="last_name"
                            type="text"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            placeholder={t('Enter Last Name')}
                            required
                        />
                        <InputError message={errors.last_name} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    {jobPostingSettings.applicant.includes('gender') && (
                        <div>
                            <Label htmlFor="gender">{t('Gender')}</Label>
                            <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select Gender')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">{t('Male')}</SelectItem>
                                    <SelectItem value="female">{t('Female')}</SelectItem>
                                    <SelectItem value="other">{t('Other')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gender} />
                        </div>
                    )}

                    {jobPostingSettings.visibility.includes('profile_image') && (
                        <div>
                            <MediaPicker
                                label={t('Profile Photo')}
                                value={data.profile_url}
                                onChange={(value) => setData('profile_url', value as string)}
                                placeholder={t('Select Profile Photo')}
                                id="profile_photo"
                            />
                            <InputError message={errors.profile_photo} />
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={t('Enter Email')}
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <PhoneInputComponent
                            label={t('Phone')}
                            value={data.phone}
                            onChange={(value) => setData('phone', value)}
                            placeholder={t('Enter Phone')}
                            error={errors.phone}
                            id="phone"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    {jobPostingSettings.applicant.includes('date_of_birth') && (
                        <div>
                            <Label>{t('Date of Birth')}</Label>
                            <DatePicker
                                value={data.dob}
                                onChange={(date) => setData('dob', date)}
                                placeholder={t('Select Date of Birth')}
                                maxDate={new Date(Date.now() - 24 * 60 * 60 * 1000)}
                            />
                            <InputError message={errors.dob} />
                        </div>
                    )}

                    {jobPostingSettings.applicant.includes('country') && (
                        <div>
                            <Label htmlFor="country">{t('Country')}</Label>
                            <Input
                                id="country"
                                type="text"
                                value={data.country}
                                onChange={(e) => setData('country', e.target.value)}
                                placeholder={t('Enter Country')}
                            />
                            <InputError message={errors.country} />
                        </div>
                    )}
                </div>

                {(jobPostingSettings.applicant.includes('country')) && (
                    <div className="grid grid-cols-2 gap-4">
                        {jobPostingSettings.applicant.includes('country') && (
                            <div>
                                <Label htmlFor="state">{t('State')}</Label>
                                <Input
                                    id="state"
                                    type="text"
                                    value={data.state}
                                    onChange={(e) => setData('state', e.target.value)}
                                    placeholder={t('Enter State')}
                                />
                                <InputError message={errors.state} />
                            </div>
                        )}

                        {jobPostingSettings.applicant.includes('country') && (
                            <div>
                                <Label htmlFor="city">{t('City')}</Label>
                                <Input
                                    id="city"
                                    type="text"
                                    value={data.city}
                                    onChange={(e) => setData('city', e.target.value)}
                                    placeholder={t('Enter City')}
                                />
                                <InputError message={errors.city} />
                            </div>
                        )}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="nationality">{t('Nationality')}</Label>
                        <Input
                            id="nationality"
                            type="text"
                            value={data.nationality}
                            onChange={(e) => setData('nationality', e.target.value)}
                            placeholder={t('Enter Nationality')}
                        />
                        <InputError message={errors.nationality} />
                    </div>

                    <div>
                        <Label htmlFor="nuit">{t('NUIT')}</Label>
                        <Input
                            id="nuit"
                            type="text"
                            value={data.nuit}
                            onChange={(e) => setData('nuit', e.target.value)}
                            placeholder={t('Enter NUIT (9 digits)')}
                        />
                        <InputError message={errors.nuit} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="identification_document_type">{t('Identification Document Type')}</Label>
                        <Select
                            value={data.identification_document_type}
                            onValueChange={(value) => setData('identification_document_type', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select document type')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bi">{t('BI')}</SelectItem>
                                <SelectItem value="passport">{t('Passport')}</SelectItem>
                                <SelectItem value="dire">{t('DIRE')}</SelectItem>
                                <SelectItem value="other">{t('Other')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.identification_document_type} />
                    </div>

                    <div>
                        <Label htmlFor="identification_document_number">{t('Identification Document Number')}</Label>
                        <Input
                            id="identification_document_number"
                            type="text"
                            value={data.identification_document_number}
                            onChange={(e) => setData('identification_document_number', e.target.value)}
                            placeholder={t('Enter document number')}
                        />
                        <InputError message={errors.identification_document_number} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="desired_professional_category">{t('Desired Professional Category')}</Label>
                        <Input
                            id="desired_professional_category"
                            type="text"
                            value={data.desired_professional_category}
                            onChange={(e) => setData('desired_professional_category', e.target.value)}
                            placeholder={t('Enter desired professional category')}
                        />
                        <InputError message={errors.desired_professional_category} />
                    </div>

                    <div className="flex items-end">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_regulated_profession"
                                checked={Boolean(data.is_regulated_profession)}
                                onCheckedChange={(checked) => setData('is_regulated_profession', checked === true)}
                            />
                            <Label htmlFor="is_regulated_profession">{t('Regulated Profession')}</Label>
                        </div>
                        <InputError message={errors.is_regulated_profession} />
                    </div>
                </div>

                {Boolean(data.is_regulated_profession) && (
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="professional_license_type">{t('Professional License Type')}</Label>
                            <Input
                                id="professional_license_type"
                                type="text"
                                value={data.professional_license_type}
                                onChange={(e) => setData('professional_license_type', e.target.value)}
                                placeholder={t('Enter license type')}
                            />
                            <InputError message={errors.professional_license_type} />
                        </div>

                        <div>
                            <Label htmlFor="professional_license_number">{t('Professional License Number')}</Label>
                            <Input
                                id="professional_license_number"
                                type="text"
                                value={data.professional_license_number}
                                onChange={(e) => setData('professional_license_number', e.target.value)}
                                placeholder={t('Enter license number')}
                            />
                            <InputError message={errors.professional_license_number} />
                        </div>

                        <div>
                            <Label>{t('Professional License Expiry Date')}</Label>
                            <DatePicker
                                value={data.professional_license_expiry_date}
                                onChange={(date) => setData('professional_license_expiry_date', date)}
                                placeholder={t('Select expiry date')}
                            />
                            <InputError message={errors.professional_license_expiry_date} />
                        </div>

                        <div className="col-span-2">
                            <MediaPicker
                                label={t('Professional License Document')}
                                value={data.professional_license_document_path}
                                onChange={(value) => setData('professional_license_document_path', value as string)}
                                placeholder={t('Select professional license document')}
                                id="professional_license_document_path"
                            />
                            <InputError message={errors.professional_license_document_path} />
                        </div>
                    </div>
                )}

                {requiresMinorAuthorization && (
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="minor_work_authorization_path" required>{t('Minor Work Authorization Evidence')}</Label>
                            <Input
                                id="minor_work_authorization_path"
                                type="text"
                                value={data.minor_work_authorization_path}
                                onChange={(e) => setData('minor_work_authorization_path', e.target.value)}
                                placeholder={t('Enter authorization file path/reference')}
                            />
                            <InputError message={errors.minor_work_authorization_path} />
                        </div>
                        <div>
                            <Label htmlFor="legal_exception_notes" required>{t('Legal Exception Notes')}</Label>
                            <Textarea
                                id="legal_exception_notes"
                                value={data.legal_exception_notes}
                                onChange={(e) => setData('legal_exception_notes', e.target.value)}
                                placeholder={t('Enter legal notes for exceptional minor hiring')}
                                rows={3}
                            />
                            <InputError message={errors.legal_exception_notes} />
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="current_company">{t('Current Company')}</Label>
                        <Input
                            id="current_company"
                            type="text"
                            value={data.current_company}
                            onChange={(e) => setData('current_company', e.target.value)}
                            placeholder={t('Enter Current Company')}
                        />
                        <InputError message={errors.current_company} />
                    </div>

                    <div>
                        <Label htmlFor="current_position">{t('Current Position')}</Label>
                        <Input
                            id="current_position"
                            type="text"
                            value={data.current_position}
                            onChange={(e) => setData('current_position', e.target.value)}
                            placeholder={t('Enter Current Position')}
                        />
                        <InputError message={errors.current_position} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="experience_years">{t('Experience (Years)')}</Label>
                        <Input
                            id="experience_years"
                            type="number"
                            step="0.5"
                            min="0"
                            value={data.experience_years}
                            onChange={(e) => setData('experience_years', e.target.value)}
                            placeholder={t('Enter Experience in Years')}
                            required
                        />
                        <InputError message={errors.experience_years} />
                    </div>

                    <div>
                        <CurrencyInput
                            label={t('Current Salary')}
                            value={data.current_salary}
                            onChange={(value) => setData('current_salary', value)}
                            error={errors.current_salary}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <CurrencyInput
                            label={t('Expected Salary')}
                            value={data.expected_salary}
                            onChange={(value) => setData('expected_salary', value)}
                            error={errors.expected_salary}
                        />
                    </div>

                    <div>
                        <Label htmlFor="notice_period">{t('Notice Period')}</Label>
                        <Input
                            id="notice_period"
                            type="text"
                            value={data.notice_period}
                            onChange={(e) => setData('notice_period', e.target.value)}
                            placeholder={t('Enter Notice Period')}
                        />
                        <InputError message={errors.notice_period} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label required>{t('Application Date')}</Label>
                        <DatePicker
                            value={data.application_date}
                            onChange={(date) => setData('application_date', date)}
                            placeholder={t('Select Application Date')}
                            required
                        />
                        <InputError message={errors.application_date} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="skills">{t('Skills')}</Label>
                    <Textarea
                        id="skills"
                        value={data.skills}
                        onChange={(e) => setData('skills', e.target.value)}
                        placeholder={t('Enter Skills')}
                        rows={3}
                    />
                    <InputError message={errors.skills} />
                </div>

                <div>
                    <Label htmlFor="education">{t('Education')}</Label>
                    <Textarea
                        id="education"
                        value={data.education}
                        onChange={(e) => setData('education', e.target.value)}
                        placeholder={t('Enter Education')}
                        rows={3}
                    />
                    <InputError message={errors.education} />
                </div>

                <div>
                    <Label htmlFor="portfolio_url">{t('Portfolio Url')}</Label>
                    <Input
                        id="portfolio_url"
                        type="text"
                        value={data.portfolio_url}
                        onChange={(e) => setData('portfolio_url', e.target.value)}
                        placeholder={t('Enter Portfolio Url')}

                    />
                    <InputError message={errors.portfolio_url} />
                </div>

                <div>
                    <Label htmlFor="linkedin_url">{t('Linkedin Url')}</Label>
                    <Input
                        id="linkedin_url"
                        type="text"
                        value={data.linkedin_url}
                        onChange={(e) => setData('linkedin_url', e.target.value)}
                        placeholder={t('Enter Linkedin Url')}
                    />
                    <InputError message={errors.linkedin_url} />
                </div>

                {(jobPostingSettings.visibility.includes('resume') || jobPostingSettings.visibility.includes('cover_letter')) && (
                    <div className="grid grid-cols-2 gap-4">
                        {jobPostingSettings.visibility.includes('resume') && (
                            <div>
                                <Label htmlFor="resume">{t('Resume/CV')}</Label>
                                <Input
                                    id="resume"
                                    type="file"
                                    onChange={(e) => handleFileChange(e.target.files?.[0] || null, 'resume')}
                                />
                                <InputError message={errors.resume} />
                                {data.resume && (
                                    <div className="mt-2 p-2 border rounded-lg bg-gray-50">
                                        <div className="flex items-center space-x-2">
                                            {resumePreview ? (
                                                <img src={resumePreview} alt="Resume preview" className="h-12 w-12 object-cover rounded" />
                                            ) : (
                                                getFileIcon(data.resume.name)
                                            )}
                                            <span className="text-sm text-gray-700 truncate">{data.resume.name}</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {jobPostingSettings.visibility.includes('cover_letter') && (
                            <div>
                                <Label htmlFor="cover_letter">{t('Cover Letter')}</Label>
                                <Input
                                    id="cover_letter"
                                    type="file"
                                    onChange={(e) => handleFileChange(e.target.files?.[0] || null, 'cover_letter')}
                                />
                                <InputError message={errors.cover_letter} />
                                {data.cover_letter && (
                                    <div className="mt-2 p-2 border rounded-lg bg-gray-50">
                                        <div className="flex items-center space-x-2">
                                            {coverLetterPreview ? (
                                                <img src={coverLetterPreview} alt="Cover letter preview" className="h-12 w-12 object-cover rounded" />
                                            ) : (
                                                getFileIcon(data.cover_letter.name)
                                            )}
                                            <span className="text-sm text-gray-700 truncate">{data.cover_letter.name}</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}

                {customQuestions.length > 0 && (
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">{t('Application Questions')}</h3>
                        {customQuestions.map((question: any) => (
                            <div key={question.id}>
                                <Label htmlFor={`custom_question_${question.id}`}>
                                    {question.question}
                                    {question.is_required && <span className="text-red-500 ml-1">*</span>}
                                </Label>
                                {question.type === 'text' && (
                                    <Input
                                        id={`custom_question_${question.id}`}
                                        type="text"
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onChange={(e) => setData(`custom_question_${question.id}`, e.target.value)}
                                        placeholder={t('Enter your answer')}
                                    />
                                )}
                                {question.type === 'textarea' && (
                                    <Textarea
                                        id={`custom_question_${question.id}`}
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onChange={(e) => setData(`custom_question_${question.id}`, e.target.value)}
                                        placeholder={t('Enter your answer')}
                                        rows={3}
                                    />
                                )}
                                {question.type === 'select' && (
                                    <Select
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onValueChange={(value) => setData(`custom_question_${question.id}`, value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select an option')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {question.options && JSON.parse(question.options).map((option: string, index: number) => (
                                                <SelectItem key={index} value={option}>
                                                    {option}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                                {question.type === 'radio' && (
                                    <RadioGroup
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onValueChange={(value) => setData(`custom_question_${question.id}`, value)}
                                    >
                                        {question.options && JSON.parse(question.options).map((option: string, index: number) => (
                                            <div key={index} className="flex items-center space-x-2">
                                                <RadioGroupItem value={option} id={`custom_question_${question.id}_${index}`} />
                                                <Label htmlFor={`custom_question_${question.id}_${index}`} className="text-sm">
                                                    {option}
                                                </Label>
                                            </div>
                                        ))}
                                    </RadioGroup>
                                )}
                                {question.type === 'checkbox' && (
                                    <div className="space-y-2">
                                        {question.options && JSON.parse(question.options).map((option: string, index: number) => (
                                            <div key={index} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={`custom_question_${question.id}_${index}`}
                                                    checked={(data[`custom_question_${question.id}`] || '').split(',').includes(option)}
                                                    onCheckedChange={(checked) => {
                                                        const currentValues = (data[`custom_question_${question.id}`] || '').split(',').filter(v => v);
                                                        if (checked) {
                                                            setData(`custom_question_${question.id}`, [...currentValues, option].join(','));
                                                        } else {
                                                            setData(`custom_question_${question.id}`, currentValues.filter(v => v !== option).join(','));
                                                        }
                                                    }}
                                                />
                                                <Label htmlFor={`custom_question_${question.id}_${index}`} className="text-sm">
                                                    {option}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {question.type === 'date' && (
                                    <Input
                                        id={`custom_question_${question.id}`}
                                        type="date"
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onChange={(e) => setData(`custom_question_${question.id}`, e.target.value)}
                                    />
                                )}
                                {question.type === 'number' && (
                                    <Input
                                        id={`custom_question_${question.id}`}
                                        type="number"
                                        value={data[`custom_question_${question.id}`] || ''}
                                        onChange={(e) => setData(`custom_question_${question.id}`, e.target.value)}
                                        placeholder={t('Enter a number')}
                                    />
                                )}
                                <InputError message={errors[`custom_question_${question.id}`] || customErrors[`custom_question_${question.id}`]} />
                            </div>
                        ))}
                    </div>
                )}

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
