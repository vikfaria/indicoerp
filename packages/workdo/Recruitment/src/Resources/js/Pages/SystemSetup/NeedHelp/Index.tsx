import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Save } from 'lucide-react';
import SystemSetupSidebar from '../SystemSetupSidebar';

interface SettingsProps {
    settings: {
        description: string;
        email: string;
        phone: string;
    };
    auth: any;
}

export default function NeedHelp() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-need-help');

    const [formSettings, setFormSettings] = useState({
        description: settings?.description || '',
        email: settings?.email || '',
        phone: settings?.phone || ''
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                description: settings?.description || '',
                email: settings?.email || '',
                phone: settings?.phone || ''
            });
        }
    }, [settings]);

    const handleInputChange = (field: string, value: string) => {
        setFormSettings(prev => ({ ...prev, [field]: value }));
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        if (!formSettings.description.trim()) {
            newErrors.description = t('Description is required');
        }
        if (!formSettings.email.trim()) {
            newErrors.email = t('Email is required');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formSettings.email)) {
            newErrors.email = t('Please enter a valid email address');
        }
        if (!formSettings.phone.trim()) {
            newErrors.phone = t('Phone is required');
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const saveSettings = () => {
        if (!validateForm()) {
            return;
        }

        setIsLoading(true);

        router.post(route('recruitment.need-help.update'), {
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
                { label: t('Need Help Section') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Need Help Section')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="need-help" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('Need Help Section')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label htmlFor="description" required>
                                        {t('Description')} 
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={formSettings.description}
                                        onChange={(e) => handleInputChange('description', e.target.value)}
                                        placeholder={t('Enter help description')}
                                        disabled={!canEdit}
                                        className={errors.description ? 'border-red-500' : ''}
                                        maxLength={100}
                                        rows={4}
                                    />
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="text-red-500">{errors.description || ''}</span>
                                        <span className="text-gray-500">{formSettings.description.length}/100</span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-3">
                                        <Label htmlFor="email" required>
                                            {t('Email')} 
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={formSettings.email}
                                            onChange={(e) => handleInputChange('email', e.target.value)}
                                            placeholder={t('Enter email address')}
                                            disabled={!canEdit}
                                            className={errors.email ? 'border-red-500' : ''}
                                            maxLength={50}
                                        />
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="text-red-500">{errors.email || ''}</span>
                                            <span className="text-gray-500">{formSettings.email.length}/50</span>
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        <Label htmlFor="phone" required>
                                            {t('Phone')} 
                                        </Label>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            value={formSettings.phone}
                                            onChange={(e) => handleInputChange('phone', e.target.value)}
                                            placeholder={t('Enter phone number')}
                                            disabled={!canEdit}
                                            className={errors.phone ? 'border-red-500' : ''}
                                            maxLength={20}
                                        />
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="text-red-500">{errors.phone || ''}</span>
                                            <span className="text-gray-500">{formSettings.phone.length}/20</span>
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