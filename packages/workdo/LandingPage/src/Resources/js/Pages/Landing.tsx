import { Head, usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import CookieConsent from "@/components/cookie-consent";
// Import components
import Header from './components/Header';
import Hero from './components/Hero';
import Stats from './components/Stats';
import Features from './components/Features';
import Modules from './components/Modules';
import Benefits from './components/Benefits';
import Gallery from './components/Gallery';
import CTA from './components/CTA';
import Footer from './components/Footer';

// Type definitions for better type safety
interface SectionData {
    [key: string]: any;
}

interface SectionVisibility {
    [key: string]: boolean;
}

interface ConfigSections {
    sections?: SectionData;
    section_visibility?: SectionVisibility;
    section_order?: string[];
    page?: {
        title?: string;
        description?: string;
        keywords?: string;
        canonical_url?: string;
    };
    social?: {
        og_title?: string;
        og_description?: string;
        og_image?: string;
        site_name?: string;
    };
    colors?: {
        primary: string;
        secondary: string;
        accent: string;
    };
}

interface LandingProps {
    settings?: {
        company_name?: string;
        contact_email?: string;
        contact_phone?: string;
        contact_address?: string;
        config_sections?: ConfigSections;
    };
}

export default function Landing({ settings }: LandingProps) {
    const { adminAllSetting } = usePage().props as any;
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;
    const pageMeta = settings?.config_sections?.page || {};
    const socialMeta = settings?.config_sections?.social || {};
    const pageTitle = pageMeta.title || `${settings?.company_name || 'ERPGo SaaS'} - All-in-One Business Management Solution`;
    const pageDescription = pageMeta.description || 'Business management solution';
    const ogTitle = socialMeta.og_title || pageTitle;
    const ogDescription = socialMeta.og_description || pageDescription;
    const ogImage = socialMeta.og_image ? getImagePath(socialMeta.og_image) : null;
    const canonicalUrl = pageMeta.canonical_url || undefined;
    
    const isSectionVisible = (key: string) => {
        return settings?.config_sections?.section_visibility?.[key] !== false;
    };
    
    const sectionOrder = settings?.config_sections?.section_order || 
        ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];
    
    const renderSection = useCallback((sectionKey: string) => {
        if (!isSectionVisible(sectionKey)) return null;
        
        switch (sectionKey) {
            case 'header':
                return <Header key={sectionKey} settings={settings} />;
            case 'hero':
                return <Hero key={sectionKey} settings={settings} />;
            case 'stats':
                return <Stats key={sectionKey} settings={settings} />;
            case 'features':
                return <Features key={sectionKey} settings={settings} />;
            case 'modules':
                return <Modules key={sectionKey} settings={settings} />;
            case 'benefits':
                return <Benefits key={sectionKey} settings={settings} />;
            case 'gallery':
                return <Gallery key={sectionKey} settings={settings} />;
            case 'cta':
                return <CTA key={sectionKey} settings={settings} />;
            case 'footer':
                return <Footer key={sectionKey} settings={settings} />;
            default:
                return null;
        }
    }, [settings, isSectionVisible]);

    return (
        <div className="min-h-screen bg-white">
            <Head title={pageTitle}>
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
                {pageDescription && <meta name="description" content={pageDescription} />}
                {pageMeta.keywords && <meta name="keywords" content={pageMeta.keywords} />}
                {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}
                <meta property="og:title" content={ogTitle} />
                <meta property="og:description" content={ogDescription} />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content={socialMeta.site_name || settings?.company_name || 'ERPGo SaaS'} />
                {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}
                {ogImage && <meta property="og:image" content={ogImage} />}
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={ogTitle} />
                <meta name="twitter:description" content={ogDescription} />
                {ogImage && <meta name="twitter:image" content={ogImage} />}
            </Head>
            
            {/* Render sections in order */}
            {sectionOrder.map(sectionKey => renderSection(sectionKey))}
            
            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}
