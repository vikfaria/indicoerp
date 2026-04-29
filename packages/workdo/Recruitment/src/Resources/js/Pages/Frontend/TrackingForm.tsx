import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, HelpCircle, Mail, Hash } from 'lucide-react';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { useFormFields } from '@/hooks/useFormFields';

interface TrackingFormProps {
    trackingFaq?: {
        question: string;
        answer: string;
    }[] | null;
}

export default function TrackingForm({ trackingFaq }: TrackingFormProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;

    const integrationFields = useFormFields('getIntegrationFields', {}, () => {}, {}, 'create', t, 'Recruitment');

    const [formData, setFormData] = useState({
        trackingId: '',
        email: ''
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
        // Clear error when user starts typing
        if (error) setError('');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.trackingId.trim() || !formData.email.trim()) {
            setError(t('Please fill in both tracking ID and email address'));
            return;
        }

        setIsSubmitting(true);
        setError('');

        // Submit form data to backend for validation
        router.post(route('recruitment.frontend.careers.track.verify', { userSlug }), {
            tracking_id: formData.trackingId.trim(),
            email: formData.email.trim()
        }, {
            onSuccess: (page) => {
                setIsSubmitting(false);
            },
            onError: (errors) => {
                setIsSubmitting(false);
                if (errors.message) {
                    setError(errors.message);
                } else {
                    setError(t('Invalid tracking ID or email address. Please check your details and try again.'));
                }
            }
        });
    };

    const isFormValid = () => {
        return formData.trackingId.trim() && formData.email.trim();
    };

    return (
        <FrontendLayout title="Track Application" currentPage="track-form">
            <Head title={t('Track Your Application')} />

            <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                {/* Hero Section */}
                <div className="text-center mb-12">
                    <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-slate-100 mb-6">
                        <Search className="h-8 w-8 text-slate-600" />
                    </div>
                    <h2 className="text-3xl font-bold text-gray-900 mb-4">{t('Check Your Application Status')}</h2>
                    <p className="text-lg text-gray-600 mb-2">{t('Enter your tracking ID and email address to view your application progress')}</p>
                </div>

                {/* Tracking Form */}
                <Card className="shadow-sm mb-8">
                    <CardContent className="p-8">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Tracking ID */}
                            <div>
                                <Label htmlFor="trackingId" className="text-sm font-medium text-gray-700 mb-2 block">
                                    {t('Tracking ID')}
                                </Label>
                                <div className="relative">
                                    <Hash className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
                                    <Input
                                        id="trackingId"
                                        type="text"
                                        value={formData.trackingId}
                                        onChange={(e) => handleInputChange('trackingId', e.target.value.toUpperCase())}
                                        placeholder={t('Enter tracking id')}
                                        className="pl-10 h-12"
                                        required
                                    />
                                </div>
                                <p className="text-xs text-gray-500 mt-1">
                                    {t('Format')}: {t('TRK11A1BC1D1111E')}
                                </p>
                            </div>

                            {/* Email Address */}
                            <div>
                                <Label htmlFor="email" className="text-sm font-medium text-gray-700 mb-2 block">
                                    {t('Email Address')}
                                </Label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
                                    <Input
                                        id="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) => handleInputChange('email', e.target.value)}
                                        placeholder={t('Enter the email address')}
                                        className="pl-10 h-12"
                                        required
                                    />
                                </div>
                                <p className="text-xs text-gray-500 mt-1">
                                    {t('Use the same email address you provided during application')}
                                </p>
                            </div>

                            {/* Error Message */}
                            {error && (
                                <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <p className="text-red-800 text-sm">{error}</p>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="pt-4">
                                <Button
                                    type="submit"
                                    className="w-full bg-slate-700 hover:bg-slate-800 text-white h-12 text-lg"
                                    disabled={!isFormValid() || isSubmitting}
                                >
                                    {isSubmitting ? (
                                        <div className="flex items-center">
                                            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                                            {t('Searching...')}
                                        </div>
                                    ) : (
                                        <div className="flex items-center">
                                            <Search className="h-5 w-5 mr-2" />
                                            {t('Track Application')}
                                        </div>
                                    )}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Help Section */}
                {trackingFaq && trackingFaq.length > 0 && (
                    <Card className="shadow-sm">
                        <CardContent className="p-6">
                            <div className="flex items-start space-x-4">
                                <div className="flex-shrink-0">
                                    <HelpCircle className="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-3">{t('Need Help?')}</h3>
                                    <div className="space-y-3 text-sm text-gray-600">
                                        {trackingFaq.map((faq, index) => (
                                            <div key={index}>
                                                <h4 className="font-medium text-gray-900">{faq.question}</h4>
                                                <p>{faq.answer}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
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