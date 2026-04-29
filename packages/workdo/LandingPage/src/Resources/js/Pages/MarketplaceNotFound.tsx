import { Head, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

// Import landing page components
import Header from './components/Header';
import Footer from './components/Footer';
import CookieConsent from "@/components/cookie-consent";
import { useTranslation } from 'react-i18next';

interface MarketplaceNotFoundProps {
    landingPageSettings?: {
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
    };
}

export default function MarketplaceNotFound({ landingPageSettings }: MarketplaceNotFoundProps) {
    const { t } = useTranslation();
    // Apply color settings from landing page
    const colorScheme = landingPageSettings?.config_sections?.colors || {
        primary: '#10b77f',
        secondary: '#059669',
        accent: '#065f46'
    };
    const { adminAllSetting, auth } = usePage().props as any;
    const updatedLandingPageSettings = { ...landingPageSettings, is_authenticated: (auth?.user?.id !== undefined && auth?.user?.id !== null) };
    return (
        <div className="min-h-screen bg-white" style={{
            '--color-primary': colorScheme.primary,
            '--color-secondary': colorScheme.secondary,
            '--color-accent': colorScheme.accent
        } as React.CSSProperties}>
            <Head title="Package Not Found - ERPGo SaaS Marketplace" />
            
            <Header key="header" settings={updatedLandingPageSettings} />
            
            <section className="py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="max-w-md mx-auto">
                        <h1 className="text-6xl font-bold text-gray-900 mb-4">404</h1>
                        <h2 className="text-2xl font-semibold text-gray-900 mb-4">{t('Package Not Found')}</h2>
                        <p className="text-gray-600 mb-8">
                            {t("The package you're looking for doesn't exist or has been removed from our marketplace.")}
                        </p>
                        <button
                            onClick={() => window.history.back()}
                            className="inline-flex items-center px-6 py-3 text-white rounded-md font-medium transition-colors"
                            style={{ backgroundColor: 'var(--color-primary)' }}
                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'var(--color-secondary)'}
                            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'var(--color-primary)'}
                        >
                            <ArrowLeft className="h-5 w-5 mr-2" />
                            {t("Go Back")}
                        </button>
                    </div>
                </div>
            </section>
            
            <Footer key="footer" settings={updatedLandingPageSettings} />

            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}