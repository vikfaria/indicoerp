import { useState, useEffect } from 'react';
import { Head, usePage, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Save } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { IconPicker } from '@/components/ui/icon-picker';
import InputError from '@/components/ui/input-error';
import { getImagePath } from '@/utils/helpers';
import SystemSetupSidebar from '../SystemSetupSidebar';

interface SettingsProps {
    settings: {
        logo_dark: string;
        favicon: string;
        titleText: string;
        footerText: string;
    };
    dashboardWelcomeCard: {
        card_title: string;
        card_description: string;
        button_text: string;
        button_icon: string;
    };
    auth: any;
}

export default function BrandSettings() {
    const { t } = useTranslation();
    const { settings, dashboardWelcomeCard, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-recruitment-brand-settings');

    const [formSettings, setFormSettings] = useState({
        logo_dark: settings?.logo_dark || '',
        favicon: settings?.favicon || '',
        titleText: settings?.titleText || '',
        footerText: settings?.footerText || '',
    });

    const { data: welcomeCardData, setData: setWelcomeCardData, post: postWelcomeCard, processing: welcomeCardProcessing, errors: welcomeCardErrors } = useForm({
        card_title: dashboardWelcomeCard?.card_title || '',
        card_description: dashboardWelcomeCard?.card_description || '',
        button_text: dashboardWelcomeCard?.button_text || '',
        button_icon: dashboardWelcomeCard?.button_icon || ''
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                logo_dark: settings?.logo_dark || '',
                favicon: settings?.favicon || '',
                titleText: settings?.titleText || '',
                footerText: settings?.footerText || '',
            });
        }
    }, [settings]);

    const handleWelcomeCardSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postWelcomeCard(route('recruitment.dashboard-welcome-card.update'));
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value } = e.target;
        setFormSettings(prev => ({ ...prev, [name]: value }));
    };

    const handleMediaSelect = (name: string, url: string | string[]) => {
        const urlString = Array.isArray(url) ? url[0] || '' : url;
        setFormSettings(prev => ({ ...prev, [name]: urlString }));
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        if (!formSettings.titleText.trim()) {
            newErrors.titleText = t('Title text is required');
        }
        if (!formSettings.footerText.trim()) {
            newErrors.footerText = t('Footer text is required');
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const saveSettings = () => {
        if (!validateForm()) {
            return;
        }

        setIsLoading(true);

        router.post(route('recruitment.settings.update'), {
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
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save settings');
                toast.error(errorMessage);
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Recruitment'), url: route('recruitment.index') },
                { label: t('System Setup') },
                { label: t('Brand Settings') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Brand Settings')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="brand-settings" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="mb-6">
                                <h3 className="text-lg font-medium">{t('Brand Settings')}</h3>
                            </div>

                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-3">
                                        <Label>{t('Logo')}</Label>
                                        <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-32">
                                            {formSettings.logo_dark ? (
                                                <img
                                                    src={getImagePath(formSettings.logo_dark)}
                                                    alt={t('Dark Logo')}
                                                    className="max-h-full max-w-full object-contain"
                                                />
                                            ) : (
                                                <div className="text-muted-foreground text-sm">{t('No logo selected')}</div>
                                            )}
                                        </div>
                                        <MediaPicker
                                            value={formSettings.logo_dark}
                                            onChange={(url) => handleMediaSelect('logo_dark', url)}
                                            placeholder={t('Browse')}
                                            showPreview={false}
                                            disabled={!canEdit}
                                        />
                                    </div>

                                    <div className="space-y-3">
                                        <Label>{t('Favicon')}</Label>
                                        <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-32">
                                            {formSettings.favicon ? (
                                                <img
                                                    src={getImagePath(formSettings.favicon)}
                                                    alt={t('Favicon')}
                                                    className="max-h-full max-w-full object-contain"
                                                />
                                            ) : (
                                                <div className="text-muted-foreground text-sm">{t('No favicon selected')}</div>
                                            )}
                                        </div>
                                        <MediaPicker
                                            value={formSettings.favicon}
                                            onChange={(url) => handleMediaSelect('favicon', url)}
                                            placeholder={t('Browse')}
                                            showPreview={false}
                                            disabled={!canEdit}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-3">
                                        <Label htmlFor="titleText" required>{t('Title Text')} </Label>
                                        <Input
                                            id="titleText"
                                            name="titleText"
                                            value={formSettings.titleText}
                                            onChange={handleInputChange}
                                            placeholder={t('Enter title text')}
                                            disabled={!canEdit}
                                            className={errors.titleText ? 'border-red-500' : ''}
                                        />
                                        {errors.titleText && <p className="text-sm text-red-500">{errors.titleText}</p>}
                                    </div>
                                    <div className="space-y-3">
                                        <Label htmlFor="footerText" required>{t('Footer Text')} </Label>
                                        <Input
                                            id="footerText"
                                            name="footerText"
                                            value={formSettings.footerText}
                                            onChange={handleInputChange}
                                            placeholder={t('Enter footer text')}
                                            disabled={!canEdit}
                                            className={errors.footerText ? 'border-red-500' : ''}
                                        />
                                        {errors.footerText && <p className="text-sm text-red-500">{errors.footerText}</p>}
                                    </div>
                                </div>
                            </div>

                            {canEdit && (
                                <div className="flex justify-end mt-6">
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm mt-6">
                        <CardContent className="p-6">
                            <div className="mb-6">
                                <div>
                                    <h3 className="text-lg font-medium">{t('Dashboard Welcome Card Settings')}</h3>
                                    <p className="text-sm text-gray-500 mt-1">{t('Configure the title and description for the dashboard welcome card')}</p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <Label htmlFor="card_title">{t('Card Title')}</Label>
                                    <Input
                                        id="card_title"
                                        value={welcomeCardData.card_title}
                                        onChange={(e) => setWelcomeCardData('card_title', e.target.value)}
                                        placeholder={t('Enter card title')}
                                        disabled={!canEdit}
                                    />
                                    <InputError message={welcomeCardErrors.card_title} />
                                </div>

                                <div>
                                    <Label htmlFor="card_description">{t('Card Description')}</Label>
                                    <Textarea
                                        id="card_description"
                                        value={welcomeCardData.card_description}
                                        onChange={(e) => setWelcomeCardData('card_description', e.target.value)}
                                        placeholder={t('Enter card description')}
                                        rows={4}
                                        disabled={!canEdit}
                                    />
                                    <InputError message={welcomeCardErrors.card_description} />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <Label htmlFor="button_text">{t('Button Text')}</Label>
                                        <Input
                                            id="button_text"
                                            value={welcomeCardData.button_text}
                                            onChange={(e) => setWelcomeCardData('button_text', e.target.value)}
                                            placeholder={t('Enter button text')}
                                            disabled={!canEdit}
                                        />
                                        <InputError message={welcomeCardErrors.button_text} />
                                    </div>

                                    <div>
                                        <Label htmlFor="button_icon">{t('Button Icon')}</Label>
                                        <IconPicker
                                            value={welcomeCardData.button_icon}
                                            onChange={(icon) => setWelcomeCardData('button_icon', icon)}
                                            placeholder={t('Select button icon')}
                                            disabled={!canEdit}
                                        />
                                        <InputError message={welcomeCardErrors.button_icon} />
                                    </div>
                                </div>
                            </div>

                            {canEdit && (
                                <div className="flex justify-end mt-6">
                                    <Button onClick={handleWelcomeCardSubmit} disabled={welcomeCardProcessing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {welcomeCardProcessing ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}