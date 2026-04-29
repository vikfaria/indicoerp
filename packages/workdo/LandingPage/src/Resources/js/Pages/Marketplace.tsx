import { Head, usePage } from '@inertiajs/react';

// Import landing page components
import Header from './components/Header';
import Footer from './components/Footer';
import CTA from './components/CTA';
import CookieConsent from "@/components/cookie-consent";
// Import marketplace components
import MarketplaceHero from './marketplace/Hero';
import MarketplaceModules from './marketplace/Modules';
import Dedication from './marketplace/Dedication';
import Screenshots from './marketplace/Screenshots';
import MarketplaceWhyChoose from './marketplace/WhyChoose';
import { getAdminSetting, getImagePath } from '@/utils/helpers';

interface MarketplaceProps {
    packages?: Array<{
        name: string;
        slug: string;
        description: string;
        price: string;
        yearly_price?: string;
        image?: string;
    }>;
    settings?: {
        title?: string;
        subtitle?: string;
        config_sections?: {
            sections?: any;
            section_visibility?: any;
            section_order?: string[];
        };
    };
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

export default function Marketplace({ packages = [], settings, landingPageSettings }: MarketplaceProps) {
    const getSectionData = (key: string) => {
        return settings?.config_sections?.sections?.[key] || {};
    };
    
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const { adminAllSetting, auth } = usePage().props as any;
    const updatedLandingPageSettings = { ...landingPageSettings, is_authenticated: (auth?.user?.id !== undefined && auth?.user?.id !== null) };
    const isSectionVisible = (key: string) => {
        return settings?.config_sections?.section_visibility?.[key] !== false;
    };
    
    const sectionOrder = settings?.config_sections?.section_order || 
        ['header', 'hero', 'modules', 'dedication', 'screenshots', 'why_choose', 'cta', 'footer'];
    
    const renderSection = (sectionKey: string) => {
        if (!isSectionVisible(sectionKey)) return null;
        
        const sectionData = getSectionData(sectionKey);
        
        switch (sectionKey) {
            case 'header':
                return <Header key={sectionKey} settings={updatedLandingPageSettings} />;
            case 'hero':
                return <MarketplaceHero key={sectionKey} settings={settings} />;
            case 'modules':
                return <MarketplaceModules key={sectionKey} packages={packages} settings={settings} />;
            case 'dedication':
                return <Dedication key={sectionKey} settings={settings} />;
            case 'screenshots':
                return <Screenshots key={sectionKey} settings={settings} />;
            case 'why_choose':
                return <MarketplaceWhyChoose key={sectionKey} settings={settings} />;
            case 'cta':
                return <CTA key={sectionKey} settings={updatedLandingPageSettings} />;
            case 'footer':
                return <Footer key={sectionKey} settings={updatedLandingPageSettings} />;
            default:
                return null;
        }
    };

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
            <Head title={`${settings?.title || 'ERPGo SaaS Marketplace'} - Premium Packages`}>
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>
            
            {sectionOrder.map(sectionKey => renderSection(sectionKey))}

            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}