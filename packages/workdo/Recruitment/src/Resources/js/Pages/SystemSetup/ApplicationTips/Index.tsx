import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { toast } from 'sonner';
import { Save, Plus, Trash2 } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import SystemSetupSidebar from '../SystemSetupSidebar';

interface Tip {
    title: string;
}

interface SettingsProps {
    settings: {
        tips: Tip[];
    };
    auth: any;
}

export default function ApplicationTips() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-application-tips');

    const [formSettings, setFormSettings] = useState({
        tips: settings?.tips || [{ title: '' }]
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                tips: settings?.tips || [{ title: '' }]
            });
        }
    }, [settings]);

    const handleTipChange = (index: number, value: string) => {
        const newTips = [...formSettings.tips];
        newTips[index].title = value;
        setFormSettings(prev => ({ ...prev, tips: newTips }));
    };

    const addTip = () => {
        setFormSettings(prev => ({
            ...prev,
            tips: [...prev.tips, { title: '' }]
        }));
    };

    const removeTip = (index: number) => {
        if (formSettings.tips.length > 1) {
            const newTips = formSettings.tips.filter((_, i) => i !== index);
            setFormSettings(prev => ({ ...prev, tips: newTips }));
        }
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        formSettings.tips.forEach((tip, index) => {
            if (!tip.title.trim()) {
                newErrors[`tip_${index}`] = t('Title is required');
            }
        });

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const saveSettings = () => {
        if (!validateForm()) {
            return;
        }

        setIsLoading(true);

        router.post(route('recruitment.application-tips.update'), {
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
                { label: t('Application Tips Section') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Application Tips Section')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="application-tips" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('Application Tips Section')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-4">
                                {formSettings.tips.map((tip, index) => (
                                    <div key={index} className="space-y-3">
                                        <div className="flex justify-between items-center">
                                            <Label htmlFor={`tip_${index}`} required>
                                                {t('Tip')} {index + 1} 
                                            </Label>
                                            {canEdit && formSettings.tips.length > 1 && (
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                onClick={() => removeTip(index)}
                                                                size="sm"
                                                                variant="ghost"
                                                                className="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{t('Delete')}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            )}
                                        </div>
                                        <Input
                                            id={`tip_${index}`}
                                            value={tip.title}
                                            onChange={(e) => handleTipChange(index, e.target.value)}
                                            placeholder={t('Enter application tip')}
                                            disabled={!canEdit}
                                            className={errors[`tip_${index}`] ? 'border-red-500' : ''}
                                            maxLength={100}
                                        />
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="text-red-500">{errors[`tip_${index}`] || ''}</span>
                                            <span className="text-gray-500">{tip.title.length}/100</span>
                                        </div>
                                    </div>
                                ))}

                                {canEdit && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={addTip}
                                        className="w-full"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t('Add Tip')}
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}