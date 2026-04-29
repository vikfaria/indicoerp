import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { toast } from 'sonner';
import { Save } from 'lucide-react';
import SystemSetupSidebar from '../SystemSetupSidebar';
import { useFormFields } from '@/hooks/useFormFields';

interface SettingsProps {
    settings: {
        templates: { [key: string]: string };
        languages?: { [key: string]: string };
    };
    auth: any;
}

export default function OfferLetterTemplate() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-offer-letter-template');

    const [selectedLanguage, setSelectedLanguage] = useState('en');
    const [formSettings, setFormSettings] = useState({
        templates: settings?.templates || { en: '' }
    });

    const getCountryFlag = (countryCode: string): string => {
        const codePoints = countryCode
            .toUpperCase()
            .split('')
            .map(char => 127397 + char.charCodeAt(0));
        return String.fromCodePoint(...codePoints);
    };

    const languages = settings?.languages ? Object.entries(settings.languages).map(([code, data]: [string, any]) => ({
        code,
        name: data.name || data,
        flag: data.countryCode ? getCountryFlag(data.countryCode) : '🌐'
    })) : [
        { code: 'en', name: 'English', flag: '🇬🇧' },
        { code: 'es', name: 'Spanish', flag: '🇪🇸' },
        { code: 'fr', name: 'French', flag: '🇫🇷' },
        { code: 'de', name: 'German', flag: '🇩🇪' }
    ];

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    const placeholders = [
        { name: 'Applicant Name', value: '{applicant_name}' },
        { name: 'App Name', value: '{app_name}' },
        { name: 'Company Name', value: '{company_name}' },
        { name: 'Job Title', value: '{job_title}' },
        { name: 'Job Type', value: '{job_type}' },
        { name: 'Proposed Start Date', value: '{start_date}' },
        { name: 'Working Location', value: '{workplace_location}' },
        { name: 'Days Of Week', value: '{days_of_week}' },
        { name: 'Salary', value: '{salary}' },
        { name: 'Salary Type', value: '{salary_type}' },
        { name: 'Salary Duration', value: '{salary_duration}' },
        { name: 'Offer Expiration Date', value: '{offer_expiration_date}' }
    ];

    useEffect(() => {
        if (settings) {
            setFormSettings({
                templates: settings?.templates || { en: '' }
            });
            // Set first available language as default if current selection doesn't exist
            const availableLanguages = Object.keys(settings?.templates || {});
            if (availableLanguages.length > 0 && !availableLanguages.includes(selectedLanguage)) {
                setSelectedLanguage(availableLanguages[0]);
            }
        }
    }, [settings]);

    const handleTemplateChange = (value: string) => {
        setFormSettings(prev => ({
            ...prev,
            templates: {
                ...prev.templates,
                [selectedLanguage]: value
            }
        }));
    };

    const [templateEditorKey, setTemplateEditorKey] = useState(0);
    const templateAI = useFormFields('aiField', formSettings, (field, value) => {
        handleTemplateChange(value);
        setTemplateEditorKey(prev => prev + 1);
    }, errors, 'edit', 'template', 'Template', 'recruitment', 'offer_letter_template');



    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        if (!formSettings.templates[selectedLanguage]?.trim()) {
            newErrors.template = t('Template is required');
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const saveSettings = () => {
        if (!validateForm()) {
            return;
        }

        setIsLoading(true);

        router.post(route('recruitment.offer-letter-template.update'), {
            settings: formSettings
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                setIsLoading(false);
                const successMessage = (page.props.flash as any)?.success;
                const errorMessage = (page.props.flash as any)?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                }
            },
            onError: (errors) => {
                setIsLoading(false);
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save template');
                toast.error(errorMessage);
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                { label: t('System Setup') },
                { label: t('Offer Letter Template') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Offer Letter Template')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="offer-letter-template" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('Offer Letter Template')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                {/* Placeholders Section */}
                                <div>
                                    <div className="flex justify-between items-center mb-4">
                                        <h4 className="text-md font-medium">{t('Placeholders')}</h4>
                                        <Select value={selectedLanguage} onValueChange={setSelectedLanguage} disabled={!canEdit}>
                                            <SelectTrigger className="w-48">
                                                <SelectValue>
                                                    <div className="flex items-center gap-2">
                                                        <span>{languages.find(l => l.code === selectedLanguage)?.flag}</span>
                                                        <span>{languages.find(l => l.code === selectedLanguage)?.name}</span>
                                                    </div>
                                                </SelectValue>
                                            </SelectTrigger>
                                            <SelectContent>
                                                {languages.map((lang) => (
                                                    <SelectItem key={lang.code} value={lang.code}>
                                                        <div className="flex items-center gap-2">
                                                            <span>{lang.flag}</span>
                                                            <span>{lang.name}</span>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                        {placeholders.map((placeholder, index) => (
                                            <div key={index} className="bg-gray-50 p-2 rounded border">
                                                <div className="text-sm font-medium text-gray-700">{placeholder.name}</div>
                                                <div className="text-xs text-green-600">{placeholder.value}</div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Template Editor */}
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between mb-2">
                                        <h4 className="text-md font-medium">{t('Template Content')}</h4>
                                        {canEdit && (
                                            <div className="flex gap-2">
                                                {templateAI.map(field => <div key={field.id}>{field.component}</div>)}
                                            </div>
                                        )}
                                    </div>
                                    <div className={errors.template ? 'border border-red-500 rounded-md' : ''}>
                                        <RichTextEditor
                                            key={`template-editor-${templateEditorKey}-${selectedLanguage}`}
                                            content={formSettings.templates[selectedLanguage] || ''}
                                            onChange={handleTemplateChange}
                                            placeholder={t('Enter your offer letter template here...')}
                                            disabled={!canEdit}
                                        />
                                    </div>
                                    {errors.template && <p className="text-sm text-red-500">{errors.template}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}