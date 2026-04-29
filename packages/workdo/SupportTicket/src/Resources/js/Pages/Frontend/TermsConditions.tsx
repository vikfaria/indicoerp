import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { Card, CardContent } from "@/components/ui/card";
import { useTranslation } from 'react-i18next';

interface TermsConditionsProps {
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
    termsConditions?: {
        content: string;
        enabled: boolean;
    };
    brandSettings: any;
    slug: string;
}

export default function TermsConditions({ settings, termsConditions, brandSettings, slug }: TermsConditionsProps) {
    const { t } = useTranslation();
    const defaultContent = `
        <h2>Acceptance of Terms</h2>
        <p>By accessing and using this support system, you accept and agree to be bound by the terms and provision of this agreement.</p>
        
        <h2>Use License</h2>
        <p>Permission is granted to temporarily use this support system for personal, non-commercial transitory viewing only.</p>
        
        <h2>Disclaimer</h2>
        <p>The materials on this support system are provided on an 'as is' basis. We make no warranties, expressed or implied.</p>
        
        <h2>Limitations</h2>
        <p>In no event shall our company or its suppliers be liable for any damages arising out of the use or inability to use the materials on this support system.</p>
        
        <h2>Contact Information</h2>
        <p>If you have any questions about these Terms and Conditions, please contact our support team.</p>
    `;

    const content = termsConditions?.content || defaultContent;

    return (
        <SupportTicketLayout title={t('Terms & Conditions')} settings={settings} brandSettings={brandSettings}>
            <div className="max-w-4xl mx-auto">
                <Card className="shadow-md">
                    <CardContent className="p-8">
                        <h1 className="text-3xl font-bold mb-6 text-gray-800">{t('Terms & Conditions')}</h1>
                        
                        <div 
                            className="prose max-w-none space-y-6"
                            dangerouslySetInnerHTML={{ __html: content }}
                        />
                    </CardContent>
                </Card>
            </div>
        </SupportTicketLayout>
    );
}