import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { Card, CardContent } from "@/components/ui/card";
import { useTranslation } from 'react-i18next';

interface PrivacyPolicyProps {
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
    privacyPolicy?: {
        content: string;
        enabled: boolean;
    };
    brandSettings: any;
    slug: string;
}

export default function PrivacyPolicy({ settings, privacyPolicy, brandSettings, slug }: PrivacyPolicyProps) {
    const { t } = useTranslation();
    const defaultContent = `
        <h2>Information We Collect</h2>
        <p>We collect information you provide directly to us, such as when you create a support ticket, contact us, or use our services.</p>
        
        <h2>How We Use Your Information</h2>
        <p>We use the information we collect to provide, maintain, and improve our services, process transactions, and communicate with you.</p>
        
        <h2>Information Sharing</h2>
        <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>
        
        <h2>Data Security</h2>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
        
        <h2>Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact our support team.</p>
    `;
    const content = privacyPolicy?.content || defaultContent;

    return (
        <SupportTicketLayout title={t('Privacy Policy')} settings={settings} brandSettings={brandSettings}>
            <div className="max-w-4xl mx-auto">
                <Card className="shadow-md">
                    <CardContent className="p-8">
                        <h1 className="text-3xl font-bold mb-6 text-gray-800">{t('Privacy Policy')}</h1>
                        
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