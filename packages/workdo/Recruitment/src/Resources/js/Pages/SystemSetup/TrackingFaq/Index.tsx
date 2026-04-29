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

interface Faq {
    question: string;
    answer: string;
}

interface SettingsProps {
    settings: {
        faqs: Faq[];
    };
    auth: any;
}

export default function TrackingFaq() {
    const { t } = useTranslation();
    const { settings, auth } = usePage<SettingsProps>().props;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('manage-tracking-faq');

    const [formSettings, setFormSettings] = useState({
        faqs: settings?.faqs || [{ question: '', answer: '' }]
    });

    const [errors, setErrors] = useState<{[key: string]: string}>({});

    useEffect(() => {
        if (settings) {
            setFormSettings({
                faqs: settings?.faqs || [{ question: '', answer: '' }]
            });
        }
    }, [settings]);

    const handleFaqChange = (index: number, field: keyof Faq, value: string) => {
        const newFaqs = [...formSettings.faqs];
        newFaqs[index][field] = value;
        setFormSettings(prev => ({ ...prev, faqs: newFaqs }));
    };

    const addFaq = () => {
        setFormSettings(prev => ({
            ...prev,
            faqs: [...prev.faqs, { question: '', answer: '' }]
        }));
    };

    const removeFaq = (index: number) => {
        if (formSettings.faqs.length > 1) {
            const newFaqs = formSettings.faqs.filter((_, i) => i !== index);
            setFormSettings(prev => ({ ...prev, faqs: newFaqs }));
        }
    };

    const validateForm = () => {
        const newErrors: {[key: string]: string} = {};

        formSettings.faqs.forEach((faq, index) => {
            if (!faq.question.trim()) {
                newErrors[`question_${index}`] = t('Question is required');
            }
            if (!faq.answer.trim()) {
                newErrors[`answer_${index}`] = t('Answer is required');
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

        router.post(route('recruitment.tracking-faq.update'), {
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
                { label: t('Tracking FAQ') }
            ]}
            pageTitle={t('System Setup')}
        >
            <Head title={t('Tracking FAQ')} />

            <div className="flex flex-col md:flex-row gap-8">
                <div className="md:w-64 flex-shrink-0">
                    <SystemSetupSidebar activeItem="tracking-faq" />
                </div>

                <div className="flex-1">
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">{t('Tracking FAQ')}</h3>
                                {canEdit && (
                                    <Button onClick={saveSettings} disabled={isLoading}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {isLoading ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                {formSettings.faqs.map((faq, index) => (
                                    <div key={index} className="border rounded-lg p-4 space-y-4">
                                        <div className="flex justify-between items-center">
                                            <h4 className="font-medium">{t('Question')} {index + 1}</h4>
                                            {canEdit && formSettings.faqs.length > 1 && (
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={0}>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                onClick={() => removeFaq(index)}
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
                                                <Label htmlFor={`question_${index}`} required>
                                                    {t('Question')}
                                                </Label>
                                                <Input
                                                    id={`question_${index}`}
                                                    value={faq.question}
                                                    onChange={(e) => handleFaqChange(index, 'question', e.target.value)}
                                                    placeholder={t('Enter FAQ question')}
                                                    disabled={!canEdit}
                                                    className={errors[`question_${index}`] ? 'border-red-500' : ''}
                                                    maxLength={100}
                                                />
                                                <div className="flex justify-between items-center text-xs">
                                                    <span className="text-red-500">{errors[`question_${index}`] || ''}</span>
                                                    <span className="text-gray-500">{faq.question.length}/ {t('100')}</span>
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor={`answer_${index}`} required>
                                                    {t('Answer')}
                                                </Label>
                                                <Textarea
                                                    id={`answer_${index}`}
                                                    value={faq.answer}
                                                    onChange={(e) => handleFaqChange(index, 'answer', e.target.value)}
                                                    placeholder={t('Enter FAQ answer')}
                                                    disabled={!canEdit}
                                                    className={errors[`answer_${index}`] ? 'border-red-500' : ''}
                                                    maxLength={300}
                                                    rows={3}
                                                />
                                                <div className="flex justify-between items-center text-xs">
                                                    <span className="text-red-500">{errors[`answer_${index}`] || ''}</span>
                                                    <span className="text-gray-500">{faq.answer.length}/ {t('300')}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                {canEdit && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={addFaq}
                                        className="w-full"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t('Add FAQ')}
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