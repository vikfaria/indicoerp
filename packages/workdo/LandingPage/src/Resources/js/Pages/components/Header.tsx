import { Menu, X } from 'lucide-react';
import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

interface HeaderProps {
    settings?: any;
}

const HEADER_VARIANTS = {
    header1: {
        nav: 'bg-white border-b border-gray-200 sticky top-0 z-50',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        wrapper: 'flex justify-between items-center h-16',
        logo: 'text-2xl font-bold lg:max-w-[180px] max-w-[140px]',
        desktop: 'hidden md:flex items-center space-x-2',
        mobile: 'md:hidden text-gray-600 p-2 transition-colors',
        mobileMenu: 'md:hidden bg-white border-t'
    },
    header2: {
        nav: 'bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        wrapper: 'flex flex-col items-center py-6 space-y-6',
        logo: 'text-3xl font-bold',
        desktop: 'flex items-center space-x-2 bg-gray-50 px-6 py-3 rounded-full',
        mobile: 'md:hidden text-gray-600 p-2 transition-colors absolute top-4 right-4 hover:bg-gray-100 rounded-lg',
        mobileMenu: 'md:hidden bg-white border-t w-full shadow-lg'
    },
    header3: {
        nav: 'bg-white/95 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50',
        container: 'max-w-6xl mx-auto px-6 sm:px-8 lg:px-10',
        wrapper: 'flex justify-between items-center h-14 py-2',
        logo: 'text-xl font-bold',
        desktop: 'hidden md:flex items-center space-x-2',
        mobile: 'md:hidden text-gray-600 p-2 transition-colors hover:bg-gray-100 rounded-md',
        mobileMenu: 'md:hidden bg-white/95 backdrop-blur-md border-t'
    },
    header4: {
        nav: 'bg-black/20 backdrop-blur-md absolute top-0 left-0 right-0 z-50 border-b border-white/10',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        wrapper: 'flex justify-between items-center h-20 py-4',
        logo: 'text-2xl font-bold text-white drop-shadow-lg',
        desktop: 'hidden md:flex items-center space-x-2',
        mobile: 'md:hidden text-white p-2 transition-colors hover:bg-white/10 rounded-lg',
        mobileMenu: 'md:hidden bg-black/90 backdrop-blur-md border-t border-white/10'
    },
    header5: {
        nav: 'sticky top-0 z-50 shadow-xl',
        container: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
        wrapper: 'flex justify-between items-center h-20 py-4',
        logo: 'text-2xl font-bold text-white drop-shadow-lg',
        desktop: 'hidden md:flex items-center space-x-2',
        mobile: 'md:hidden text-white p-2 transition-colors hover:bg-white/10 rounded-lg',
        mobileMenu: 'md:hidden border-t border-white/20'
    }
};

export default function Header({ settings }: HeaderProps) {
    const sectionData = settings?.config_sections?.sections?.header || {};
    const { t } = useTranslation();
    const variant = sectionData.variant || 'header1';
    const config = HEADER_VARIANTS[variant as keyof typeof HEADER_VARIANTS] || HEADER_VARIANTS.header1;
    
    const companyName = sectionData.company_name || settings?.company_name || 'ERPGo SaaS';
    const isAuthenticated = settings?.is_authenticated;
    const ctaText = isAuthenticated ? 'Dashboard' : (sectionData.cta_text || 'Get Started');
    const colors = settings?.config_sections?.colors || { primary: '#10b77f', secondary: '#059669', accent: '#f59e0b' };
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    
    const themeMode = getAdminSetting('theme_mode') || 'light';
    const logoKey = themeMode === 'dark' ? 'logo_light' : 'logo_dark';
    const logoPath = getAdminSetting(logoKey);
    const logoUrl = logoPath ? getImagePath(logoPath) : null;
    
    // Use dynamic navigation items from settings or empty array
    const navigationItems = sectionData.navigation_items || [];

    // Add custom pages to navigation if they exist
    const customPages = settings?.custom_pages || [];
    const customPageItems = customPages.map(page => ({
        text: page.title,
        href: `/page/${page.slug}`,
        target: '_self'
    }));
    
    // Combine navigation items with custom pages
    const allNavigationItems = [...navigationItems, ...customPageItems];

    const renderNavItems = (isMobile = false) => {
        const isTransparentOrGradient = variant === 'header4' || variant === 'header5';
        const textColor = isTransparentOrGradient ? 'text-white' : 'text-gray-600';
        const hoverBg = variant === 'header2' ? 'hover:bg-white hover:shadow-sm' : variant === 'header3' ? 'hover:bg-gray-50' : isTransparentOrGradient ? 'hover:bg-white/10' : 'hover:bg-gray-50';
        
        return allNavigationItems.map((item) => {
            const href = item.href?.startsWith('/page/') ? route('custom-page.show', item.href.replace('/page/', '')) : item.href;
            return item.target === '_blank' ? (
                <a 
                    key={item.text} 
                    href={href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={isMobile 
                        ? `block px-4 py-3 text-base font-medium ${textColor} ${hoverBg} rounded-lg transition-all` 
                        : `${textColor} px-4 py-2 text-sm font-medium ${hoverBg} rounded-lg transition-all duration-200`
                    }
                    style={!isMobile ? { '--hover-color': isTransparentOrGradient ? 'white' : colors.primary } as React.CSSProperties : {}}
                    onMouseEnter={!isMobile ? (e) => {
                        if (!isTransparentOrGradient) {
                            e.currentTarget.style.color = colors.primary;
                        }
                    } : undefined}
                    onMouseLeave={!isMobile ? (e) => e.currentTarget.style.color = '' : undefined}
                >
                    {item.text}
                </a>
            ) : (
                <Link 
                    key={item.text} 
                    href={href} 
                    className={isMobile 
                        ? `block px-4 py-3 text-base font-medium ${textColor} ${hoverBg} rounded-lg transition-all` 
                        : `${textColor} px-4 py-2 text-sm font-medium ${hoverBg} rounded-lg transition-all duration-200`
                    }
                    style={!isMobile ? { '--hover-color': isTransparentOrGradient ? 'white' : colors.primary } as React.CSSProperties : {}}
                    onMouseEnter={!isMobile ? (e) => {
                        if (!isTransparentOrGradient) {
                            e.currentTarget.style.color = colors.primary;
                        }
                    } : undefined}
                    onMouseLeave={!isMobile ? (e) => e.currentTarget.style.color = '' : undefined}
                >
                    {item.text}
                </Link>
            );
        });
    };

    const renderCTAButtons = (isMobile = false) => {
        const enableRegistration = settings?.enable_registration !== false;
        if (isAuthenticated) {
            return (
                <button 
                    onClick={() => router.visit(route('dashboard'))}
                    className={`text-white rounded-md font-medium transition-colors ${
                        isMobile ? 'px-4 py-2 text-sm w-full' : 
                        variant === 'header3' ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm'
                    }`}
                    style={{ backgroundColor: colors.primary }} 
                    onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.secondary} 
                    onMouseLeave={(e) => e.currentTarget.style.backgroundColor = colors.primary}
                >
                    {t('Dashboard')}
                </button>
            );
        }
        
        if (enableRegistration) {
            return (
                <div className={`flex ${isMobile ? 'flex-col space-y-2' : 'space-x-2'}`}>
                    <button 
                        onClick={() => router.visit(route('login'))}
                        className={`border rounded-md font-medium transition-colors ${
                            isMobile ? 'px-4 py-2 text-sm w-full' : 
                            variant === 'header3' ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm'
                        }`}
                        style={{ borderColor: colors.primary, color: colors.primary }}
                        onMouseEnter={(e) => {
                            e.currentTarget.style.backgroundColor = colors.primary;
                            e.currentTarget.style.color = 'white';
                        }}
                        onMouseLeave={(e) => {
                            e.currentTarget.style.backgroundColor = 'transparent';
                            e.currentTarget.style.color = colors.primary;
                        }}
                    >
                        {t('Sign In')}
                    </button>
                    <button 
                        onClick={() => router.visit(route('register'))}
                        className={`text-white rounded-md font-medium transition-colors ${
                            isMobile ? 'px-4 py-2 text-sm w-full' : 
                            variant === 'header3' ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm'
                        }`}
                        style={{ backgroundColor: colors.primary }} 
                        onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.secondary} 
                        onMouseLeave={(e) => e.currentTarget.style.backgroundColor = colors.primary}
                    >
                        {t('Get Started')}
                    </button>
                </div>
            );
        }
        
        return (
            <button 
                onClick={() => router.visit(route('login'))}
                className={`text-white rounded-md font-medium transition-colors ${
                    isMobile ? 'px-4 py-2 text-sm w-full' : 
                    variant === 'header3' ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm'
                }`}
                style={{ backgroundColor: colors.primary }} 
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.secondary} 
                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = colors.primary}
            >
                {t('Sign In')}
            </button>
        );
    };

    const getGradientStyle = () => {
        if (variant === 'header5') {
            return {
                background: `linear-gradient(to right, ${colors.primary}, ${colors.secondary}, ${colors.accent})`
            };
        }
        return {};
    };

    const getMobileMenuStyle = () => {
        if (variant === 'header5') {
            return {
                background: `linear-gradient(to right, ${colors.primary}, ${colors.secondary})`
            };
        }
        return {};
    };

    return (
        <nav className={config.nav} style={getGradientStyle()}>
            <div className={config.container}>
                <div className={config.wrapper}>
                    <Link href={route('landing.page')} className={config.logo} style={{ color: colors.primary }}>
                        {logoUrl ? (
                            <img src={logoUrl} alt={companyName} className="w-auto" />
                        ) : (
                            companyName
                        )}
                    </Link>
                    
                    <div className={config.desktop}>
                        {renderNavItems()}
                        {sectionData?.enable_pricing_link !== false && (
                            <Link 
                                href={route("pricing.page")}
                                className={`px-4 py-2 text-sm font-medium transition-all duration-200 rounded-lg ${
                                    variant === 'header4' || variant === 'header5' 
                                        ? 'text-white hover:bg-white/10' 
                                        : variant === 'header2' 
                                            ? 'text-gray-600 hover:bg-white hover:shadow-sm'
                                            : 'text-gray-600 hover:bg-gray-50'
                                }`}
                                onMouseEnter={(e) => {
                                    if (variant !== 'header4' && variant !== 'header5') {
                                        e.currentTarget.style.color = colors.primary;
                                    }
                                }}
                                onMouseLeave={(e) => e.currentTarget.style.color = ''}
                            >
                                {t('Pricing')}
                            </Link>
                        )}
                        {renderCTAButtons()}
                    </div>
                    
                    <button 
                        className={config.mobile}
                        onMouseEnter={(e) => e.currentTarget.style.color = colors.primary} 
                        onMouseLeave={(e) => e.currentTarget.style.color = ''}
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    >
                        {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>
            </div>
            
            {mobileMenuOpen && (
                <div className={config.mobileMenu} style={getMobileMenuStyle()}>
                    <div className="px-2 pt-2 pb-3 space-y-1">
                        {renderNavItems(true)}
                        <div className="px-3 py-2">
                            {sectionData?.enable_pricing_link !== false && (
                                <Link 
                                    href={route("pricing.page")}
                                    className="block px-3 py-2 text-base font-medium text-gray-600"
                                >
                                    {t('Pricing')}
                                </Link>
                            )}
                            {renderCTAButtons(true)}
                        </div>
                    </div>
                </div>
            )}
        </nav>
    );
}