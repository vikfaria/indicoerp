import { Mail, Phone, MapPin, Globe } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import { useState } from 'react';
import { toast } from 'sonner';

interface FooterProps {
    settings?: any;
}

const FOOTER_VARIANTS = {
    footer1: {
        footer: 'bg-gray-900 text-white py-16',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        grid: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8',
        companyName: 'text-2xl font-bold mb-4 lg:max-w-[180px] max-w-[140px] inline-block',
        description: 'text-gray-400 mb-6 leading-relaxed',
        sectionTitle: 'text-lg font-semibold mb-6 text-white',
        newsletterTitle: 'text-lg font-semibold mb-4 text-white',
        copyright: 'border-t border-gray-700 mt-8 pt-8 text-center text-gray-400',
        layout: 'standard'
    },
    footer2: {
        footer: 'bg-white py-16 border-t border-gray-100 shadow-inner',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        grid: 'flex flex-col md:flex-row justify-between items-start space-y-8 md:space-y-0 md:space-x-12',
        companyName: 'text-xl font-bold text-gray-900 lg:max-w-[180px] max-w-[140px] inline-block',
        description: 'text-gray-600 text-sm max-w-md leading-relaxed',
        sectionTitle: 'text-sm font-semibold text-gray-900 mb-4',
        newsletterTitle: 'text-lg font-semibold text-gray-900 mb-4',
        copyright: 'mt-12 pt-8 border-t border-gray-100 text-center text-gray-500 text-sm',
        layout: 'minimal'
    },
    footer3: {
        footer: 'bg-gradient-to-br from-gray-800 via-gray-900 to-black text-white py-24',
        container: 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center',
        grid: 'space-y-16',
        companyName: 'text-4xl font-bold mb-8 lg:max-w-[180px] max-w-[140px] inline-block',
        description: 'text-gray-300 text-xl mb-12 max-w-3xl mx-auto leading-relaxed',
        sectionTitle: 'text-lg font-semibold mb-6 text-white',
        newsletterTitle: 'text-2xl font-bold mb-6 text-white',
        copyright: 'border-t border-gray-700 mt-16 pt-8 text-gray-400',
        layout: 'centered'
    },
    footer4: {
        footer: 'bg-gray-50 py-20 border-t border-gray-200',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        grid: 'grid grid-cols-1 lg:grid-cols-2 gap-20 items-start',
        companyName: 'text-2xl font-bold text-gray-900 mb-6 lg:max-w-[180px] max-w-[140px] inline-block',
        description: 'text-gray-600 mb-8 leading-relaxed text-lg',
        sectionTitle: 'text-lg font-semibold mb-6 text-gray-900',
        newsletterTitle: 'text-xl font-bold mb-6 text-gray-900',
        copyright: 'border-t border-gray-200 mt-16 pt-8 text-center text-gray-500',
        layout: 'split'
    },
    footer5: {
        footer: 'py-20 relative overflow-hidden',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10',
        grid: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12',
        companyName: 'text-3xl font-bold mb-6 text-white drop-shadow-lg lg:max-w-[180px] max-w-[140px] inline-block',
        description: 'text-white/90 mb-8 leading-relaxed text-lg',
        sectionTitle: 'text-xl font-bold mb-6 text-white',
        newsletterTitle: 'text-2xl font-bold mb-6 text-white',
        copyright: 'border-t border-white/30 mt-12 pt-8 text-center text-white/80 backdrop-blur-sm',
        layout: 'modern'
    }
};

export default function Footer({ settings }: FooterProps) {
    const [emailInput, setEmailInput] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const sectionData = settings?.config_sections?.sections?.footer || {};
    const variant = sectionData.variant || 'footer1';
    const config = FOOTER_VARIANTS[variant as keyof typeof FOOTER_VARIANTS] || FOOTER_VARIANTS.footer1;
    
    const companyName = settings?.company_name || 'ERPGo SaaS';
    const description = sectionData.description || 'The complete business management solution for modern enterprises.';
    const contactEmail = settings?.contact_email || 'support@erpgosaas.com';
    const phone = settings?.contact_phone || '+1 (555) 123-4567';
    const newsletterTitle = sectionData.newsletter_title || 'Join Our Community';
    const newsletterDescription = sectionData.newsletter_description || 'We build modern web tools to help you jump-start your daily business work.';
    const newsletterButtonText = sectionData.newsletter_button_text || 'Subscribe';
    const copyrightText = sectionData.copyright_text || `© ${new Date().getFullYear()} ${companyName}. All rights reserved.`;
    const colors = settings?.config_sections?.colors || { primary: '#10b77f', secondary: '#059669', accent: '#f59e0b' };
    
    const handleNewsletterSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!emailInput.trim()) {
            toast.error('Please enter your email address');
            return;
        }

        setIsSubmitting(true);

        try {
            const response = await fetch(route('newsletter.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    email: emailInput.trim()
                })
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Thank you for subscribing to our newsletter!');
                setEmailInput('');
            } else {
                toast.error(data.message || 'Failed to subscribe. Please try again.');
            }
        } catch (error) {
            toast.error('An error occurred. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    const isLightBackground = config.layout === 'minimal' || config.layout === 'split';
    const logoKey = isLightBackground ? 'logo_dark' : 'logo_light';
    const logoPath = getAdminSetting(logoKey);
    const logoUrl = logoPath ? getImagePath(logoPath) : null;

    const getBackgroundStyle = () => {
        if (config.layout === 'modern') {
            return { 
                background: `linear-gradient(135deg, ${colors.primary} 0%, ${colors.secondary} 50%, ${colors.accent} 100%)`,
                backgroundSize: '400% 400%',
                animation: 'gradientShift 15s ease infinite'
            };
        }
        return {};
    };

    const renderCompanyInfo = () => {
        if (config.layout === 'minimal') {
            return (
                <div className="flex-1 space-y-4">
                    <Link href={route('landing.page')} className={config.companyName}>
                        {logoUrl ? (
                            <img src={logoUrl} alt={companyName} className="w-auto" />
                        ) : (
                            companyName
                        )}
                    </Link>
                    <p className={config.description}>{description}</p>
                    <div className="flex flex-col space-y-2">
                        <div className="flex items-center text-sm">
                            <Mail className="h-4 w-4 mr-2 text-gray-500" />
                            <span className="text-gray-600">{contactEmail}</span>
                        </div>
                        <div className="flex items-center text-sm">
                            <Phone className="h-4 w-4 mr-2 text-gray-500" />
                            <span className="text-gray-600">{phone}</span>
                        </div>
                    </div>
                </div>
            );
        }
        
        return (
            <div className={config.layout === 'split' ? 'space-y-6' : ''}>
                <Link href={route('landing.page')} className={config.companyName} style={config.layout !== 'split' ? { color: colors.primary } : {}}>
                    {logoUrl ? (
                        <img src={logoUrl} alt={companyName} className={config.layout === 'centered' ? 'h-12 w-auto mx-auto' : 'w-auto'} />
                    ) : (
                        companyName
                    )}
                </Link>
                <p className={config.description}>
                    {description}
                </p>
                <div className={`space-y-4 ${config.layout === 'centered' ? 'flex flex-col items-center' : ''}`}>
                    <div className="flex items-center">
                        <Mail 
                            className={config.layout === 'centered' || config.layout === 'modern' ? 'mr-4' : 'h-5 w-5 mr-3'} 
                            style={{ 
                                color: colors.primary,
                                width: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                height: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                minWidth: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                minHeight: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px'
                            }} 
                        />
                        <span className={`${config.layout === 'split' ? 'text-gray-600' : config.layout === 'centered' || config.layout === 'modern' ? 'text-gray-300 text-lg' : 'text-gray-300'}`}>
                            {contactEmail}
                        </span>
                    </div>
                    <div className="flex items-center">
                        <Phone 
                            className={config.layout === 'centered' || config.layout === 'modern' ? 'mr-4' : 'h-5 w-5 mr-3'} 
                            style={{ 
                                color: colors.primary,
                                width: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                height: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                minWidth: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px',
                                minHeight: config.layout === 'centered' || config.layout === 'modern' ? '24px' : '20px'
                            }} 
                        />
                        <span className={`${config.layout === 'split' ? 'text-gray-600' : config.layout === 'centered' || config.layout === 'modern' ? 'text-gray-300 text-lg' : 'text-gray-300'}`}>
                            {phone}
                        </span>
                    </div>
                </div>
            </div>
        );
    };

    const renderNavigationSections = () => {
        if (config.layout === 'minimal') {
            return (
                <div className="flex flex-wrap gap-8">
                    {sectionData.navigation_sections?.slice(0, 3).map((section: any, index: number) => (
                        section.links?.length > 0 && (
                            <div key={index} className="min-w-0">
                                <h3 className={config.sectionTitle}>{section.title}</h3>
                                <ul className="space-y-2">
                                    {section.links.slice(0, 4).map((link: any, linkIndex: number) => (
                                        <li key={linkIndex}>
                                            {link.target === '_blank' ? (
                                                <a href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} target="_blank" rel="noopener noreferrer" className="text-gray-600 hover:text-gray-900 transition-colors text-sm hover:underline">
                                                    {link.text}
                                                </a>
                                            ) : (
                                                <Link href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} className="text-gray-600 hover:text-gray-900 transition-colors text-sm hover:underline">
                                                    {link.text}
                                                </Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )
                    ))}
                </div>
            );
        }

        if (config.layout === 'centered') {
            return (
                <div className="flex flex-wrap justify-center gap-12">
                    {sectionData.navigation_sections?.map((section: any, index: number) => (
                        section.links?.length > 0 && (
                            <div key={index}>
                                <h3 className={config.sectionTitle}>{section.title}</h3>
                                <ul className="space-y-2">
                                    {section.links.map((link: any, linkIndex: number) => (
                                        <li key={linkIndex}>
                                            {link.target === '_blank' ? (
                                                <a href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} target="_blank" rel="noopener noreferrer" className="text-gray-300 hover:text-white transition-colors text-sm">
                                                    {link.text}
                                                </a>
                                            ) : (
                                                <Link href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} className="text-gray-300 hover:text-white transition-colors text-sm">
                                                    {link.text}
                                                </Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )
                    ))}
                </div>
            );
        }

        if (config.layout === 'split') {
            return (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-8">
                    {sectionData.navigation_sections?.map((section: any, index: number) => (
                        section.links?.length > 0 && (
                            <div key={index}>
                                <h3 className={config.sectionTitle}>{section.title}</h3>
                                <ul className="space-y-3">
                                    {section.links.map((link: any, linkIndex: number) => (
                                        <li key={linkIndex}>
                                            {link.target === '_blank' ? (
                                                <a href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} target="_blank" rel="noopener noreferrer" className="text-gray-600 hover:text-gray-900 transition-colors text-sm">
                                                    {link.text}
                                                </a>
                                            ) : (
                                                <Link href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} className="text-gray-600 hover:text-gray-900 transition-colors text-sm">
                                                    {link.text}
                                                </Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )
                    ))}
                </div>
            );
        }

        // Standard and modern layouts
        return sectionData.navigation_sections?.map((section: any, index: number) => (
            section.links?.length > 0 && (
                <div key={index}>
                    <h3 className={config.sectionTitle}>{section.title}</h3>
                    <ul className="space-y-3">
                        {section.links.map((link: any, linkIndex: number) => (
                            <li key={linkIndex}>
                                {link.target === '_blank' ? (
                                    <a href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} target="_blank" rel="noopener noreferrer" className={`transition-all duration-300 text-base ${config.layout === 'modern' ? 'text-white/80 hover:text-white hover:translate-x-2 hover:drop-shadow-lg' : 'text-gray-400 hover:text-white'}`}>
                                        {link.text}
                                    </a>
                                ) : (
                                    <Link href={link.href?.startsWith('/page/') ? route('custom-page.show', link.href.replace('/page/', '')) : link.href} className={`transition-all duration-300 text-base ${config.layout === 'modern' ? 'text-white/80 hover:text-white hover:translate-x-2 hover:drop-shadow-lg' : 'text-gray-400 hover:text-white'}`}>
                                        {link.text}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            )
        ));
    };

    const renderNewsletter = () => {
        if (config.layout === 'minimal') {
            return (
                <div className="space-y-3">
                    <h3 className={config.newsletterTitle}>{newsletterTitle}</h3>
                    <p className="text-gray-600 text-sm">{newsletterDescription}</p>
                    <div className="flex items-center space-x-3">
                        <form onSubmit={handleNewsletterSubmit} className="flex items-center space-x-3">
                            <input 
                                type="email" 
                                placeholder="Enter email"
                                value={emailInput}
                                onChange={(e) => setEmailInput(e.target.value)}
                                disabled={isSubmitting}
                                className="px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:border-blue-500 text-sm disabled:opacity-50"
                            />
                            <button 
                                type="submit"
                                disabled={isSubmitting}
                                className="text-white px-4 py-2 rounded-md text-sm font-medium transition-colors disabled:opacity-50" 
                                style={{ backgroundColor: colors.primary }}
                            >
                                {isSubmitting ? 'Subscribing...' : newsletterButtonText}
                            </button>
                        </form>
                    </div>
                </div>
            );
        }

        return (
            <div className={config.layout === 'split' ? 'lg:col-span-2' : ''}>
                <h3 className={config.newsletterTitle}>{newsletterTitle}</h3>
                <p className={`text-sm mb-4 ${config.layout === 'split' ? 'text-gray-600' : config.layout === 'centered' || config.layout === 'modern' ? 'text-gray-300' : 'text-gray-400'}`}>
                    {newsletterDescription}
                </p>
                <div className={`flex ${config.layout === 'modern' ? 'shadow-2xl' : ''}`}>
                    <form onSubmit={handleNewsletterSubmit} className={`flex ${config.layout === 'modern' ? 'shadow-2xl' : ''} w-full`}>
                        <input 
                            type="email" 
                            placeholder="Enter email"
                            value={emailInput}
                            onChange={(e) => setEmailInput(e.target.value)}
                            disabled={isSubmitting}
                            className={`flex-1 w-full px-4 py-3 rounded-l-lg text-sm focus:outline-none transition-all duration-300 disabled:opacity-50 ${
                                config.layout === 'split' 
                                    ? 'bg-white border border-gray-300 text-gray-900 placeholder-gray-500 focus:border-blue-500'
                                    : config.layout === 'modern'
                                        ? 'bg-white/20 backdrop-blur-md border border-white/30 text-white placeholder-white/60 focus:border-white focus:bg-white/30'
                                        : 'bg-gray-800 border border-gray-600 text-white placeholder-gray-400 focus:border-blue-500'
                            }`}
                        />
                        <button 
                            type="submit"
                            disabled={isSubmitting}
                            className={`text-white px-6 py-3 rounded-r-lg font-semibold transition-all duration-300 disabled:opacity-50 ${
                                config.layout === 'modern' ? 'hover:scale-105 hover:shadow-xl transform' : ''
                            }`}
                            style={{ backgroundColor: colors.primary }} 
                            onMouseEnter={(e) => !isSubmitting && (e.currentTarget.style.backgroundColor = colors.secondary)} 
                            onMouseLeave={(e) => !isSubmitting && (e.currentTarget.style.backgroundColor = colors.primary)}
                        >
                            {isSubmitting ? 'Subscribing...' : newsletterButtonText}
                        </button>
                    </form>
                </div>
            </div>
        );
    };

    if (config.layout === 'minimal') {
        return (
            <footer className={config.footer}>
                <div className={config.container}>
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-8 lg:space-y-0">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:space-x-12 space-y-6 lg:space-y-0">
                            {renderCompanyInfo()}
                            {renderNavigationSections()}
                        </div>
                        <div className="lg:ml-auto">
                            {renderNewsletter()}
                        </div>
                    </div>
                    <div className={config.copyright}>
                        <p>{copyrightText}</p>
                    </div>
                </div>
            </footer>
        );
    }

    if (config.layout === 'centered') {
        return (
            <footer className={config.footer}>
                <div className={config.container}>
                    <div className={config.grid}>
                        {renderCompanyInfo()}
                        {renderNavigationSections()}
                        {renderNewsletter()}
                    </div>
                    <div className={config.copyright}>
                        <p>{copyrightText}</p>
                    </div>
                </div>
            </footer>
        );
    }

    if (config.layout === 'split') {
        return (
            <footer className={config.footer}>
                <div className={config.container}>
                    <div className={config.grid}>
                        <div>
                            {renderCompanyInfo()}
                            {renderNewsletter()}
                        </div>
                        <div>
                            {renderNavigationSections()}
                        </div>
                    </div>
                    <div className={config.copyright}>
                        <p>{copyrightText}</p>
                    </div>
                </div>
            </footer>
        );
    }

    // Standard and modern layouts
    return (
        <footer className={config.footer} style={getBackgroundStyle()}>
            {config.layout === 'modern' && (
                <>
                    <div className="absolute inset-0 bg-black/20"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                    <div className="absolute top-0 left-0 w-full h-full">
                        <div className="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                        <div className="absolute bottom-20 right-20 w-48 h-48 bg-white/5 rounded-full blur-3xl animate-pulse delay-1000"></div>
                        <div className="absolute top-1/2 left-1/3 w-24 h-24 bg-white/10 rounded-full blur-2xl animate-pulse delay-500"></div>
                    </div>
                    <style>{`
                        @keyframes gradientShift {
                            0% { background-position: 0% 50%; }
                            50% { background-position: 100% 50%; }
                            100% { background-position: 0% 50%; }
                        }
                    `}</style>
                </>
            )}
            <div className={config.container}>
                <div className={config.grid}>
                    {renderCompanyInfo()}
                    {renderNavigationSections()}
                    {renderNewsletter()}
                </div>
                <div className={config.copyright}>
                    <p>{copyrightText}</p>
                </div>
            </div>
        </footer>
    );
}