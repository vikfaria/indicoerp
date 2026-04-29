import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import FrontendLayout from '../../Components/Frontend/FrontendLayout';
import { useFormFields } from '@/hooks/useFormFields';

interface Job {
    id: number;
    encrypted_id: string;
    title: string;
    terms_condition?: string;
}

interface JobTermsProps {
    job: Job;
}

export default function JobTerms({ job }: JobTermsProps) {
    const { t } = useTranslation();
    const { props } = usePage();
    const userSlug = props.userSlug as string;

    const integrationFields = useFormFields('getIntegrationFields', {}, () => {}, {}, 'create', t, 'Recruitment');

    return (
        <FrontendLayout title={`Terms - ${job.title}`}>
            <Head title={`Terms & Conditions - ${job.title}`} />

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('Terms & Conditions')}</h1>
                    <p className="text-gray-600">{t('For position')} : {job.title}</p>
                </div>

                <Card className="shadow-sm">
                    <CardContent className="p-8">
                        {job.terms_condition ? (
                            <div
                                className="prose prose-lg max-w-none text-gray-700 leading-relaxed"
                                dangerouslySetInnerHTML={{ __html: job.terms_condition }}
                            />
                        ) : (
                            <div className="text-center py-12">
                                <p className="text-gray-500 text-lg">{t('No terms and conditions specified for this position.')}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
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