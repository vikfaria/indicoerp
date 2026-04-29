import SupportTicketLayout from './Layouts/SupportTicketLayout';
import { Card, CardContent } from "@/components/ui/card";
import { useTranslation } from 'react-i18next';

interface CustomPageProps {
    settings: {
        faq_is_on: string;
        knowledge_base_is_on: string;
    };
    customPage: {
        id: number;
        title: string;
        contents: string;
        description: string;
    };
    brandSettings: any;
}

export default function CustomPage({ settings, customPage, brandSettings }: CustomPageProps) {
    const { t } = useTranslation();
    return (
        <SupportTicketLayout title={customPage.title} settings={settings} brandSettings={brandSettings}>
            <div className="max-w-4xl mx-auto">
                <Card className="shadow-md">
                    <CardContent className="p-8">
                        <h1 className="text-3xl font-bold mb-6 text-gray-800">{customPage.title}</h1>
                        
                        <div 
                            className="prose max-w-none space-y-6"
                            dangerouslySetInnerHTML={{ __html: customPage.contents }}
                        />
                    </CardContent>
                </Card>
            </div>
        </SupportTicketLayout>
    );
}