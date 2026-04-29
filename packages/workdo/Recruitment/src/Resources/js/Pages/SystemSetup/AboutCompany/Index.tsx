import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { toast } from 'sonner';
import { Save } from 'lucide-react';
import SystemSetupSidebar from '../SystemSetupSidebar';

interface SettingsProps {
    settings: {
        ourMission: string;
        companySize: string;
        industry: string;
    };
    auth: any;
}

export default function AboutCompany() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-about-company');

    const [formSettings, setFormSettings] = useState({
        ourMission: settings?.ourMission || '',
        companySize: settings?.companySize || '',
        industry: settings?.industry || '',
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                ourMission: settings?.ourMission || '',
                companySize: settings?.companySize || '',
                industry: settings?.industry || '',
            });
        }
    }, [settings]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormSettings(prev => ({ ...prev, [name]: value }));
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        if (!formSettings.ourMission.trim()) {
            newErrors.ourMission = t('Our Mission is required');
        }
        if (!formSettings.companySize.trim()) {
            newErrors.companySize = t('Company Size is required');
        }
        if (!formSettings.industry.trim()) {
            newErrors.industry = t('Industry is required');
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const saveSettings = () => {
        if (!validateForm()) {
            return;
        }

        setIsLoading(true);

        router.post(route('recruitment.about-company.update'), {
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
                { label: t('About Company Section') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('About Company Section')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="about-company" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('About Company Section')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label htmlFor="ourMission" required>{t('Our Mission')} </Label>
                                    <Textarea
                                        id="ourMission"
                                        name="ourMission"
                                        value={formSettings.ourMission}
                                        onChange={handleInputChange}
                                        placeholder={t('Enter our mission')}
                                        disabled={!canEdit}
                                        className={errors.ourMission ? 'border-red-500' : ''}
                                        rows={3}
                                        maxLength={100}
                                    />
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="text-red-500">{errors.ourMission || ''}</span>
                                        <span className="text-gray-500">{formSettings.ourMission.length}/100</span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-3">
                                        <Label htmlFor="companySize" required>{t('Company Size')} </Label>
                                        <Input
                                            id="companySize"
                                            name="companySize"
                                            value={formSettings.companySize}
                                            onChange={handleInputChange}
                                            placeholder={t('Enter company size')}
                                            disabled={!canEdit}
                                            className={errors.companySize ? 'border-red-500' : ''}
                                            maxLength={50}
                                        />
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="text-red-500">{errors.companySize || ''}</span>
                                            <span className="text-gray-500">{formSettings.companySize.length}/50</span>
                                        </div>
                                    </div>
                                    <div className="space-y-3">
                                        <Label htmlFor="industry" required>{t('Industry')} </Label>
                                        <Input
                                            id="industry"
                                            name="industry"
                                            value={formSettings.industry}
                                            onChange={handleInputChange}
                                            placeholder={t('Enter industry')}
                                            disabled={!canEdit}
                                            className={errors.industry ? 'border-red-500' : ''}
                                            maxLength={60}
                                        />
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="text-red-500">{errors.industry || ''}</span>
                                            <span className="text-gray-500">{formSettings.industry.length}/60</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}