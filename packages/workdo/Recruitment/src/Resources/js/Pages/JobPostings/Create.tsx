import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { Button } from "@/components/ui/button";
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/ui/currency-input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TagsInput } from '@/components/ui/tags-input';
import { DatePicker } from '@/components/ui/date-picker';
import { CreateJobPostingProps, CreateJobPostingFormData } from './types';
import { usePage } from '@inertiajs/react';
import { useFormFields } from '@/hooks/useFormFields';


export default function Create({ onSuccess }: CreateJobPostingProps) {
    const { jobtypes, joblocations, customquestions, departments, branches } = usePage<any>().props;

    const { t } = useTranslation();
    const [customQuestionsError, setCustomQuestionsError] = useState('');
    const { data, setData, post, processing, errors } = useForm<CreateJobPostingFormData>({
        title: '',
        position: '',
        priority: '0',
        job_application: 'existing',
        application_url: '',
        branch_id: '',
        applicant: [],
        visibility: [],
        min_experience: '',
        max_experience: '',
        min_salary: '',
        max_salary: '',
        description: '',
        requirements: '',
        benefits: '',
        terms_condition: '',
        show_terms_condition: false,
        application_deadline: '',
        is_published: false,
        publish_date: '',
        is_featured: false,
        status: '0',
        department_id: '',
        job_type_id: '',
        location_id: '',
        custom_questions: [],
        skills: [],
    });

    // AI hooks for job posting fields
    const titleAI = useFormFields('aiField', data, setData, errors, 'create', 'title', 'Title', 'recruitment', 'job_posting');

    const [descriptionEditorKey, setDescriptionEditorKey] = useState(0);
    const descriptionAI = useFormFields('aiField', data, (field, value) => {
        setData('description', value);
        setDescriptionEditorKey(prev => prev + 1);
    }, errors, 'create', 'description', 'Description', 'recruitment', 'job_posting');

    const [requirementsEditorKey, setRequirementsEditorKey] = useState(0);
    const requirementsAI = useFormFields('aiField', data, (field, value) => {
        setData('requirements', value);
        setRequirementsEditorKey(prev => prev + 1);
    }, errors, 'create', 'requirements', 'Requirements', 'recruitment', 'job_posting');

    const [benefitsEditorKey, setBenefitsEditorKey] = useState(0);
    const benefitsAI = useFormFields('aiField', data, (field, value) => {
        setData('benefits', value);
        setBenefitsEditorKey(prev => prev + 1);
    }, errors, 'create', 'benefits', 'Benefits', 'recruitment', 'job_posting');

    const [termsEditorKey, setTermsEditorKey] = useState(0);
    const termsAI = useFormFields('aiField', data, (field, value) => {
        setData('terms_condition', value);
        setTermsEditorKey(prev => prev + 1);
    }, errors, 'create', 'terms_condition', 'Terms Condition', 'recruitment', 'job_posting');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate required custom questions
        const requiredQuestions = customquestions?.filter((q: any) => q.is_required) || [];
        const missingRequired = requiredQuestions.filter((q: any) => !data.custom_questions.includes(q.id));

        if (missingRequired.length > 0) {
            setCustomQuestionsError(t('Please select all required custom questions'));
            return;
        }

        setCustomQuestionsError('');
        post(route('recruitment.job-postings.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent className="max-w-4xl">
            <DialogHeader>
                <DialogTitle>{t('Create Job Posting')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div className="flex gap-2 items-end">
                            <div className="flex-1">
                                <Label htmlFor="title">{t('Title')}</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder={t('Enter Title')}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>
                            {titleAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="job_type_id" required>{t('Job Type')}</Label>
                        <Select
                            value={data.job_type_id?.toString() || ''}
                            onValueChange={(value) => setData('job_type_id', value)}
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Job Type')} />
                            </SelectTrigger>
                            <SelectContent>
                                {jobtypes?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {(!jobtypes || jobtypes.length === 0) && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Create job type here. ')}
                                <a
                                    href={route('recruitment.job-types.index')}
                                    className="text-blue-600 hover:text-blue-800 cursor-pointer"
                                >
                                    {t('job type')}
                                </a>.
                            </p>
                        )}
                        <InputError message={errors.job_type_id} />
                    </div>

                    <div>
                        <Label htmlFor="location_id" required>{t('Location')}</Label>
                        <Select
                            value={data.location_id?.toString() || ''}
                            onValueChange={(value) => setData('location_id', value)}
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Location')} />
                            </SelectTrigger>
                            <SelectContent>
                                {joblocations?.map((item: any) => (
                                    <SelectItem key={item.id} value={item.id.toString()}>
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {(!joblocations || joblocations.length === 0) && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Create job location here. ')}
                                <a
                                    href={route('recruitment.job-locations.index')}
                                    className="text-blue-600 hover:text-blue-800 cursor-pointer"
                                >
                                    {t('job location')}
                                </a>.
                            </p>
                        )}
                        <InputError message={errors.location_id} />
                    </div>
                    <div>
                        <Label htmlFor="branch_id" required>{t('Branch')}</Label>
                        <Select value={data.branch_id?.toString() || ''} onValueChange={(value) => setData('branch_id', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Branch')} />
                            </SelectTrigger>
                            <SelectContent>
                                {branches?.map((branch: any) => (
                                    <SelectItem key={branch.id} value={branch.id.toString()}>
                                        {branch.branch_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.branch_id} />
                        <p className="text-xs text-muted-foreground mt-1">
                            {t('Branch data comes from HRM add-on. Please ensure branches are created in HRM module first.')}
                        </p>
                        {(!branches || branches.length === 0) && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Create branch here. ')}
                                <a
                                    href={route('hrm.branches.index')}
                                    className="text-blue-600 hover:text-blue-800 cursor-pointer"
                                >
                                    {t('branch')}
                                </a>.
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="job_application" required>{t('Job Application')}</Label>
                        <Select value={data.job_application || ''} onValueChange={(value) => setData('job_application', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Application Type')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="existing">{t('Use Existing System')}</SelectItem>
                                <SelectItem value="custom">{t('Custom Application URL')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.job_application} />
                    </div>
                    {data.job_application === 'existing' ? (
                        <div>
                            <Label>{t('Career Portal URL')}</Label>
                            <Input
                                value={route('recruitment.frontend.careers.jobs.index', { userSlug: usePage<any>().props.auth?.user?.slug || 'demo' })}
                                readOnly
                                className="bg-gray-50"
                            />
                        </div>
                    ) : data.job_application === 'custom' ? (
                        <div>
                            <Label htmlFor="application_url" required>{t('Application URL')}</Label>
                            <Input
                                id="application_url"
                                type="url"
                                value={data.application_url}
                                onChange={(e) => setData('application_url', e.target.value)}
                                placeholder={t('Enter Application URL')}
                                required
                            />
                            <InputError message={errors.application_url} />
                        </div>
                    ) : (
                        <div></div>
                    )}
                    <div>
                        <Label htmlFor="position" required>{t('Number of Positions')}</Label>
                        <Input
                            id="position"
                            type="number"
                            min="1"
                            value={data.position}
                            onChange={(e) => setData('position', e.target.value)}
                            placeholder={t('Enter Number of Positions')}
                            required
                        />
                        <InputError message={errors.position} />
                    </div>
                    <div>
                        <Label htmlFor="priority" required>{t('Priority')}</Label>
                        <Select value={data.priority?.toString() || ''} onValueChange={(value) => setData('priority', value)} required>
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select Priority')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">{t('Low')}</SelectItem>
                                <SelectItem value="1">{t('Medium')}</SelectItem>
                                <SelectItem value="2">{t('High')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>

                    <div>
                        <Label htmlFor="min_experience" required>{t('Min Experience (Years)')}</Label>
                        <Input
                            id="min_experience"
                            type="number"
                            step="0.5"
                            min="0"
                            value={data.min_experience}
                            onChange={(e) => {
                                const value = e.target.value;
                                setData('min_experience', value);
                                // Clear max_experience if it's less than min_experience
                                if (data.max_experience && parseFloat(data.max_experience) < parseFloat(value)) {
                                    setData('max_experience', '');
                                }
                            }}
                            placeholder={t('Enter Minimum Experience')}
                            required
                        />
                        <InputError message={errors.min_experience} />
                    </div>

                    <div>
                        <Label htmlFor="max_experience">{t('Max Experience (Years)')}</Label>
                        <Input
                            id="max_experience"
                            type="number"
                            step="0.5"
                            min={data.min_experience || "0"}
                            value={data.max_experience}
                            onChange={(e) => setData('max_experience', e.target.value)}
                            placeholder={t('Enter Maximum Experience')}
                        />
                        <InputError message={errors.max_experience} />
                        {data.min_experience && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Must be greater than or equal to minimum experience')} ({data.min_experience})
                            </p>
                        )}
                    </div>

                    <div>
                        <CurrencyInput
                            label={t('Min Salary')}
                            value={data.min_salary}
                            onChange={(value) => {
                                setData('min_salary', value);
                                // Clear max_salary if it's less than min_salary
                                if (data.max_salary && parseFloat(data.max_salary) < parseFloat(value)) {
                                    setData('max_salary', '');
                                }
                            }}
                            error={errors.min_salary}
                        />
                    </div>

                    <div>
                        <CurrencyInput
                            label={t('Max Salary')}
                            value={data.max_salary}
                            onChange={(value) => setData('max_salary', value)}
                            error={errors.max_salary}
                            min={data.min_salary || "0"}
                        />
                        {data.min_salary && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {t('Must be greater than or equal to minimum salary')}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label required>{t('Application Deadline')}</Label>
                        <DatePicker
                            value={data.application_deadline}
                            onChange={(date) => setData('application_deadline', date)}
                            placeholder={t('Select Application Deadline')}
                            minDate={new Date(Date.now() + 24 * 60 * 60 * 1000)}
                            required
                        />
                        <InputError message={errors.application_deadline} />
                    </div>

                    <div className="flex items-center space-x-2 pt-2">
                        <Checkbox
                            id="is_featured"
                            checked={data.is_featured || false}
                            onCheckedChange={(checked) => setData('is_featured', !!checked)}
                        />
                        <Label htmlFor="is_featured" className="cursor-pointer">{t('Featured Job')}</Label>
                        <InputError message={errors.is_featured} />
                    </div>
                </div>

                <div>
                    <Label>{t('Need to Ask?')}</Label>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="applicant_gender"
                                checked={data.applicant.includes('gender')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('applicant', [...data.applicant, 'gender']);
                                    } else {
                                        setData('applicant', data.applicant.filter(item => item !== 'gender'));
                                    }
                                }}
                            />
                            <Label htmlFor="applicant_gender" className="cursor-pointer">{t('Gender')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="applicant_date_of_birth"
                                checked={data.applicant.includes('date_of_birth')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('applicant', [...data.applicant, 'date_of_birth']);
                                    } else {
                                        setData('applicant', data.applicant.filter(item => item !== 'date_of_birth'));
                                    }
                                }}
                            />
                            <Label htmlFor="applicant_date_of_birth" className="cursor-pointer">{t('Date Of Birth')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="applicant_country"
                                checked={data.applicant.includes('country')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('applicant', [...data.applicant, 'country']);
                                    } else {
                                        setData('applicant', data.applicant.filter(item => item !== 'country'));
                                    }
                                }}
                            />
                            <Label htmlFor="applicant_country" className="cursor-pointer">{t('Country')}</Label>
                        </div>
                    </div>
                </div>

                <div>
                    <Label>{t('Need to show Option?')}</Label>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="visibility_profile_image"
                                checked={data.visibility.includes('profile_image')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('visibility', [...data.visibility, 'profile_image']);
                                    } else {
                                        setData('visibility', data.visibility.filter(item => item !== 'profile_image'));
                                    }
                                }}
                            />
                            <Label htmlFor="visibility_profile_image" className="cursor-pointer">{t('Profile Image')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="visibility_resume"
                                checked={data.visibility.includes('resume')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('visibility', [...data.visibility, 'resume']);
                                    } else {
                                        setData('visibility', data.visibility.filter(item => item !== 'resume'));
                                    }
                                }}
                            />
                            <Label htmlFor="visibility_resume" className="cursor-pointer">{t('Resume')}</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="visibility_cover_letter"
                                checked={data.visibility.includes('cover_letter')}
                                onCheckedChange={(checked) => {
                                    if (checked) {
                                        setData('visibility', [...data.visibility, 'cover_letter']);
                                    } else {
                                        setData('visibility', data.visibility.filter(item => item !== 'cover_letter'));
                                    }
                                }}
                            />
                            <Label htmlFor="visibility_cover_letter" className="cursor-pointer">{t('Cover Letter')}</Label>
                        </div>
                    </div>
                </div>

                <div>
                    <Label htmlFor="skills" required>{t('Required Skills')}</Label>
                    <TagsInput
                        value={data.skills}
                        onChange={(skills) => setData('skills', skills)}
                        placeholder={t('Add skills and press Enter...')}
                        allowCustom={true}
                        required
                    />
                    <p className="text-xs text-muted-foreground mt-1">
                        {t('Type Required Skills and press Enter')}
                    </p>
                    <InputError message={errors.skills} />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="description">{t('Description')}</Label>
                        <div className="flex gap-2">
                            {descriptionAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`description-editor-${descriptionEditorKey}`}
                        content={data.description}
                        onChange={(content) => setData('description', content)}
                        placeholder={t('Enter Description')}
                    />
                    <InputError message={errors.description} />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="requirements">{t('Requirements')}</Label>
                        <div className="flex gap-2">
                            {requirementsAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`requirements-editor-${requirementsEditorKey}`}
                        content={data.requirements}
                        onChange={(content) => setData('requirements', content)}
                        placeholder={t('Enter Requirements')}
                    />
                    <InputError message={errors.requirements} />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="benefits">{t('Benefits')}</Label>
                        <div className="flex gap-2">
                            {benefitsAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`benefits-editor-${benefitsEditorKey}`}
                        content={data.benefits}
                        onChange={(content) => setData('benefits', content)}
                        placeholder={t('Enter Benefits')}
                    />
                    <InputError message={errors.benefits} />
                </div>

                <div>
                    <div className="flex items-center justify-between mb-2">
                        <Label htmlFor="terms_condition" required>{t('Terms Condition')}</Label>
                        <div className="flex gap-2">
                            {termsAI.map(field => <div key={field.id}>{field.component}</div>)}
                        </div>
                    </div>
                    <RichTextEditor
                        key={`terms-editor-${termsEditorKey}`}
                        content={data.terms_condition}
                        onChange={(content) => setData('terms_condition', content)}
                        placeholder={t('Enter Terms Condition')}
                    />
                    <InputError message={errors.terms_condition} />
                    <div className="flex items-center space-x-2 mt-2">
                        <Checkbox
                            id="show_terms_condition"
                            checked={data.show_terms_condition || false}
                            onCheckedChange={(checked) => setData('show_terms_condition', !!checked)}
                        />
                        <Label htmlFor="show_terms_condition" className="cursor-pointer">{t('Show Terms & Conditions on Application Form')}</Label>
                    </div>
                </div>

                <div>
                    <Label>{t('Application Questions')}</Label>
                    <div className="space-y-2 mt-2">
                        {customquestions?.map((question: any) => (
                            <div key={question.id} className="flex items-center space-x-2">
                                <Checkbox
                                    id={`question_${question.id}`}
                                    checked={data.custom_questions.includes(question.id)}
                                    onCheckedChange={(checked) => {
                                        if (checked) {
                                            setData('custom_questions', [...data.custom_questions, question.id]);
                                        } else {
                                            setData('custom_questions', data.custom_questions.filter(id => id !== question.id));
                                        }
                                        setCustomQuestionsError('');
                                    }}
                                />
                                <Label htmlFor={`question_${question.id}`} className="cursor-pointer">
                                    {question.question}
                                    {question.is_required && <span className="text-red-500 ml-1">*</span>}
                                </Label>
                            </div>
                        ))}
                    </div>
                    <InputError message={errors.custom_questions || customQuestionsError} />
                </div>

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
