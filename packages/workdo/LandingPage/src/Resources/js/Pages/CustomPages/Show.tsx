import { Head, usePage } from '@inertiajs/react';
import Header from '../components/Header';
import Footer from '../components/Footer';
import CookieConsent from "@/components/cookie-consent";


interface CustomPage {
    id: number;
    title: string;
    slug: string;
    content: string;
    meta_title: string;
    meta_description: string;
    is_active: boolean;
}

interface LandingPageSettings {
    company_name?: string;
    contact_email?: string;
    contact_phone?: string;
    contact_address?: string;
    config_sections?: {
        sections?: any;
        colors?: {
            primary: string;
            secondary: string;
            accent: string;
        };
    };
}

interface ShowProps {
    page: CustomPage;
    landingPageSettings?: LandingPageSettings;
}

export default function Show({ page, landingPageSettings }: ShowProps) {
    const { adminAllSetting } = usePage().props as any;
    // Apply color settings from landing page
    const colorScheme = landingPageSettings?.config_sections?.colors || {
        primary: '#10b77f',
        secondary: '#059669',
        accent: '#065f46'
    };

    return (
        <div className="min-h-screen bg-white" style={{
            '--color-primary': colorScheme.primary,
            '--color-secondary': colorScheme.secondary,
            '--color-accent': colorScheme.accent
        } as React.CSSProperties}>
            <Head 
                title={page.meta_title || page.title}
                description={page.meta_description}
            />
            
            <Header key="header" settings={landingPageSettings} />
            
            <section className="py-20">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                            {page.title}
                        </h1>
                    </div>
                    
                    <div className="prose prose-lg max-w-none">
                        <div 
                            dangerouslySetInnerHTML={{ __html: page.content }}
                            className="text-gray-700 leading-relaxed"
                        />
                    </div>
                </div>
            </section>
            
            <Footer key="footer" settings={landingPageSettings} />

            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}