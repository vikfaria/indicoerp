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
import { Save, Plus, Trash2 } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import SystemSetupSidebar from '../SystemSetupSidebar';
import { IconPicker } from '@/components/ui/icon-picker';

interface Step {
    title: string;
    description: string;
    icon: string;
}

interface SettingsProps {
    settings: {
        steps: Step[];
    };
    auth: any;
}

export default function WhatHappensNext() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-what-happens-next');

    const [formSettings, setFormSettings] = useState({
        steps: settings?.steps || [{ title: '', description: '', icon: '' }]
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                steps: settings?.steps || [{ title: '', description: '', icon: '' }]
            });
        }
    }, [settings]);

    const handleStepChange = (index: number, field: keyof Step, value: string) => {
        const newSteps = [...formSettings.steps];
        newSteps[index][field] = value;
        setFormSettings(prev => ({ ...prev, steps: newSteps }));
    };

    const addStep = () => {
        setFormSettings(prev => ({
            ...prev,
            steps: [...prev.steps, { title: '', description: '', icon: '' }]
        }));
    };

    const removeStep = (index: number) => {
        if (formSettings.steps.length > 1) {
            const newSteps = formSettings.steps.filter((_, i) => i !== index);
            setFormSettings(prev => ({ ...prev, steps: newSteps }));
        }
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        formSettings.steps.forEach((step, index) => {
            if (!step.title.trim()) {
                newErrors[`title_${index}`] = t('Title is required');
            }
            if (!step.description.trim()) {
                newErrors[`description_${index}`] = t('Description is required');
            }
            if (!step.icon.trim()) {
                newErrors[`icon_${index}`] = t('Icon is required');
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

        router.post(route('recruitment.what-happens-next.update'), {
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
                { label: t('What Happens Next Section') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('What Happens Next Section')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="what-happens-next" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('What Happens Next Section')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                {formSettings.steps.map((step, index) => (
                                    <div key={index} className="border rounded-lg p-4 space-y-4">
                                        <div className="flex justify-between items-center">
                                            <h4 className="font-medium">{t('Step')} {index + 1}</h4>
                                            {canEdit && formSettings.steps.length > 1 && (
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                onClick={() => removeStep(index)}
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

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor={`title_${index}`} required>
                                                    {t('Title')} 
                                                </Label>
                                                <Input
                                                    id={`title_${index}`}
                                                    value={step.title}
                                                    onChange={(e) => handleStepChange(index, 'title', e.target.value)}
                                                    placeholder={t('Enter step title')}
                                                    disabled={!canEdit}
                                                    className={errors[`title_${index}`] ? 'border-red-500' : ''}
                                                    maxLength={50}
                                                />
                                                <div className="flex justify-between items-center text-xs">
                                                    <span className="text-red-500">{errors[`title_${index}`] || ''}</span>
                                                    <span className="text-gray-500">{step.title.length}/50</span>
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor={`icon_${index}`} required>
                                                    {t('Icon')} 
                                                </Label>
                                                <IconPicker
                                                    value={step.icon}
                                                    onChange={(iconName) => handleStepChange(index, 'icon', iconName)}
                                                    placeholder={t('Select an icon')}
                                                    className={errors[`icon_${index}`] ? 'border-red-500' : ''}
                                                />
                                                <div className="flex justify-between items-center text-xs">
                                                    <span className="text-red-500">{errors[`icon_${index}`] || ''}</span>
                                                    <span className="text-gray-500">{step.icon.length}/30</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor={`description_${index}`} required>
                                                {t('Description')} 
                                            </Label>
                                            <Textarea
                                                id={`description_${index}`}
                                                value={step.description}
                                                onChange={(e) => handleStepChange(index, 'description', e.target.value)}
                                                placeholder={t('Enter step description')}
                                                disabled={!canEdit}
                                                className={errors[`description_${index}`] ? 'border-red-500' : ''}
                                                maxLength={100}
                                                rows={3}
                                            />
                                            <div className="flex justify-between items-center text-xs">
                                                <span className="text-red-500">{errors[`description_${index}`] || ''}</span>
                                                <span className="text-gray-500">{step.description.length}/100</span>
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                {canEdit && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={addStep}
                                        className="w-full"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t('Add Step')}
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